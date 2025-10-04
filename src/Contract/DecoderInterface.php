<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Contract;

use ScienceStories\Mqtt\Client\InboundMessage;
use ScienceStories\Mqtt\Exception\ProtocolError;
use ScienceStories\Mqtt\Protocol\Packet\ConnAck;
use ScienceStories\Mqtt\Protocol\Packet\SubAck;
use ScienceStories\Mqtt\Protocol\Packet\UnsubAck;

/**
 * Contract for MQTT packet decoders.
 *
 * Implementations must decode packets according to their respective MQTT protocol version:
 * - V311\Decoder: MQTT 3.1.1 (protocol level 4)
 * - V5\Decoder: MQTT 5.0 (protocol level 5)
 */
interface DecoderInterface
{
    /**
     * Decode a CONNACK packet from binary format.
     *
     * The CONNACK packet is sent by the broker in response to a CONNECT packet.
     * It contains session present flag, return/reason code, and properties (MQTT 5).
     *
     * @param  string  $packetBody  Binary packet body (variable header + payload, excluding fixed header)
     * @return ConnAck Decoded CONNACK packet data
     * @throws ProtocolError If a packet is malformed
     */
    public function decodeConnAck(string $packetBody): ConnAck;

    /**
     * Decode a SUBACK packet from binary format.
     *
     * The SUBACK packet is sent by the broker in response to a SUBSCRIBE packet.
     * It contains the packet identifier and return codes for each subscription.
     *
     * @param  string  $packetBody  Binary packet body (variable header + payload, excluding fixed header)
     * @return SubAck Decoded SUBACK packet with packet ID, return codes, and properties (MQTT 5.0)
     * @throws ProtocolError If packet is malformed
     */
    public function decodeSubAck(string $packetBody): SubAck;

    /**
     * Decode an inbound PUBLISH packet from binary format.
     *
     * The PUBLISH packet is used to deliver messages to subscribed clients.
     * It contains topic, payload, QoS level, retain flag, and properties (MQTT 5).
     *
     * @param  int  $flags  Fixed header flags (bits 0-3 of first byte)
     * @param  string  $packetBody  Binary packet body (variable header + payload, excluding fixed header)
     * @return InboundMessage Decoded PUBLISH message
     * @throws ProtocolError If packet is malformed
     */
    public function decodePublish(int $flags, string $packetBody): InboundMessage;

    /**
     * Decode an UNSUBACK packet from binary format.
     *
     * The UNSUBACK packet is sent by the broker in response to an UNSUBSCRIBE packet.
     * For MQTT 3.1.1, it contains only the packet identifier (implicit success).
     * For MQTT 5.0, it also includes reason codes for each unsubscription and optional properties.
     *
     * @param  string  $packetBody  Binary packet body (variable header + payload, excluding fixed header)
     * @return UnsubAck Decoded UNSUBACK packet with packet ID, reason codes (MQTT 5.0), and properties (MQTT 5.0)
     * @throws ProtocolError If packet is malformed
     */
    public function decodeUnsubAck(string $packetBody): UnsubAck;
}
