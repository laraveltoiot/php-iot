<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Protocol\V311\Packet;

/**
 * Minimal CONNECT packet model (no Will message for MVP).
 */
final class Connect
{
    /**
     * @param  array<string, mixed>|null  $properties  MQTT 5 CONNECT properties (e.g., session_expiry_interval)
     */
    public function __construct(
        public string $clientId,
        public int $keepAlive = 60,
        public bool $cleanSession = true,
        public ?string $username = null,
        public ?string $password = null,
        public ?\ScienceStories\Mqtt\Client\WillOptions $will = null,
        public ?array $properties = null,
    ) {
    }
}
