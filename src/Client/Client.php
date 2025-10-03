<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Client;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ScienceStories\Mqtt\Contract\ClientInterface;
use ScienceStories\Mqtt\Contract\MetricsInterface;
use ScienceStories\Mqtt\Contract\TransportInterface;
use ScienceStories\Mqtt\Events\MessageReceived as EvMessageReceived;
use ScienceStories\Mqtt\Events\PacketReceived as EvPacketReceived;
use ScienceStories\Mqtt\Events\PacketSent as EvPacketSent;
use ScienceStories\Mqtt\Exception\ProtocolError;
use ScienceStories\Mqtt\Exception\Timeout;
use ScienceStories\Mqtt\Exception\TransportError;
use ScienceStories\Mqtt\Protocol\MqttVersion;
use ScienceStories\Mqtt\Protocol\V311\Decoder as V311Decoder;
use ScienceStories\Mqtt\Protocol\V311\Encoder as V311Encoder;
use ScienceStories\Mqtt\Protocol\Packet\PacketType;
use ScienceStories\Mqtt\Protocol\Packet\Connect as ConnectPacket;
use ScienceStories\Mqtt\Protocol\Packet\Publish;
use ScienceStories\Mqtt\Protocol\V5\Decoder as V5Decoder;
use ScienceStories\Mqtt\Protocol\V5\Encoder as V5Encoder;
use ScienceStories\Mqtt\Util\Bytes;
use ScienceStories\Mqtt\Util\RandomId;

/**
 * Client supporting CONNECT/DISCONNECT, PUBLISH QoS0 and basic SUBSCRIBE/receive for MQTT 3.1.1 and 5.0.
 */
final class Client implements ClientInterface
{
    private TransportInterface $transport;

    private Options $options;

    private V311Encoder|V5Encoder $encoder;

    private V311Decoder|V5Decoder $decoder;

    private LoggerInterface $logger;

    private ?EventDispatcherInterface $events = null;

    private ?MetricsInterface $metrics = null;

    /** @var \SplQueue<InboundMessage> */
    private \SplQueue $inbound;

    /** @var callable|null */
    private $messageHandler = null;

    private bool $shouldStop = false;

    /** @var array{packetId:int,codes:list<int>}|null */
    private ?array $lastSubAck = null;

    /** @var array{packetId:int,codes:list<int>}|null */
    private ?array $lastUnsubAck = null;

    private float $lastActivity = 0.0;

    private bool $pingOutstanding = false;

    private ?int $lastPubAck = null;

    private ?int $lastPubComp = null;

    /** @var array<int, InboundMessage> QoS2 inbound pending messages by Packet Identifier */
    private array $qos2InboundPending = [];

    /** @var array<string, array{qos:int, options:?SubscribeOptions}> Map of topic filter => subscription settings */
    private array $subscriptions = [];

    private int $reconnectAttempts = 0;

    private bool $isResubscribing = false;

    /** @var array<int, float> Recently seen QoS1 Packet Identifiers for de-duplication */
    private array $qos1Seen = [];

    private int $qos1SeenMax = 256;

    private function touchActivity(): void
    {
        $this->lastActivity = microtime(true);
    }

    private function dispatch(object $event): void
    {
        if ($this->events instanceof EventDispatcherInterface) {
            $this->events->dispatch($event);
        }
    }

    private function sendPingReq(): void
    {
        // Build PINGREQ without awaiting response; loopOnce() or ping() will handle PINGRESP
        $pkt = \chr(PacketType::PINGREQ->value << 4).\chr(0);
        $this->logger->debug('>> PINGREQ (auto)');
        $this->transport->write($pkt);
        $this->pingOutstanding = true;
        $this->touchActivity();
    }

    private function maybeAutoPing(?float $timeoutSec): void
    {
        $keep = $this->options->keepAlive;
        if ($keep <= 0) {
            return;
        }
        $now     = microtime(true);
        $elapsed = $now - $this->lastActivity;
        // Guard threshold: send it slightly before keepAlive expires (e.g., 90% of interval) to be safe
        $threshold = max(1.0, $keep * 0.9);
        if ($elapsed >= $threshold && ! $this->pingOutstanding && $this->transport->isOpen()) {
            $this->sendPingReq();
        }
    }

    public function __construct(Options $options, TransportInterface $transport, V311Encoder|V5Encoder|null $enc = null, V311Decoder|V5Decoder|null $dec = null, ?LoggerInterface $logger = null, ?EventDispatcherInterface $events = null, ?MetricsInterface $metrics = null)
    {
        $this->options   = $options;
        $this->transport = $transport;
        $this->logger    = $logger ?? new NullLogger();
        $this->events    = $events;
        $this->metrics   = $metrics;
        $this->inbound   = new \SplQueue();
        if ($enc && $dec) {
            $this->encoder = $enc;
            $this->decoder = $dec;
        } else {
            // Choose by protocol version
            if ($options->version === MqttVersion::V5_0) {
                $this->encoder = new V5Encoder();
                $this->decoder = new V5Decoder();
            } else {
                $this->encoder = new V311Encoder();
                $this->decoder = new V311Decoder();
            }
        }
        $this->touchActivity();
    }

    public function connect(): ConnectResult
    {
        $this->logger->info('Opening connection', ['host' => $this->options->host, 'port' => $this->options->port]);
        $this->transport->open($this->options->host, $this->options->port);

        if ($this->options->useTls) {
            // Ensure peer_name defaults to host for SNI if not provided
            $tls = $this->options->tlsOptions ?? [];
            $ssl = \is_array($tls['ssl'] ?? null) ? $tls['ssl'] : $tls;
            if (! \array_key_exists('peer_name', $ssl)) {
                $tls = ['ssl' => $ssl + ['peer_name' => $this->options->host]];
            }
            $this->logger->info('Enabling TLS');
            $this->transport->enableTls($tls);
        }

        // Build CONNECT packet
        $connectProps = null;
        if ($this->options->version === MqttVersion::V5_0 && $this->options->sessionExpiry !== null) {
            $connectProps = ['session_expiry_interval' => $this->options->sessionExpiry];
        }
        $connect = new ConnectPacket(
            $this->options->clientId,
            $this->options->keepAlive,
            $this->options->cleanSession,
            $this->options->username,
            $this->options->password,
            $this->options->will,
            $connectProps,
        );
        $data = $this->encoder->encodeConnect($connect);
        $this->logger->debug('>> CONNECT', ['bytes' => \strlen($data), 'preview' => $this->hexPreview($data)]);
        $this->transport->write($data);
        $this->touchActivity();

        // Read the fixed header: 1 byte type, then varint remaining length (1-4 bytes)
        $this->logger->debug('<< waiting for CONNACK');
        $typeByte = $this->transport->readExact(1, 5.0);
        $this->touchActivity();
        $packetType = \ord($typeByte[0]) >> 4;
        if ($packetType !== PacketType::CONNACK->value) {
            throw new ProtocolError("Expected CONNACK, got type {$packetType}");
        }

        // Read Remaining Length varint
        $varBytes = '';
        for ($i = 0; $i < 4; $i++) {
            $b = $this->transport->readExact(1, 5.0);
            $varBytes .= $b;
            $byte = \ord($b);
            if (($byte & 0x80) === 0) {
                break;
            }
        }
        $consumed     = 0;
        $remainingLen = Bytes::decodeVarInt($varBytes, $consumed);
        if ($remainingLen < 2) {
            // v3: at least 2 bytes (ack flags + return code), v5: also followed by properties
            throw new ProtocolError("Invalid CONNACK length: {$remainingLen}");
        }

        $body = $this->transport->readExact($remainingLen, 5.0);
        $this->touchActivity();
        $this->logger->debug('<< CONNACK', ['bytes' => $remainingLen, 'preview' => $this->hexPreview($typeByte.$varBytes.$body)]);
        $connack = $this->decoder->decodeConnAck($body);

        $versionStr = $this->options->version->value;

        $this->logger->info('CONNACK', ['sessionPresent' => $connack->sessionPresent, 'reasonCode' => $connack->returnCode, 'version' => $versionStr]);

        $assignedId = null;
        if ($this->options->version === MqttVersion::V5_0 && \is_array($connack->properties)) {
            $assignedId = $connack->properties['assigned_client_identifier'] ?? null;
            if (! \is_string($assignedId)) {
                $assignedId = null;
            }
        }

        return new ConnectResult(
            $connack->sessionPresent,
            'MQTT',
            $versionStr,
            $connack->returnCode,
            $assignedId,
        );
    }

    public function disconnect(string $reason = ''): void
    {
        if (! $this->transport->isOpen()) {
            return;
        }

        // DISCONNECT fixed header: type 14, flags 0, length 0
        $packet = \chr(PacketType::DISCONNECT->value << 4).\chr(0);
        $this->logger->debug('>> DISCONNECT');
        $this->transport->write($packet);

        $this->transport->close();
        $this->logger->info('Disconnected', ['reason' => $reason]);
    }

    public function publish(string $topic, string $payload, ?PublishOptions $options = null): int
    {
        $options ??= new PublishOptions();

        $qos      = $options->qos->value;
        $packetId = null;
        if ($qos > 0) {
            $packetId = RandomId::packetId();
        }

        $pkt = new Publish(
            $topic,
            $payload,
            $options->qos,
            $options->retain,
            $options->dup,
            packetId: $packetId,
            properties: $options->properties,
        );

        $data = $this->encoder->encodePublish($pkt);
        $this->logger->debug('>> PUBLISH', [
            'topic'         => $topic,
            'qos'           => $options->qos->value,
            'retain'        => $options->retain,
            'bytes'         => \strlen($data),
            'payload_bytes' => \strlen($payload),
            'properties'    => $options->properties ?? [],
            'preview'       => $this->hexPreview($data),
            'packetId'      => $packetId,
        ]);
        // PSR-14: emit PacketSent event for PUBLISH
        $this->dispatch(new EvPacketSent($data, PacketType::PUBLISH->value));
        $this->transport->write($data);
        if ($this->metrics) {
            $this->metrics->increment('publish_sent', 1.0, ['qos' => $qos]);
            $this->metrics->size('publish_payload_bytes', \strlen($payload), ['qos' => $qos]);
        }
        $this->touchActivity();

        switch ($qos) {
            case 0:
                return 0;
            case 1:
                $pid      = (int) $packetId;
                $deadline = microtime(true) + 5.0;
                while (true) {
                    $timeLeft = $deadline - microtime(true);
                    if ($timeLeft <= 0) {
                        throw new Timeout('Timed out waiting for PUBACK');
                    }
                    $ok = $this->loopOnce(max(0.01, $timeLeft));
                    if (! $ok) {
                        continue;
                    }
                    $ack = $this->lastPubAck;
                    if (\is_int($ack) && $ack === $pid) {
                        $this->logger->info('PUBACK', ['packetId' => $ack]);
                        $this->lastPubAck = null;

                        return $pid;
                    }
                }
                // no break
            case 2:
                $pid               = (int) $packetId;
                $deadline          = microtime(true) + 5.0;
                $this->lastPubComp = null;
                while (true) {
                    $timeLeft = $deadline - microtime(true);
                    if ($timeLeft <= 0) {
                        throw new Timeout('Timed out waiting for QoS2 handshake');
                    }
                    $ok = $this->loopOnce(max(0.01, $timeLeft));
                    if (! $ok) {
                        continue;
                    }
                    // Completion when PUBCOMP arrives for our packetId
                    $comp = $this->lastPubComp;
                    // @phpstan-ignore-next-line state is updated by loopOnce()
                    if (\is_int($comp) && $comp === $pid) {
                        $this->logger->info('PUBCOMP', ['packetId' => $comp]);
                        $this->lastPubComp = null;

                        return $pid;
                    }
                }
        }

        return 0; // @phpstan-ignore-line unreachable: all cases handled or exceptions thrown above
    }

    public function ping(?float $timeoutSec = 5.0): bool
    {
        if (! $this->transport->isOpen()) {
            throw new \LogicException('Cannot PING: transport not open');
        }
        $pkt = \chr(PacketType::PINGREQ->value << 4).\chr(0);
        $this->logger->debug('>> PINGREQ');
        $this->transport->write($pkt);
        $this->pingOutstanding = true;
        $this->touchActivity();

        $hdr = $this->transport->readExact(2, $timeoutSec ?? 5.0);
        $this->touchActivity();
        $type = (\ord($hdr[0]) >> 4);
        $rl   = \ord($hdr[1]);
        if ($type === PacketType::PINGRESP->value && $rl === 0) {
            $this->pingOutstanding = false;
            $this->logger->info('PINGRESP OK');

            return true;
        }
        $this->logger->info('PINGRESP unexpected', ['type' => $type, 'len' => $rl, 'preview' => $this->hexPreview($hdr)]);
        throw new ProtocolError('Unexpected response to PINGREQ');
    }

    public function subscribe(array $topics, int $qos = 0): void
    {
        $filters = [];
        foreach ($topics as $t) {
            $filters[] = ['filter' => (string) $t, 'qos' => $qos];
        }
        $this->subscribeWith($filters, null);
    }

    public function subscribeWith(array $filters, ?SubscribeOptions $options = null): SubscribeResult
    {
        $pid  = RandomId::packetId();
        $data = $this->encoder->encodeSubscribe($filters, $pid, $options);
        $this->logger->debug('>> SUBSCRIBE', ['packetId' => $pid, 'filters' => $filters, 'bytes' => \strlen($data), 'preview' => $this->hexPreview($data)]);
        $this->transport->write($data);
        $this->touchActivity();

        // Await SUBACK; handle interleaved PUBLISH if broker sends retained messages immediately.
        $deadline = microtime(true) + 5.0;
        while (true) {
            $timeLeft = $deadline - microtime(true);
            if ($timeLeft <= 0) {
                throw new Timeout('Timed out waiting for SUBACK');
            }
            $ok = $this->loopOnce(max(0.01, $timeLeft));
            if (! $ok) {
                // timeout in loopOnce - continue loop until overall deadline
                continue;
            }
            // loopOnce handles SUBACK internally and stores last; break when found
            if (isset($this->lastSubAck) && $this->lastSubAck['packetId'] === $pid) {
                $codes = $this->lastSubAck['codes'];
                unset($this->lastSubAck);
                $this->logger->info('SUBACK', ['packetId' => $pid, 'codes' => $codes]);

                // Record subscriptions if this is a user-initiated subscribed (avoid duplicate records during resubscribe)
                if (! $this->isResubscribing) {
                    $this->recordSubscriptionsFromFilters($filters, $options);
                }

                return new SubscribeResult($pid, $codes);
            }
        }
    }

    public function unsubscribe(array $topics): void
    {
        $pid = RandomId::packetId();
        // Build filters list of strings; encoders accept list<string>
        $filters = [];
        foreach ($topics as $t) {
            $f = (string) $t;
            if ($f !== '') {
                $filters[] = $f;
            }
        }
        if ($filters === []) {
            return;
        }
        $data = $this->encoder->encodeUnsubscribe($filters, $pid);
        $this->logger->debug('>> UNSUBSCRIBE', ['packetId' => $pid, 'filters' => $filters, 'bytes' => \strlen($data), 'preview' => $this->hexPreview($data)]);
        $this->transport->write($data);
        $this->touchActivity();

        $deadline = microtime(true) + 5.0;
        while (true) {
            $timeLeft = $deadline - microtime(true);
            if ($timeLeft <= 0) {
                throw new Timeout('Timed out waiting for UNSUBACK');
            }
            $ok = $this->loopOnce(max(0.01, $timeLeft));
            if (! $ok) {
                continue;
            }
            if (isset($this->lastUnsubAck) && $this->lastUnsubAck['packetId'] === $pid) {
                $codes = $this->lastUnsubAck['codes'];
                unset($this->lastUnsubAck);
                $this->logger->info('UNSUBACK', ['packetId' => $pid, 'codes' => $codes]);

                // Remove from stored subscriptions
                $this->removeSubscriptions($filters);

                return;
            }
        }
    }

    public function loopOnce(?float $timeoutSec = 0.1): bool
    {
        // Auto-reconnect if transport is closed and the feature enabled
        if (! $this->transport->isOpen() && $this->options->autoReconnect) {
            $this->logger->info('Transport not open; attempting auto-reconnect');
            $ok = $this->attemptReconnect();
            if (! $ok) {
                return false;
            }
        }

        // Before attempting to read, send an automatic PING if needed.
        $this->maybeAutoPing($timeoutSec);
        try {
            $b0 = $this->transport->readExact(1, $timeoutSec);
        } catch (Timeout) {
            return false; // nothing available
        } catch (TransportError) {
            // Transport broke; attempt to reconnect next iteration
            $this->logger->info('Transport error on readExact; will try to reconnect');
            $this->transport->close();

            return false;
        }
        $type  = (\ord($b0[0]) >> 4);
        $flags = (\ord($b0[0]) & 0x0F);

        // Remaining Length varint
        $varBytes = '';
        for ($i = 0; $i < 4; $i++) {
            $b = $this->transport->readExact(1, $timeoutSec);
            $varBytes .= $b;
            $byte = \ord($b);
            if (($byte & 0x80) === 0) {
                break;
            }
        }
        $consumed = 0;
        $rl       = Bytes::decodeVarInt($varBytes, $consumed);
        $body     = $this->transport->readExact($rl, $timeoutSec);
        $this->touchActivity();
        // PSR-14: emit PacketReceived with full frame bytes
        $raw = $b0.$varBytes.$body;
        if ($this->metrics) {
            $this->metrics->increment('packets_received', 1.0, ['type' => $type]);
            $this->metrics->size('packet_bytes', \strlen($raw), ['dir' => 'in', 'type' => $type]);
        }
        $this->dispatch(new EvPacketReceived($raw, $type, $flags, $rl));

        switch ($type) {
            case PacketType::PUBLISH->value:
                $msg = $this->decoder->decodePublish($flags, $body);
                // QoS1: immediately acknowledge with PUBACK (v3/v5 minimal form)
                if ($msg->qos->value === 1 && $msg->packetId !== null) {
                    $puback = \chr(PacketType::PUBACK->value << 4).\chr(2).pack('n', $msg->packetId);
                    $this->logger->debug('>> PUBACK', ['packetId' => $msg->packetId]);
                    $this->transport->write($puback);
                    $this->touchActivity();
                    // QoS1 idempotency: suppress duplicate deliveries for same Packet Identifier
                    $pid = $msg->packetId;
                    if (isset($this->qos1Seen[$pid])) {
                        $this->logger->debug('QoS1 duplicate PUBLISH suppressed', ['packetId' => $pid, 'dup' => $msg->dup]);

                        return true;
                    }
                    $this->qos1Seen[$pid] = microtime(true);
                    if (\count($this->qos1Seen) > $this->qos1SeenMax) {
                        // Drop the oldest (insertion order preserved in PHP arrays)
                        array_shift($this->qos1Seen);
                    }
                    // QoS1: decide delivery based on client-side filters
                    $this->logger->debug('<< PUBLISH', ['topic' => $msg->topic, 'bytes' => $rl, 'qos' => $msg->qos->value, 'retain' => $msg->retain]);
                    $this->deliverIfMatches($msg);

                    return true;
                }
                // QoS2: respond with PUBREC and store pending until PUBREL
                if ($msg->qos->value === 2 && $msg->packetId !== null) {
                    // If duplicate PUBLISH (a DUP flag may be set), resend PUBREC but do not duplicate store
                    $pid    = $msg->packetId;
                    $pubrec = \chr(PacketType::PUBREC->value << 4).\chr(2).pack('n', $pid);
                    $this->logger->debug('>> PUBREC', ['packetId' => $pid]);
                    $this->transport->write($pubrec);
                    $this->touchActivity();
                    // Store message only if not already pending
                    if (! isset($this->qos2InboundPending[$pid])) {
                        $this->qos2InboundPending[$pid] = $msg;
                    }

                    return true;
                }
                // QoS0: deliver immediately (subject to client-side filters)
                $this->logger->debug('<< PUBLISH', ['topic' => $msg->topic, 'bytes' => $rl, 'qos' => $msg->qos->value, 'retain' => $msg->retain]);
                $this->deliverIfMatches($msg);

                return true;
            case PacketType::PINGRESP->value:
                $this->logger->debug('<< PINGRESP');
                // Clear outstanding flag to allow future auto PINGs
                $this->pingOutstanding = false;

                return true;
            case PacketType::SUBACK->value:
                // store last SUBACK for subscribeWith waiter
                $dec              = $this->decoder;
                $this->lastSubAck = $dec->decodeSubAck($body);

                return true;
            case PacketType::UNSUBACK->value:
                // store last UNSUBACK for unsubscribed waiter
                if ($this->options->version === MqttVersion::V5_0) {
                    /** @var V5Decoder $dec */
                    $dec                = $this->decoder;
                    $this->lastUnsubAck = $dec->decodeUnsubAck($body);
                } else {
                    /** @var V311Decoder $dec */
                    $dec                = $this->decoder;
                    $arr                = $dec->decodeUnsubAck($body);
                    $this->lastUnsubAck = ['packetId' => $arr['packetId'], 'codes' => []];
                }

                return true;
            case PacketType::PUBACK->value:
                // Minimal PUBACK handling: first 2 bytes are Packet Identifier (v3 and v5). Extra v5 fields ignored.
                if ($rl >= 2) {
                    $this->lastPubAck = $this->unpackPacketId(substr($body, 0, 2));
                    $this->logger->debug('<< PUBACK', ['packetId' => $this->lastPubAck]);
                } else {
                    $this->logger->debug('<< PUBACK (invalid length)', ['len' => $rl]);
                }

                return true;
            case PacketType::PUBREL->value:
                // PUBREL acknowledges receipt of PUBREC; respond with PUBCOMP and deliver stored message (QoS2 Rx)
                if ($rl >= 2) {
                    $pid     = $this->unpackPacketId(substr($body, 0, 2));
                    $pubcomp = \chr(PacketType::PUBCOMP->value << 4).\chr(2).pack('n', $pid);
                    $this->logger->debug('>> PUBCOMP', ['packetId' => $pid]);
                    $this->transport->write($pubcomp);
                    $this->touchActivity();
                    if (isset($this->qos2InboundPending[$pid])) {
                        $msg = $this->qos2InboundPending[$pid];
                        unset($this->qos2InboundPending[$pid]);
                        // Now deliver exactly once (subject to client-side filters)
                        $this->logger->debug('<< PUBLISH (QoS2 complete)', ['topic' => $msg->topic, 'packetId' => $pid]);
                        $this->deliverIfMatches($msg);
                    }
                } else {
                    $this->logger->debug('<< PUBREL (invalid length)', ['len' => $rl]);
                }

                return true;
            case PacketType::PUBREC->value:
                // Tx side: upon PUBREC, respond with PUBREL and note packet id
                if ($rl >= 2) {
                    $pid = $this->unpackPacketId(substr($body, 0, 2));
                    // Send PUBREL (flags 0x02)
                    $pubrel = \chr((PacketType::PUBREL->value << 4) | 0x02).\chr(2).pack('n', $pid);
                    $this->logger->debug('>> PUBREL', ['packetId' => $pid]);
                    $this->transport->write($pubrel);
                    $this->touchActivity();
                } else {
                    $this->logger->debug('<< PUBREC (invalid length)', ['len' => $rl]);
                }

                return true;
            case PacketType::PUBCOMP->value:
                // Tx side final ack for QoS2
                if ($rl >= 2) {
                    $this->lastPubComp = $this->unpackPacketId(substr($body, 0, 2));
                    $this->logger->debug('<< PUBCOMP', ['packetId' => $this->lastPubComp]);
                } else {
                    $this->logger->debug('<< PUBCOMP (invalid length)', ['len' => $rl]);
                }

                return true;
            case PacketType::DISCONNECT->value:
                // Inbound DISCONNECT: log and close transport. For MQTT 5, may include reason and properties; we ignore details here.
                $reason = null;
                if ($rl >= 1) {
                    $reason = \ord($body[0]);
                }
                $this->logger->info('<< DISCONNECT', ['reasonCode' => $reason]);
                $this->transport->close();
                if ($this->options->autoReconnect) {
                    // Keep running; loopOnce will try to reconnect on the next iteration
                    $this->shouldStop = false;
                    $this->logger->info('Will attempt auto-reconnect');
                } else {
                    $this->shouldStop = true;
                }

                return true;
            default:
                // ignore others for MVP
                $this->logger->debug('<< IGNORED', ['type' => $type, 'bytes' => $rl, 'preview' => $this->hexPreview($b0.$varBytes.$body)]);

                return true;
        }
    }

    public function tick(): bool
    {
        return $this->loopOnce(0.0);
    }

    public function stop(): void
    {
        $this->shouldStop = true;
    }

    public function awaitMessage(?float $timeoutSec = null): ?InboundMessage
    {
        if (! $this->inbound->isEmpty()) {
            /** @var InboundMessage $m */
            $m = $this->inbound->dequeue();

            return $m;
        }

        if ($timeoutSec === 0.0) {
            return null;
        }

        if ($timeoutSec === null) {
            // Block until we get something
            for (; ;) {
                // @phpstan-ignore-next-line - queue state may change after loopOnce()
                if (! $this->inbound->isEmpty()) {
                    /** @var InboundMessage $m */
                    $m = $this->inbound->dequeue();

                    return $m;
                }
                $this->loopOnce(null);
            }
        }

        $deadline = microtime(true) + $timeoutSec;
        for (; ;) {
            // @phpstan-ignore-next-line - queue state may change after loopOnce()
            if (! $this->inbound->isEmpty()) {
                /** @var InboundMessage $m */
                $m = $this->inbound->dequeue();

                return $m;
            }

            $left = $deadline - microtime(true);
            if ($left <= 0) {
                return null;
            }

            $this->loopOnce($left);
        }
    }

    public function onMessage(callable $handler): void
    {
        $this->messageHandler = $handler;
    }

    public function run(callable $onMessage, ?float $idleSleep = 0.01): void
    {
        $this->onMessage($onMessage);
        for (; ;) {
            if ($this->shouldStop) {
                break;
            }
            if ($this->loopOnce(0.2) === false) {
                if ($idleSleep !== null && $idleSleep > 0) {
                    usleep((int) floor($idleSleep * 1_000_000));
                }
            }
        }
    }

    /**
     * @return \Generator<int, InboundMessage, mixed, void>
     */
    public function messages(?float $timeoutSec = 0.2): \Generator
    {
        for (; ;) {
            if ($this->shouldStop) {
                break;
            }
            $msg = $this->awaitMessage($timeoutSec);
            if ($msg instanceof InboundMessage) {
                yield $msg;
            }
        }
    }

    /**
     * @param  list<array{filter:string,qos:int}>  $filters
     */
    private function recordSubscriptionsFromFilters(array $filters, ?SubscribeOptions $options): void
    {
        foreach ($filters as $f) {
            $filter = $f['filter'];
            if ($filter === '') {
                continue;
            }
            $qos = $f['qos'];
            if ($qos < 0) {
                $qos = 0;
            }
            if ($qos > 2) {
                $qos = 2;
            }
            $this->subscriptions[$filter] = ['qos' => $qos, 'options' => $options];
        }
    }

    /**
     * @param  non-empty-list<string>  $filters
     */
    private function removeSubscriptions(array $filters): void
    {
        foreach ($filters as $filter) {
            $f = (string) $filter;
            unset($this->subscriptions[$f]);
        }
    }

    private function attemptReconnect(): bool
    {
        if (! $this->options->autoReconnect) {
            return false;
        }
        if ($this->reconnectAttempts >= $this->options->reconnectMaxAttempts) {
            $this->logger->warning('Auto-reconnect: max attempts reached; stopping');
            $this->shouldStop = true;

            return false;
        }

        $delay = $this->computeBackoffDelay();
        if ($delay > 0) {
            usleep((int) floor($delay * 1_000_000));
        }

        try {
            $this->logger->info('Attempting reconnect', ['attempt' => $this->reconnectAttempts + 1]);
            $this->connect();
            $this->reconnectAttempts = 0;
            // Resubscribe
            if ($this->subscriptions !== []) {
                $this->isResubscribing = true;
                foreach ($this->subscriptions as $topic => $s) {
                    $this->subscribeWith([
                        ['filter' => $topic, 'qos' => $s['qos']],
                    ], $s['options']);
                }
                $this->isResubscribing = false;
            }

            return true;
        } catch (\Throwable $e) {
            $this->reconnectAttempts++;
            $this->logger->warning('Reconnect failed', ['error' => $e->getMessage(), 'attempts' => $this->reconnectAttempts]);

            return false;
        }
    }

    private function computeBackoffDelay(): float
    {
        $attempt = $this->reconnectAttempts;
        $base    = $this->options->reconnectBaseDelay;
        $max     = $this->options->reconnectMaxDelay;
        $delay   = $base * (2 ** max(0, $attempt));
        if ($delay > $max) {
            $delay = $max;
        }
        $jitter = $this->options->reconnectJitter;
        if ($jitter > 0) {
            $r     = mt_rand() / max(1, mt_getrandmax());
            $delta = (2 * $r - 1) * $jitter; // [-jitter, +jitter]
            $delay *= (1.0 + $delta);
        }
        if ($delay < 0) {
            $delay = 0.0;
        }

        return $delay;
    }

    private function hexPreview(string $bytes): string
    {
        $s   = substr($bytes, 0, 64);
        $hex = strtoupper(bin2hex($s));

        return trim(chunk_split($hex, 2, ' ')).(\strlen($bytes) > 64 ? ' …' : '');
    }

    private function unpackPacketId(string $twoBytes): int
    {
        /** @var array{pid:int}|false $arr */
        $arr = unpack('npid', $twoBytes);
        if (\is_array($arr)) {
            return $arr['pid'];
        }

        return 0;
    }

    private function deliverIfMatches(InboundMessage $msg): void
    {
        $filters = $this->options->messageFilters;
        if ($filters === [] || $this->topicMatchesAny($msg->topic, $filters)) {
            $this->inbound->enqueue($msg);
            if ($this->metrics) {
                $this->metrics->increment('messages_delivered', 1.0, ['qos' => $msg->qos->value, 'retain' => $msg->retain ? 1 : 0]);
                $this->metrics->size('message_payload_bytes', \strlen($msg->payload), ['dir' => 'in', 'qos' => $msg->qos->value]);
            }
            // PSR-14: emit MessageReceived when the message is accepted for delivery
            $this->dispatch(new EvMessageReceived($msg));
            if ($this->messageHandler) {
                ($this->messageHandler)($msg);
            }
        } else {
            $this->logger->debug('Message filtered out by client filters', ['topic' => $msg->topic]);
        }
    }

    /**
     * @param  list<string>  $filters
     */
    private function topicMatchesAny(string $topic, array $filters): bool
    {
        return array_any($filters, fn($filter) => $this->topicMatchesFilter($topic, (string)$filter));

    }

    private function topicMatchesFilter(string $topic, string $filter): bool
    {
        if ($filter === '#') {
            return true;
        }
        $tLevels = explode('/', $topic);
        $fLevels = explode('/', $filter);
        $ti      = 0;
        $fi      = 0;
        $tn      = \count($tLevels);
        $fn      = \count($fLevels);
        while ($fi < $fn) {
            $f = $fLevels[$fi];
            if ($f === '#') {
                // multi-level must be last
                return $fi === $fn - 1;
            }
            if ($ti >= $tn) {
                return false;
            }
            $t = $tLevels[$ti];
            if ($f !== '+' && $f !== $t) {
                return false;
            }
            $fi++;
            $ti++;
        }

        return $ti === $tn && $fi === $fn;
    }
}
