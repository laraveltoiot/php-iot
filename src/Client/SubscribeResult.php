<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Client;

use ScienceStories\Mqtt\Protocol\Packet\SubAck;

/**
 * Result of a SUBSCRIBE exchange.
 *
 * Contains both high-level subscription result information and the raw SUBACK packet
 * for detailed inspection of broker response and capabilities.
 *
 * - packetId: identifier used in the SUBSCRIBE/SUBACK handshake
 * - results: list of return codes in order of requested filters
 *   * For v3: 0,1,2 = granted QoS; 0x80 = failure
 *   * For v5: reason codes (0x00..0x9F); 0x00..0x02 still map to granted QoS
 * - subAck: Full SUBACK packet for detailed inspection (return codes, properties, descriptions)
 */
final class SubscribeResult
{
    /**
     * @param  int  $packetId  Packet identifier matching the SUBSCRIBE request
     * @param  list<int>  $results  List of return/reason codes (one per-topic filter)
     * @param  SubAck|null  $subAck  Full SUBACK packet for detailed inspection
     */
    public function __construct(
        public int $packetId,
        public array $results,
        public ?SubAck $subAck = null,
    ) {
    }
}
