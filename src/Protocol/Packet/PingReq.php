<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Protocol\Packet;

/**
 * MQTT PINGREQ packet model for MQTT 3.1.1 and 5.0.
 *
 * The PINGREQ packet is sent from the client to the broker to:
 * 1. Indicate to the broker that the client is alive (keepalive mechanism)
 * 2. Check that the broker is alive and responsive
 * 3. Exercise the network to keep the connection open through firewalls/NAT devices
 *
 * Packet Structure:
 * - MQTT 3.1.1: Fixed header only (2 bytes): Type (12), Flags (0), Remaining Length (0)
 * - MQTT 5.0: Identical structure to MQTT 3.1.1 (no changes)
 *
 * Keepalive Mechanism:
 * - The client must send a PINGREQ if no other control packet is sent within the keepalive period
 * - The keepalive interval is specified in the CONNECT packet (in seconds)
 * - If keepalive is 0, the keepalive mechanism is disabled
 * - The broker must respond with a PINGRESP packet
 * - If the broker doesn't respond within a reasonable time, the client should close the connection
 *
 * Behavior:
 * - Both MQTT 3.1.1 and MQTT 5.0 use the same packet structure (no body, no properties)
 * - The packet is always 2 bytes: [0xC0, 0x00] (type 12, flags 0, length 0)
 * - The broker must respond with a PINGRESP packet
 * - Failure to respond indicates a network or broker failure
 *
 * Automatic Keepalive:
 * - Most MQTT clients implement automatic PINGREQ sending
 * - The client tracks time since last packet sent
 * - When approaching the keepalive interval, the client automatically sends PINGREQ
 * - This is typically handled transparently by the client library
 *
 * Usage Examples:
 * ```php
 * // MQTT 3.1.1 and 5.0 use identical PINGREQ packets
 * $pingReq = new PingReq();
 *
 * // Manual ping (usually handled automatically by client)
 * $client->ping(timeout: 5.0);
 *
 * // Configure keepalive interval in CONNECT
 * $options = new Options(host: 'broker.example.com', port: 1883)
 *     ->withKeepAlive(60);  // Send PINGREQ if no activity for 60 seconds
 *
 * // Disable keepalive
 * $options = new Options(host: 'broker.example.com', port: 1883)
 *     ->withKeepAlive(0);  // No automatic PINGREQ
 * ```
 *
 * Implementation Notes:
 * - PINGREQ has no variable header and no payload
 * - The packet is always exactly 2 bytes in both MQTT versions
 * - No reason codes or properties are supported (even in MQTT 5.0)
 * - The broker MUST respond with PINGRESP or the connection should be closed
 */
final class PingReq
{
    /**
     * PINGREQ has no properties - this is just a marker packet.
     * Both MQTT 3.1.1 and MQTT 5.0 use identical packet structure.
     */
    public function __construct()
    {
        // No properties needed - PINGREQ is always empty
    }
}
