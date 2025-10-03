<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Protocol;

enum MqttVersion: string
{
    case V3_1_1 = '3.1.1';
    case V5_0   = '5.0';
}
