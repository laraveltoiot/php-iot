<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Protocol\Packet;

/**
 * MQTT control packet types for MQTT 3.1.1 and 5.0 (values 1..14).
 *
 * Each packet type represents a specific message in the MQTT protocol:
 * - CONNECT: Client request to connect to broker
 * - CONNACK: Broker acknowledgment of connection
 * - PUBLISH: Publish message to a topic
 * - PUBACK: QoS 1 publish acknowledgment
 * - PUBREC: QoS 2 publish received (part 1 of two-phase acknowledgment)
 * - PUBREL: QoS 2 publish release (part 2 of two-phase acknowledgment)
 * - PUBCOMP: QoS 2 publish complete (final acknowledgment)
 * - SUBSCRIBE: Client subscription request
 * - SUBACK: Broker subscription acknowledgment
 * - UNSUBSCRIBE: Client unsubscribe request
 * - UNSUBACK: Broker unsubscribe acknowledgment
 * - PINGREQ: Client ping request (keepalive)
 * - PINGRESP: Broker ping response
 * - DISCONNECT: Client or broker disconnect notification
 *
 * The packet type value is encoded in the upper 4 bits of the fixed header's first byte.
 */
enum PacketType: int
{
    case CONNECT     = 1;  // Client -> Broker: Connection request
    case CONNACK     = 2;  // Broker -> Client: Connection acknowledgment
    case PUBLISH     = 3;  // Bidirectional: Publish a message
    case PUBACK      = 4;  // Bidirectional: QoS 1 acknowledgment
    case PUBREC      = 5;  // Bidirectional: QoS 2 delivery part 1
    case PUBREL      = 6;  // Bidirectional: QoS 2 delivery part 2
    case PUBCOMP     = 7;  // Bidirectional: QoS 2 delivery part 3
    case SUBSCRIBE   = 8;  // Client -> Broker: Subscribe request
    case SUBACK      = 9;  // Broker -> Client: Subscribe acknowledgment
    case UNSUBSCRIBE = 10; // Client -> Broker: Unsubscribe request
    case UNSUBACK    = 11; // Broker -> Client: Unsubscribe acknowledgment
    case PINGREQ     = 12; // Client -> Broker: Ping request
    case PINGRESP    = 13; // Broker -> Client: Ping response
    case DISCONNECT  = 14; // Bidirectional: Disconnect notification (v5 allows broker-initiated)
}
