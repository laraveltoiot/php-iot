<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Protocol\V311;

use ScienceStories\Mqtt\Client\InboundMessage;
use ScienceStories\Mqtt\Contract\DecoderInterface;
use ScienceStories\Mqtt\Exception\ProtocolError;
use ScienceStories\Mqtt\Protocol\Packet\ConnAck;
use ScienceStories\Mqtt\Protocol\Packet\SubAck;
use ScienceStories\Mqtt\Protocol\Packet\UnsubAck;
use ScienceStories\Mqtt\Protocol\QoS;
use ScienceStories\Mqtt\Util\Bytes;

/**
 * Decoder for MQTT 3.1.1 packets.
 *
 * Decodes packets according to the MQTT 3.1.1 specification (protocol level 4).
 * - No properties field (properties are MQTT 5.0 only)
 * - Simple return codes (0 = accepted, 1-5 = various error conditions)
 */
final class Decoder implements DecoderInterface
{
    /**
     * Decode bytes of a CONNACK packet body (variable header + payload).
     * Caller should pass only the remaining bytes after fixed header.
     */
    public function decodeConnAck(string $packetBody): ConnAck
    {
        if (\strlen($packetBody) < 2) {
            throw new ProtocolError('CONNACK too short');
        }

        $ackFlags   = \ord($packetBody[0]);
        $returnCode = \ord($packetBody[1]);

        $sessionPresent = (bool) ($ackFlags & 0x01);

        return new ConnAck($sessionPresent, $returnCode);
    }

    /**
     * Decode SUBACK body: packetId (2) + payload (list of return codes).
     *
     * MQTT 3.1.1 SUBACK structure:
     * - Packet Identifier (2 bytes)
     * - Return codes (1 byte per subscription)
     *   * 0x00-0x02: Success with granted QoS 0, 1, or 2
     *   * 0x80: Failure
     */
    public function decodeSubAck(string $packetBody): SubAck
    {
        if (\strlen($packetBody) < 2) {
            throw new ProtocolError('SUBACK too short');
        }
        $packetId = unpack('n', substr($packetBody, 0, 2));
        if ($packetId === false || ! isset($packetId[1]) || ! \is_int($packetId[1])) {
            throw new ProtocolError('SUBACK malformed packet id');
        }
        $pid   = (int) $packetId[1];
        $codes = [];
        $rest  = substr($packetBody, 2);
        $len   = \strlen($rest);
        for ($i = 0; $i < $len; $i++) {
            $codes[] = \ord($rest[$i]);
        }

        return new SubAck($pid, $codes);
    }

    /**
     * Decode inbound PUBLISH packet body (topic[,packetId]) + payload.
     */
    public function decodePublish(int $flags, string $packetBody): InboundMessage
    {
        $dup    = (bool) (($flags & 0x08) >> 3);
        $qosVal = ($flags & 0x06) >> 1;
        $retain = (bool) ($flags & 0x01);
        $qos    = QoS::from($qosVal);

        $offset   = 0;
        $topic    = Bytes::decodeString($packetBody, $offset);
        $packetId = null;
        if ($qosVal > 0) {
            if ($offset + 2 > \strlen($packetBody)) {
                throw new ProtocolError('PUBLISH missing packet id');
            }
            $arr = unpack('n', substr($packetBody, $offset, 2));
            if ($arr === false || ! isset($arr[1]) || ! \is_int($arr[1])) {
                throw new ProtocolError('PUBLISH invalid packet id');
            }
            $packetId = (int) $arr[1];
            $offset += 2;
        }

        $payload = substr($packetBody, $offset);

        return new InboundMessage(
            topic: $topic,
            payload: $payload,
            qos: $qos,
            retain: $retain,
            dup: $dup,
            packetId: $packetId,
            properties: null,
        );
    }

    /**
     * Decode UNSUBACK body: packetId only in v3.1.1.
     *
     * MQTT 3.1.1 UNSUBACK structure:
     * - Packet Identifier (2 bytes)
     * - No reason codes (acknowledgment is implicit success)
     * - No properties
     */
    public function decodeUnsubAck(string $packetBody): UnsubAck
    {
        if (\strlen($packetBody) < 2) {
            throw new ProtocolError('UNSUBACK too short');
        }
        $arr = unpack('n', substr($packetBody, 0, 2));
        if ($arr === false || ! isset($arr[1]) || ! \is_int($arr[1])) {
            throw new ProtocolError('UNSUBACK malformed packet id');
        }

        $pid = (int) $arr[1];

        return new UnsubAck($pid);
    }
}
