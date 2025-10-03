<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Protocol\V311;

/**
 * MQTT 3.1.1 control packet types (1..14).
 */
enum PacketType: int
{
    case CONNECT     = 1;
    case CONNACK     = 2;
    case PUBLISH     = 3;
    case PUBACK      = 4;
    case PUBREC      = 5;
    case PUBREL      = 6;
    case PUBCOMP     = 7;
    case SUBSCRIBE   = 8;
    case SUBACK      = 9;
    case UNSUBSCRIBE = 10;
    case UNSUBACK    = 11;
    case PINGREQ     = 12;
    case PINGRESP    = 13;
    case DISCONNECT  = 14;
}
