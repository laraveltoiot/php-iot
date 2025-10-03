<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Events;

use ScienceStories\Mqtt\Client\InboundMessage;

/**
 * PSR-14 event dispatched when an inbound PUBLISH is delivered to the client.
 * DTO-only, immutable.
 */
final class MessageReceived
{
    public function __construct(
        public InboundMessage $message,
    ) {
    }
}
