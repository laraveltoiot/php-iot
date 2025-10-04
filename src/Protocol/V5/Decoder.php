<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Protocol\V5;

use ScienceStories\Mqtt\Client\InboundMessage;
use ScienceStories\Mqtt\Contract\DecoderInterface;
use ScienceStories\Mqtt\Exception\ProtocolError;
use ScienceStories\Mqtt\Protocol\Packet\ConnAck; // reuse DTO
use ScienceStories\Mqtt\Protocol\Packet\PubAck;
use ScienceStories\Mqtt\Protocol\Packet\PubComp;
use ScienceStories\Mqtt\Protocol\Packet\PubRec;
use ScienceStories\Mqtt\Protocol\Packet\PubRel;
use ScienceStories\Mqtt\Protocol\Packet\SubAck;
use ScienceStories\Mqtt\Protocol\Packet\UnsubAck;
use ScienceStories\Mqtt\Protocol\QoS;
use ScienceStories\Mqtt\Util\Bytes;

/**
 * Decoder for MQTT 5.0 packets.
 *
 * Decodes packets according to the MQTT 5.0 specification (protocol level 5).
 * - Supports properties field with extensive metadata
 * - Reason codes instead of simple return codes
 * - Enhanced error information with reason strings and user properties
 */
final class Decoder implements DecoderInterface
{
    /**
     * Decode bytes of a CONNACK packet body for v5.
     * Body layout (v5):
     *  - byte1: Acknowledge Flags (bit0 = session present)
     *  - byte2: Reason Code
     *  - properties: VarInt length + properties (ignored in MVP)
     */
    public function decodeConnAck(string $packetBody): ConnAck
    {
        if (\strlen($packetBody) < 2) {
            throw new ProtocolError('CONNACK too short');
        }

        $ackFlags   = \ord($packetBody[0]);
        $reasonCode = \ord($packetBody[1]);

        // Properties
        $offset   = 2;
        $propsMap = null;
        if (isset($packetBody[$offset])) {
            $rest     = substr($packetBody, $offset);
            $consumed = 0;
            $len      = Bytes::decodeVarInt($rest, $consumed);
            if ($consumed + $offset + $len > \strlen($packetBody)) {
                throw new ProtocolError('Malformed CONNACK: properties truncated');
            }
            $propsRaw = substr($packetBody, $offset + $consumed, $len);
            $propsMap = $this->parseConnAckProperties($propsRaw);
        }

        $sessionPresent = (bool) ($ackFlags & 0x01);

        return new ConnAck($sessionPresent, $reasonCode, $propsMap);
    }

    /**
     * Parse a subset of MQTT 5 CONNACK properties into an associative array.
     * Recognized keys:
     *  - assigned_client_identifier (0x12) string
     *  - server_keep_alive (0x13) u16
     *  - receive_maximum (0x21) u16
     *  - topic_alias_maximum (0x22) u16
     *  - maximum_qos (0x24) byte
     *  - retain_available (0x25) byte
     *  - maximum_packet_size (0x27) u32
     *  - wildcard_subscription_available (0x28) byte
     *  - subscription_identifier_available (0x29) byte
     *  - shared_subscription_available (0x2A) byte
     *  - response_information (0x1A) string
     *  - reason_string (0x1F) string
     *  - server_reference (0x1C) string
     *  - user_properties (0x26) map<string,string>
     *
     * @return array<string, mixed>
     */
    private function parseConnAckProperties(string $props): array
    {
        $out = [];
        $i   = 0;
        $n   = \strlen($props);
        while ($i < $n) {
            $id = \ord($props[$i++]);
            switch ($id) {
                case 0x12: // Assigned Client Identifier
                    $off                               = $i;
                    $out['assigned_client_identifier'] = Bytes::decodeString($props, $off);
                    $i                                 = $off;
                    break;
                case 0x13: // Server Keep Alive (u16)
                    if ($i + 2 > $n) {
                        $i = $n;
                        break;
                    }
                    $out['server_keep_alive'] = unpack('n', substr($props, $i, 2))[1] ?? 0;
                    $i += 2;
                    break;
                case 0x21: // Receive Maximum (u16)
                    if ($i + 2 > $n) {
                        $i = $n;
                        break;
                    }
                    $out['receive_maximum'] = unpack('n', substr($props, $i, 2))[1] ?? 0;
                    $i += 2;
                    break;
                case 0x22: // Topic Alias Maximum (u16)
                    if ($i + 2 > $n) {
                        $i = $n;
                        break;
                    }
                    $out['topic_alias_maximum'] = unpack('n', substr($props, $i, 2))[1] ?? 0;
                    $i += 2;
                    break;
                case 0x24: // Maximum QoS (byte)
                    if ($i >= $n) {
                        $i = $n;
                        break;
                    }
                    $out['maximum_qos'] = \ord($props[$i++]);
                    break;
                case 0x25: // Retain Available (byte)
                    if ($i >= $n) {
                        $i = $n;
                        break;
                    }
                    $out['retain_available'] = \ord($props[$i++]);
                    break;
                case 0x27: // Maximum Packet Size (u32)
                    if ($i + 4 > $n) {
                        $i = $n;
                        break;
                    }
                    $out['maximum_packet_size'] = unpack('N', substr($props, $i, 4))[1] ?? 0;
                    $i += 4;
                    break;
                case 0x28: // Wildcard Subscription Available (byte)
                    if ($i >= $n) {
                        $i = $n;
                        break;
                    }
                    $out['wildcard_subscription_available'] = \ord($props[$i++]);
                    break;
                case 0x29: // Subscription Identifier Available (byte)
                    if ($i >= $n) {
                        $i = $n;
                        break;
                    }
                    $out['subscription_identifier_available'] = \ord($props[$i++]);
                    break;
                case 0x2A: // Shared Subscription Available (byte)
                    if ($i >= $n) {
                        $i = $n;
                        break;
                    }
                    $out['shared_subscription_available'] = \ord($props[$i++]);
                    break;
                case 0x1A: // Response Information (string)
                    $off                         = $i;
                    $out['response_information'] = Bytes::decodeString($props, $off);
                    $i                           = $off;
                    break;
                case 0x1F: // Reason String (string)
                    $off                  = $i;
                    $out['reason_string'] = Bytes::decodeString($props, $off);
                    $i                    = $off;
                    break;
                case 0x1C: // Server Reference (string)
                    $off                     = $i;
                    $out['server_reference'] = Bytes::decodeString($props, $off);
                    $i                       = $off;
                    break;
                case 0x26: // User Property (key,value)
                    $off                        = $i;
                    $k                          = Bytes::decodeString($props, $off);
                    $v                          = Bytes::decodeString($props, $off);
                    $i                          = $off;
                    $out['user_properties'][$k] = $v;
                    break;
                default:
                    // Unknown property: stop parsing for safety
                    $i = $n;
                    break;
            }
        }

        return $out;
    }

    /**
     * Parse MQTT 5.0 SUBACK properties.
     * Recognized keys:
     *  - reason_string (0x1F): string
     *  - user_properties (0x26): array<string,string>
     *
     * @return array<string, mixed>
     */
    private function parseSubAckProperties(string $props): array
    {
        $out = [];
        $i   = 0;
        $n   = \strlen($props);
        while ($i < $n) {
            $id = \ord($props[$i++]);
            switch ($id) {
                case 0x1F: // Reason String (string)
                    $off                  = $i;
                    $out['reason_string'] = Bytes::decodeString($props, $off);
                    $i                    = $off;
                    break;
                case 0x26: // User Property (key,value)
                    $off                        = $i;
                    $k                          = Bytes::decodeString($props, $off);
                    $v                          = Bytes::decodeString($props, $off);
                    $i                          = $off;
                    $out['user_properties'][$k] = $v;
                    break;
                default:
                    // Unknown property: stop parsing for safety
                    $i = $n;
                    break;
            }
        }

        return $out;
    }

    /**
     * Decode SUBACK: packetId (2) + properties(varint+props) + payload reason codes.
     *
     * MQTT 5.0 SUBACK structure:
     * - Packet Identifier (2 bytes)
     * - Properties (varint length + properties)
     *   * reason_string (0x1F): string
     *   * user_properties (0x26): key-value pairs
     * - Reason codes (1 byte per subscription)
     *   * 0x00-0x02: Granted QoS 0, 1, or 2
     *   * 0x80+: Various failure codes
     */
    public function decodeSubAck(string $packetBody): SubAck
    {
        if (\strlen($packetBody) < 4) { // minimal: id(2)+props_len(1)+empty
            throw new ProtocolError('SUBACK too short');
        }
        $arr = unpack('n', substr($packetBody, 0, 2));
        if ($arr === false || ! isset($arr[1]) || ! \is_int($arr[1])) {
            throw new ProtocolError('SUBACK malformed packet id');
        }
        $pid    = (int) $arr[1];
        $offset = 2;
        // Properties
        $rest     = substr($packetBody, $offset);
        $consumed = 0;
        $propLen  = Bytes::decodeVarInt($rest, $consumed);
        $offset += $consumed;
        if ($offset + $propLen > \strlen($packetBody)) {
            throw new ProtocolError('SUBACK properties truncated');
        }
        $propsRaw = substr($packetBody, $offset, $propLen);
        $offset += $propLen;
        $propsMap = $propLen > 0 ? $this->parseSubAckProperties($propsRaw) : null;

        // Reason codes
        $codes = [];
        for ($i = $offset, $n = \strlen($packetBody); $i < $n; $i++) {
            $codes[] = \ord($packetBody[$i]);
        }

        return new SubAck($pid, $codes, $propsMap);
    }

    /**
     * Decode inbound PUBLISH with v5 Properties between topic and payload.
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

        // Properties
        $rest     = substr($packetBody, $offset);
        $consumed = 0;
        $propLen  = Bytes::decodeVarInt($rest, $consumed);
        $offset += $consumed;
        if ($offset + $propLen > \strlen($packetBody)) {
            throw new ProtocolError('PUBLISH properties truncated');
        }
        $propsRaw = substr($packetBody, $offset, $propLen);
        $offset += $propLen;

        $properties = $this->parsePublishProperties($propsRaw);
        $payload    = substr($packetBody, $offset);

        return new InboundMessage(
            topic: $topic,
            payload: $payload,
            qos: $qos,
            retain: $retain,
            dup: $dup,
            packetId: $packetId,
            properties: $properties,
        );
    }

    /**
     * Decode UNSUBACK: packetId + properties + reason codes.
     *
     * MQTT 5.0 UNSUBACK structure:
     * - Packet Identifier (2 bytes)
     * - Properties (varint length + properties)
     *   * reason_string (0x1F): string
     *   * user_properties (0x26): key-value pairs
     * - Reason codes (1 byte per unsubscribed topic filter)
     *   * 0x00: Success
     *   * 0x11: No subscription existed
     *   * 0x80+: Various failure codes
     */
    public function decodeUnsubAck(string $packetBody): UnsubAck
    {
        if (\strlen($packetBody) < 4) {
            throw new ProtocolError('UNSUBACK too short');
        }
        $arr = unpack('n', substr($packetBody, 0, 2));
        if ($arr === false || ! isset($arr[1]) || ! \is_int($arr[1])) {
            throw new ProtocolError('UNSUBACK malformed packet id');
        }
        $pid    = (int) $arr[1];
        $offset = 2;

        // Properties
        $rest     = substr($packetBody, $offset);
        $consumed = 0;
        $propLen  = Bytes::decodeVarInt($rest, $consumed);
        $offset += $consumed;
        if ($offset + $propLen > \strlen($packetBody)) {
            throw new ProtocolError('UNSUBACK properties truncated');
        }
        $propsRaw = substr($packetBody, $offset, $propLen);
        $offset += $propLen;
        $propsMap = $propLen > 0 ? $this->parseUnsubAckProperties($propsRaw) : null;

        // Reason codes
        $codes = [];
        for ($i = $offset, $n = \strlen($packetBody); $i < $n; $i++) {
            $codes[] = \ord($packetBody[$i]);
        }

        return new UnsubAck($pid, $codes, $propsMap);
    }

    /**
     * Parse MQTT 5.0 UNSUBACK properties.
     * Recognized keys:
     *  - reason_string (0x1F): string
     *  - user_properties (0x26): array<string,string>
     *
     * @return array<string, mixed>
     */
    private function parseUnsubAckProperties(string $props): array
    {
        $out = [];
        $i   = 0;
        $n   = \strlen($props);
        while ($i < $n) {
            $id = \ord($props[$i++]);
            switch ($id) {
                case 0x1F: // Reason String (string)
                    $off                  = $i;
                    $out['reason_string'] = Bytes::decodeString($props, $off);
                    $i                    = $off;
                    break;
                case 0x26: // User Property (key,value)
                    $off                        = $i;
                    $k                          = Bytes::decodeString($props, $off);
                    $v                          = Bytes::decodeString($props, $off);
                    $i                          = $off;
                    $out['user_properties'][$k] = $v;
                    break;
                default:
                    // Unknown property: stop parsing for safety
                    $i = $n;
                    break;
            }
        }

        return $out;
    }

    /**
     * Parse a subset of v5 PUBLISH properties into an associative array.
     *
     * @return array<string,mixed>
     */
    private function parsePublishProperties(string $props): array
    {
        $out = [];
        $i   = 0;
        $len = \strlen($props);
        while ($i < $len) {
            $id = \ord($props[$i++]);
            switch ($id) {
                case 0x01: // payload_format_indicator (byte)
                    if ($i >= $len) {
                        break 2;
                    }
                    $out['payload_format_indicator'] = \ord($props[$i++]);
                    break;
                case 0x02: // message_expiry_interval (u32)
                    if ($i + 4 > $len) {
                        break 2;
                    }
                    $out['message_expiry_interval'] = unpack('N', substr($props, $i, 4))[1] ?? 0;
                    $i += 4;
                    break;
                case 0x03: // content_type (string)
                    $offset              = $i;
                    $out['content_type'] = Bytes::decodeString($props, $offset);
                    $i                   = $offset;
                    break;
                case 0x08: // response_topic (string)
                    $offset                = $i;
                    $out['response_topic'] = Bytes::decodeString($props, $offset);
                    $i                     = $offset;
                    break;
                case 0x09: // correlation_data (binary)
                    $offset                  = $i;
                    $out['correlation_data'] = Bytes::decodeString($props, $offset);
                    $i                       = $offset;
                    break;
                case 0x23: // topic_alias (u16)
                    if ($i + 2 > $len) {
                        break 2;
                    }
                    $out['topic_alias'] = unpack('n', substr($props, $i, 2))[1] ?? 0;
                    $i += 2;
                    break;
                case 0x26: // user_property (key,value)
                    $offset                       = $i;
                    $key                          = Bytes::decodeString($props, $offset);
                    $val                          = Bytes::decodeString($props, $offset);
                    $i                            = $offset;
                    $out['user_properties'][$key] = $val;
                    break;
                default:
                    // Unknown property id: break loop for safety
                    $i = $len; // stop parsing
                    break;
            }
        }

        return $out;
    }

    /**
     * Parse MQTT 5.0 QoS acknowledgment packet properties (PUBACK, PUBREC, PUBREL, PUBCOMP).
     * Recognized keys:
     *  - reason_string (0x1F): string
     *  - user_properties (0x26): array<string,string>
     *
     * @return array<string, mixed>
     */
    private function parseQoSAckProperties(string $props): array
    {
        $out = [];
        $i   = 0;
        $n   = \strlen($props);
        while ($i < $n) {
            $id = \ord($props[$i++]);
            switch ($id) {
                case 0x1F: // Reason String (string)
                    $off                  = $i;
                    $out['reason_string'] = Bytes::decodeString($props, $off);
                    $i                    = $off;
                    break;
                case 0x26: // User Property (key,value)
                    $off                        = $i;
                    $k                          = Bytes::decodeString($props, $off);
                    $v                          = Bytes::decodeString($props, $off);
                    $i                          = $off;
                    $out['user_properties'][$k] = $v;
                    break;
                default:
                    // Unknown property: stop parsing for safety
                    $i = $n;
                    break;
            }
        }

        return $out;
    }

    /**
     * Decode PUBACK body for MQTT 5.0.
     *
     * Structure:
     * - Packet Identifier (2 bytes)
     * - Reason Code (1 byte) - optional, defaults to 0x00 if omitted
     * - Properties (varint length + data) - optional
     */
    public function decodePubAck(string $packetBody): PubAck
    {
        if (\strlen($packetBody) < 2) {
            throw new ProtocolError('PUBACK too short');
        }
        $arr = unpack('n', substr($packetBody, 0, 2));
        if ($arr === false || ! isset($arr[1]) || ! \is_int($arr[1])) {
            throw new ProtocolError('PUBACK malformed packet id');
        }
        $pid = (int) $arr[1];

        // If packet is only 2 bytes, reason code and properties are omitted (success)
        if (\strlen($packetBody) === 2) {
            return new PubAck($pid, 0, null);
        }

        // Reason code (1 byte)
        $reasonCode = \ord($packetBody[2]);
        $offset     = 3;

        // Properties
        $propsMap = null;
        if ($offset < \strlen($packetBody)) {
            $rest     = substr($packetBody, $offset);
            $consumed = 0;
            $propLen  = Bytes::decodeVarInt($rest, $consumed);
            $offset += $consumed;
            if ($offset + $propLen > \strlen($packetBody)) {
                throw new ProtocolError('PUBACK properties truncated');
            }
            $propsRaw = substr($packetBody, $offset, $propLen);
            $propsMap = $propLen > 0 ? $this->parseQoSAckProperties($propsRaw) : null;
        }

        return new PubAck($pid, $reasonCode, $propsMap);
    }

    /**
     * Decode PUBREC body for MQTT 5.0.
     *
     * Structure:
     * - Packet Identifier (2 bytes)
     * - Reason Code (1 byte) - optional, defaults to 0x00 if omitted
     * - Properties (varint length + data) - optional
     */
    public function decodePubRec(string $packetBody): PubRec
    {
        if (\strlen($packetBody) < 2) {
            throw new ProtocolError('PUBREC too short');
        }
        $arr = unpack('n', substr($packetBody, 0, 2));
        if ($arr === false || ! isset($arr[1]) || ! \is_int($arr[1])) {
            throw new ProtocolError('PUBREC malformed packet id');
        }
        $pid = (int) $arr[1];

        // If packet is only 2 bytes, reason code and properties are omitted (success)
        if (\strlen($packetBody) === 2) {
            return new PubRec($pid, 0, null);
        }

        // Reason code (1 byte)
        $reasonCode = \ord($packetBody[2]);
        $offset     = 3;

        // Properties
        $propsMap = null;
        if ($offset < \strlen($packetBody)) {
            $rest     = substr($packetBody, $offset);
            $consumed = 0;
            $propLen  = Bytes::decodeVarInt($rest, $consumed);
            $offset += $consumed;
            if ($offset + $propLen > \strlen($packetBody)) {
                throw new ProtocolError('PUBREC properties truncated');
            }
            $propsRaw = substr($packetBody, $offset, $propLen);
            $propsMap = $propLen > 0 ? $this->parseQoSAckProperties($propsRaw) : null;
        }

        return new PubRec($pid, $reasonCode, $propsMap);
    }

    /**
     * Decode PUBREL body for MQTT 5.0.
     *
     * Structure:
     * - Packet Identifier (2 bytes)
     * - Reason Code (1 byte) - optional, defaults to 0x00 if omitted
     * - Properties (varint length + data) - optional
     */
    public function decodePubRel(string $packetBody): PubRel
    {
        if (\strlen($packetBody) < 2) {
            throw new ProtocolError('PUBREL too short');
        }
        $arr = unpack('n', substr($packetBody, 0, 2));
        if ($arr === false || ! isset($arr[1]) || ! \is_int($arr[1])) {
            throw new ProtocolError('PUBREL malformed packet id');
        }
        $pid = (int) $arr[1];

        // If packet is only 2 bytes, reason code and properties are omitted (success)
        if (\strlen($packetBody) === 2) {
            return new PubRel($pid, 0, null);
        }

        // Reason code (1 byte)
        $reasonCode = \ord($packetBody[2]);
        $offset     = 3;

        // Properties
        $propsMap = null;
        if ($offset < \strlen($packetBody)) {
            $rest     = substr($packetBody, $offset);
            $consumed = 0;
            $propLen  = Bytes::decodeVarInt($rest, $consumed);
            $offset += $consumed;
            if ($offset + $propLen > \strlen($packetBody)) {
                throw new ProtocolError('PUBREL properties truncated');
            }
            $propsRaw = substr($packetBody, $offset, $propLen);
            $propsMap = $propLen > 0 ? $this->parseQoSAckProperties($propsRaw) : null;
        }

        return new PubRel($pid, $reasonCode, $propsMap);
    }

    /**
     * Decode PUBCOMP body for MQTT 5.0.
     *
     * Structure:
     * - Packet Identifier (2 bytes)
     * - Reason Code (1 byte) - optional, defaults to 0x00 if omitted
     * - Properties (varint length + data) - optional
     */
    public function decodePubComp(string $packetBody): PubComp
    {
        if (\strlen($packetBody) < 2) {
            throw new ProtocolError('PUBCOMP too short');
        }
        $arr = unpack('n', substr($packetBody, 0, 2));
        if ($arr === false || ! isset($arr[1]) || ! \is_int($arr[1])) {
            throw new ProtocolError('PUBCOMP malformed packet id');
        }
        $pid = (int) $arr[1];

        // If packet is only 2 bytes, reason code and properties are omitted (success)
        if (\strlen($packetBody) === 2) {
            return new PubComp($pid, 0, null);
        }

        // Reason code (1 byte)
        $reasonCode = \ord($packetBody[2]);
        $offset     = 3;

        // Properties
        $propsMap = null;
        if ($offset < \strlen($packetBody)) {
            $rest     = substr($packetBody, $offset);
            $consumed = 0;
            $propLen  = Bytes::decodeVarInt($rest, $consumed);
            $offset += $consumed;
            if ($offset + $propLen > \strlen($packetBody)) {
                throw new ProtocolError('PUBCOMP properties truncated');
            }
            $propsRaw = substr($packetBody, $offset, $propLen);
            $propsMap = $propLen > 0 ? $this->parseQoSAckProperties($propsRaw) : null;
        }

        return new PubComp($pid, $reasonCode, $propsMap);
    }
}
