<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Client;

use ScienceStories\Mqtt\Protocol\QoS;

/**
 * Immutable Last Will options for CONNECT.
 */
final class WillOptions
{
    public function __construct(
        public string $topic,
        public string $payload,
        public QoS $qos = QoS::AtMostOnce,
        public bool $retain = false,
        /** @var array<string, mixed>|null MQTT 5 Will properties (unused in v3 and MVP) */
        public ?array $properties = null,
    ) {
    }

    public function withTopic(string $topic): self
    {
        $c        = clone $this;
        $c->topic = $topic;

        return $c;
    }

    public function withPayload(string $payload): self
    {
        $c          = clone $this;
        $c->payload = $payload;

        return $c;
    }

    public function withQos(QoS $qos): self
    {
        $c      = clone $this;
        $c->qos = $qos;

        return $c;
    }

    public function withRetain(bool $retain = true): self
    {
        $c         = clone $this;
        $c->retain = $retain;

        return $c;
    }

    /**
     * @param  array<string, mixed>|null  $props
     */
    public function withProperties(?array $props): self
    {
        $c             = clone $this;
        $c->properties = $props;

        return $c;
    }
}
