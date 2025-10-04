<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Client;

use ScienceStories\Mqtt\Protocol\Packet\UnsubAck;

/**
 * Result of an UNSUBSCRIBE exchange.
 *
 * Contains both high-level unsubscription result information and the raw UNSUBACK packet
 * for detailed inspection of broker response.
 *
 * - packetId: identifier used in the UNSUBSCRIBE/UNSUBACK handshake
 * - results: list of reason codes (MQTT 5.0) or empty array (MQTT 3.1.1)
 *   * For v3.1.1: empty array (implicit success)
 *   * For v5: reason codes per topic filter (0x00=Success, 0x11=No subscription existed, 0x80+=errors)
 * - unsubAck: Full UNSUBACK packet for detailed inspection (reason codes, properties, descriptions)
 */
final class UnsubscribeResult
{
    /**
     * @param  int  $packetId  Packet identifier matching the UNSUBSCRIBE request
     * @param  list<int>  $results  List of reason codes (MQTT 5.0) or empty array (MQTT 3.1.1)
     * @param  UnsubAck|null  $unsubAck  Full UNSUBACK packet for detailed inspection
     */
    public function __construct(
        public int $packetId,
        public array $results,
        public ?UnsubAck $unsubAck = null,
    ) {
    }
}
