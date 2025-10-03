<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Client;

final class ConnectResult
{
    public function __construct(
        public bool $sessionPresent,
        public string $protocol,          // "MQTT"
        public string $version,           // "3.1.1" | "5.0"
        public ?int $reasonCode = null,   // MQTT 5 (0 = Success)
        public ?string $assignedClientId = null, // MQTT 5 (optional)
    ) {
    }
}
