<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Protocol\V311;

use ScienceStories\Mqtt\Client\SubscribeOptions;
use ScienceStories\Mqtt\Contract\EncoderInterface;
use ScienceStories\Mqtt\Protocol\Packet\Connect;
use ScienceStories\Mqtt\Protocol\Packet\PacketType;
use ScienceStories\Mqtt\Protocol\Packet\Publish;
use ScienceStories\Mqtt\Util\Bytes;

/**
 * Encoder for MQTT 3.1.1 packets.
 *
 * Encodes packets according to the MQTT 3.1.1 specification (protocol level 4).
 * - No properties field (properties are MQTT 5.0 only)
 * - Clean Session flag instead of Clean Start
 * - Maximum packet size is implementation-dependent
 */
final class Encoder implements EncoderInterface
{
    /**
     * Build a CONNECT packet byte for MQTT 3.1.1.
     */
    public function encodeConnect(Connect $pkt): string
    {
        // Variable header
        $vh = Bytes::encodeString('MQTT'); // Protocol Name
        $vh .= \chr(4);                    // Protocol Level = 4 (MQTT 3.1.1)

        // Connect Flags
        $flags = 0;
        // Clean Session
        if ($pkt->cleanSession) {
            $flags |= 0x02;
        }
        // Username / Password
        $hasUser = $pkt->username !== null;
        $hasPass = $pkt->password !== null;
        if ($hasUser) {
            $flags |= 0x80;
        }
        if ($hasPass) {
            $flags |= 0x40;
        }
        // Will settings (v3 payload Will Topic + Message precede username/password)
        $hasWill = $pkt->will !== null;
        $will    = $pkt->will;
        if ($hasWill && $will !== null) {
            $flags |= 0x04; // Will Flag
            $q = $will->qos->value & 0x03;
            $flags |= ($q << 3); // Will QoS bits 3-4
            if ($will->retain) {
                $flags |= 0x20; // Will Retain
            }
        }

        $vh .= \chr($flags);
        // Keep Alive (2 bytes)
        $vh .= pack('n', $pkt->keepAlive);

        // Payload
        $payload = Bytes::encodeString($pkt->clientId);
        if ($hasWill && $will !== null) {
            $payload .= Bytes::encodeString($will->topic);
            $payload .= Bytes::encodeString($will->payload);
        }
        if ($hasUser) {
            $username = $pkt->username ?? '';
            $payload .= Bytes::encodeString($username);
        }
        if ($hasPass) {
            $password = $pkt->password ?? '';
            $payload .= Bytes::encodeString($password);
        }

        // Fixed header
        $remaining = \strlen($vh) + \strlen($payload);
        $fixed     = \chr(PacketType::CONNECT->value << 4).Bytes::encodeVarInt($remaining);

        return $fixed.$vh.$payload;
    }

    public function encodePublish(Publish $pkt): string
    {
        $fixedHeader = ($pkt->dup ? 0x08 : 0x00);
        $fixedHeader |= ($pkt->qos->value << 1);
        $fixedHeader |= ($pkt->retain ? 0x01 : 0x00);
        $fixedHeader |= (PacketType::PUBLISH->value << 4);

        $variableHeader = Bytes::encodeString($pkt->topic);
        // For QoS1/2 include Packet Identifier in variable header
        if ($pkt->qos->value > 0) {
            if ($pkt->packetId === null) {
                throw new \LogicException('QoS>0 requires packetId in Publish packet');
            }
            $variableHeader .= pack('n', $pkt->packetId);
        }

        $payload = $pkt->payload;

        $remainingLength = \strlen($variableHeader) + \strlen($payload);

        return \chr($fixedHeader)
            .Bytes::encodeVarInt($remainingLength)
            .$variableHeader
            .$payload;
    }

    /**
     * @param  non-empty-list<array{filter:string,qos:int}>  $filters
     */
    public function encodeSubscribe(array $filters, int $packetId, ?SubscribeOptions $options = null): string
    {
        // Variable header: Packet Identifier
        $vh = pack('n', $packetId);
        // Payload: Topic Filter + Requested QoS (only QoS byte in v3)
        $payload = '';
        foreach ($filters as $f) {
            $filter = (string) $f['filter'];
            $qos    = (int) $f['qos'];
            if ($filter === '') {
                continue;
            }
            if ($qos < 0) {
                $qos = 0;
            }
            if ($qos > 2) {
                $qos = 2;
            }
            $payload .= Bytes::encodeString($filter).\chr($qos);
        }
        $remaining = \strlen($vh) + \strlen($payload);
        $fixed     = \chr((PacketType::SUBSCRIBE->value << 4) | 0x02).Bytes::encodeVarInt($remaining);

        return $fixed.$vh.$payload;
    }

    /**
     * @param  non-empty-list<string>  $filters
     */
    public function encodeUnsubscribe(array $filters, int $packetId): string
    {
        // Variable header: Packet Identifier
        $vh = pack('n', $packetId);
        // Payload: list of topic filters
        $payload = '';
        foreach ($filters as $filter) {
            $f = (string) $filter;
            if ($f === '') {
                continue;
            }
            $payload .= Bytes::encodeString($f);
        }
        $remaining = \strlen($vh) + \strlen($payload);
        // Fixed header: type UNSUBSCRIBE (10) with reserved flags 0b0010
        $fixed = \chr((PacketType::UNSUBSCRIBE->value << 4) | 0x02).Bytes::encodeVarInt($remaining);

        return $fixed.$vh.$payload;
    }
}
