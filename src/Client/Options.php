<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Client;

use ScienceStories\Mqtt\Protocol\MqttVersion;

/**
 * Options encapsulate connection settings for the MQTT client.
 * Immutable builder-style API.
 */
final class Options
{
    /**
     * @param  array<string, mixed>|null  $tlsOptions
     */
    public function __construct(
        public string $host,
        public int $port = 1883,
        public string $clientId = '',
        public int $keepAlive = 60,
        public bool $cleanSession = true,
        public ?string $username = null,
        public ?string $password = null,
        public MqttVersion $version = MqttVersion::V3_1_1,
        public bool $useTls = false,
        public ?array $tlsOptions = null,
        public ?WillOptions $will = null,
        // MQTT 5 session expiry (CONNECT property). Null = do not send property.
        public ?int $sessionExpiry = null,
        // Auto reconnect options
        public bool $autoReconnect = false,
        public int $reconnectMaxAttempts = 5,
        public float $reconnectBaseDelay = 0.2, // seconds
        public float $reconnectMaxDelay = 5.0,  // seconds
        public float $reconnectJitter = 0.2,    // +/- 20%,
        /** @var list<string> */
        public array $messageFilters = [],
    ) {
    }

    public function withHost(string $host, int $port = 1883): self
    {
        $c       = clone $this;
        $c->host = $host;
        $c->port = $port;

        return $c;
    }

    public function withClientId(string $id): self
    {
        $c           = clone $this;
        $c->clientId = $id;

        return $c;
    }

    public function withUser(string $username, ?string $password = null): self
    {
        $c           = clone $this;
        $c->username = $username;
        $c->password = $password;

        return $c;
    }

    public function withKeepAlive(int $seconds): self
    {
        $c            = clone $this;
        $c->keepAlive = $seconds;

        return $c;
    }

    public function withCleanSession(bool $flag): self
    {
        $c               = clone $this;
        $c->cleanSession = $flag;

        return $c;
    }

    /**
     * MQTT 5 Session Expiry Interval (seconds). Null to omit property; 0 to expire immediately; >0 for persistent session.
     */
    public function withSessionExpiry(?int $seconds): self
    {
        $c = clone $this;
        if ($seconds !== null) {
            if ($seconds < 0) {
                $seconds = 0;
            }
            // Clamp to 32-bit unsigned max per MQTT 5
            if ($seconds > 0xFFFFFFFF) {
                $seconds = 0xFFFFFFFF;
            }
        }
        $c->sessionExpiry = $seconds;

        return $c;
    }

    /**
     * @param  array<string, mixed>|null  $options
     */
    public function withTls(?array $options = null): self
    {
        $c             = clone $this;
        $c->useTls     = true;
        $c->tlsOptions = $options;

        return $c;
    }

    public function withWill(?WillOptions $will): self
    {
        $c       = clone $this;
        $c->will = $will;

        return $c;
    }

    public function withAutoReconnect(
        bool $enable = true,
        int $maxAttempts = 5,
        float $baseDelay = 0.2,
        float $maxDelay = 5.0,
        float $jitter = 0.2,
    ): self {
        $c                       = clone $this;
        $c->autoReconnect        = $enable;
        $c->reconnectMaxAttempts = $maxAttempts;
        $c->reconnectBaseDelay   = $baseDelay;
        $c->reconnectMaxDelay    = $maxDelay;
        $c->reconnectJitter      = $jitter;

        return $c;
    }

    /**
     * Add a single client-side delivery filter (MQTT topic filter syntax: + and # supported).
     */
    public function withMessageFilter(string $filter): self
    {
        $f = trim($filter);
        $c = clone $this;
        if ($f !== '') {
            $c->messageFilters   = $c->messageFilters ?? [];
            $c->messageFilters[] = $f;
        }

        return $c;
    }

    /**
     * Replace client-side delivery filters with the provided list.
     *
     * @param  list<string>  $filters
     */
    public function withMessageFilters(array $filters): self
    {
        $out = [];
        foreach ($filters as $f) {
            $s = trim((string) $f);
            if ($s !== '') {
                $out[] = $s;
            }
        }
        $c                 = clone $this;
        $c->messageFilters = $out;

        return $c;
    }
}
