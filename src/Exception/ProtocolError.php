<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Exception;

/**
 * Thrown when an MQTT protocol violation occurs,
 * e.g. malformed packets, invalid flags, or bad encodings.
 */
class ProtocolError extends MqttException
{
}
