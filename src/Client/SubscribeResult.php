<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Client;

/**
 * Result of a SUBSCRIBE exchange.
 * - packetId: identifier used in the SUBSCRIBE/SUBACK handshake.
 * - results: list of return codes in order of requested filters.
 *   For v3: 0,1,2 = granted QoS; 0x80 = failure.
 *   For v5: reason codes (0x00..0x9F); 0x00..0x02 still map to granted QoS.
 */
final class SubscribeResult
{
    /**
     * @param  list<int>  $results
     */
    public function __construct(
        public int $packetId,
        public array $results,
    ) {
    }
}
