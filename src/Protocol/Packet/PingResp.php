<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Protocol\Packet;

/**
 * MQTT PINGRESP packet model for MQTT 3.1.1 and 5.0.
 *
 * The PINGRESP packet is sent from the broker to the client in response to a PINGREQ packet.
 * It confirms that the broker is alive and the connection is still active.
 *
 * Packet Structure:
 * - MQTT 3.1.1: Fixed header only (2 bytes): Type (13), Flags (0), Remaining Length (0)
 * - MQTT 5.0: Identical structure to MQTT 3.1.1 (no changes)
 *
 * Purpose:
 * - Confirms the broker is alive and responsive
 * - Confirms the network connection is functional
 * - Allows the client to detect a broken connection
 * - Must be sent by the broker in response to every PINGREQ
 *
 * Behavior:
 * - Both MQTT 3.1.1 and MQTT 5.0 use the same packet structure (no body, no properties)
 * - The packet is always 2 bytes: [0xD0, 0x00] (type 13, flags 0, length 0)
 * - The broker MUST send PINGRESP in response to PINGREQ
 * - If the client doesn't receive PINGRESP within a timeout period, it should close the connection
 *
 * Timeout Handling:
 * - The MQTT specification doesn't mandate a specific timeout duration
 * - Common practice: 1.5x the keepalive interval (e.g., 90 seconds for 60-second keepalive)
 * - If PINGRESP is not received within the timeout, the connection is considered broken
 * - The client should close the connection and attempt to reconnect
 *
 * Usage Examples:
 * ```php
 * // MQTT 3.1.1 and 5.0 use identical PINGRESP packets
 * $pingResp = new PingResp();
 *
 * // Manual ping with timeout
 * try {
 *     $success = $client->ping(timeout: 5.0);
 *     if ($success) {
 *         echo "Broker is alive\n";
 *     }
 * } catch (Timeout $e) {
 *     echo "Broker didn't respond to PING\n";
 *     $client->disconnect();
 * }
 *
 * // Automatic keepalive handles PINGRESP internally
 * $client->loopOnce(0.1);  // Processes PINGRESP if received
 * ```
 *
 * Implementation Notes:
 * - PINGRESP has no variable header and no payload
 * - The packet is always exactly 2 bytes in both MQTT versions
 * - No reason codes or properties are supported (even in MQTT 5.0)
 * - Missing PINGRESP indicates a broken connection or unresponsive broker
 * - The client library typically handles PINGRESP automatically in the message loop
 */
final class PingResp
{
    /**
     * PINGRESP has no properties - this is just a marker packet.
     * Both MQTT 3.1.1 and MQTT 5.0 use identical packet structure.
     */
    public function __construct()
    {
        // No properties needed - PINGRESP is always empty
    }
}
