<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Client;

use ScienceStories\Mqtt\Protocol\QoS;

final class PublishOptions
{
    /**
     * @param  array<string, mixed>|null  $properties
     */
    public function __construct(
        public QoS $qos = QoS::AtMostOnce,
        public bool $retain = false,
        public bool $dup = false,
        public ?array $properties = null
    ) {
    }
}
