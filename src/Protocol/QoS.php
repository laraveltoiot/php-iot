<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Protocol;

enum QoS: int
{
    case AtMostOnce  = 0; // QoS 0
    case AtLeastOnce = 1; // QoS 1
    case ExactlyOnce = 2; // QoS 2
}
