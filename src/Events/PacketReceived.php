<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Events;

/**
 * PSR-14 event dispatched when a raw MQTT packet is received and parsed at the fixed header level.
 * DTO-only, immutable.
 */
final class PacketReceived
{
    public function __construct(
        public string $bytes,
        public int $packetType,
        public int $flags,
        public int $remainingLength,
    ) {
    }
}
