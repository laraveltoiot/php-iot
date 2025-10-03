<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Protocol\V311\Packet;

use ScienceStories\Mqtt\Protocol\QoS;

/**
 * MQTT PUBLISH packet model.
 * - Used by both MQTT 3.1.1 and MQTT 5 encoders.
 * - For MQTT 5, optional properties are carried in $properties and encoded by the v5 encoder.
 */
final class Publish
{
    /**
     * @param  array<string, mixed>|null  $properties  MQTT 5 publish properties map. Supported keys:
     *                                                 - payload_format_indicator: 0|1 or bool
     *                                                 - message_expiry_interval: int (seconds)
     *                                                 - topic_alias: int (1..65535)
     *                                                 - response_topic: string
     *                                                 - correlation_data: string (binary)
     *                                                 - user_properties: array<string, string>|list<array{0:string,1:string}>|list<array{key:string,value:string}>
     *                                                 - content_type: string
     */
    public function __construct(
        public string $topic,
        public string $payload,
        public QoS $qos = QoS::AtMostOnce,
        public bool $retain = false,
        public bool $dup = false,
        public ?int $packetId = null, // only needed for QoS1/2
        public ?array $properties = null,
    ) {
    }
}
