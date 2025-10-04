<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Contract;

use ScienceStories\Mqtt\Client\SubscribeOptions;
use ScienceStories\Mqtt\Protocol\Packet\Connect;
use ScienceStories\Mqtt\Protocol\Packet\Publish;

/**
 * Contract for MQTT packet encoders.
 *
 * Implementations must encode packets according to their respective MQTT protocol version:
 * - V311\Encoder: MQTT 3.1.1 (protocol level 4)
 * - V5\Encoder: MQTT 5.0 (protocol level 5)
 */
interface EncoderInterface
{
    /**
     * Encode a CONNECT packet to binary format.
     *
     * The CONNECT packet is the first packet sent from a client to a broker.
     * It contains client identifier, authentication credentials, will message,
     * and protocol-specific properties (MQTT 5 only).
     *
     * @param  Connect  $pkt  The CONNECT packet data
     * @return string Binary-encoded CONNECT packet ready for transmission
     */
    public function encodeConnect(Connect $pkt): string;

    /**
     * Encode a PUBLISH packet to binary format.
     *
     * The PUBLISHING packet is used to send messages to topics.
     * It includes QoS level, retain flag, and optional properties (MQTT 5).
     *
     * @param  Publish  $pkt  The PUBLISHING packet data
     * @return string Binary-encoded PUBLISH packet ready for transmission
     */
    public function encodePublish(Publish $pkt): string;

    /**
     * Encode a SUBSCRIBE packet to binary format.
     *
     * The SUBSCRIBE packet is used to request subscriptions to one or more topics.
     * Each topic filter includes a requested QoS level.
     *
     * @param  non-empty-list<array{filter:string,qos:int}>  $filters  List of topic filters with QoS
     * @param  int  $packetId  Packet identifier for tracking the request
     * @param  SubscribeOptions|null  $options  Additional subscription options (MQTT 5 only)
     * @return string Binary-encoded SUBSCRIBE packet ready for transmission
     */
    public function encodeSubscribe(array $filters, int $packetId, ?SubscribeOptions $options = null): string;

    /**
     * Encode an UNSUBSCRIBE packet to binary format.
     *
     * The UNSUBSCRIBE packet is used to remove existing subscriptions.
     *
     * @param  non-empty-list<string>  $filters  List of topic filters to unsubscribe from
     * @param  int  $packetId  Packet identifier for tracking the request
     * @return string Binary-encoded UNSUBSCRIBE packet ready for transmission
     */
    public function encodeUnsubscribe(array $filters, int $packetId): string;
}
