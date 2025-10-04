<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Protocol\Packet;

use ScienceStories\Mqtt\Protocol\MqttVersion;

/**
 * CONNACK packet model for MQTT 3.1.1 and 5.0.
 *
 * This class represents the broker's response to a CONNECT packet, containing:
 * - a Session Present flag: indicates if a previous session is being resumed
 * - Return/Reason Code: indicates connection success or failure reason
 * - Properties (MQTT 5.0 only): additional broker capabilities and settings
 *
 * MQTT 3.1.1 Return Codes:
 * - 0: Connection Accepted
 * - 1: Connection Refused: unacceptable protocol version
 * - 2: Connection Refused: identifier rejected
 * - 3: Connection Refused: server unavailable
 * - 4: Connection Refused: bad username or password
 * - 5: Connection Refused: not authorized
 *
 * MQTT 5.0 Reason Codes (subset):
 * - 0: Success
 * - 128: Unspecified error
 * - 129: Malformed Packet
 * - 130: Protocol Error
 * - 131: Implementation specific error
 * - 132: Unsupported Protocol Version
 * - 133: Client Identifier not valid
 * - 134: Bad Username or Password
 * - 135: Not authorized
 * - 136: Server unavailable
 * - 137: Server busy
 * - 138: Banned
 * - 140: Bad authentication method
 * - 144: Topic Name invalid
 * - 149: Packet too large
 * - 151: Quota exceeded
 * - 153: Payload format invalid
 * - 154: Retain not supported
 * - 155: QoS not supported
 * - 156: Use another server
 * - 157: Server moved
 * - 159: Connection rate exceeded
 */
final class ConnAck
{
    /**
     * MQTT 3.1.1 return code descriptions.
     *
     * @var array<int, string>
     */
    private const array V3_RETURN_CODES = [
        0 => 'Connection Accepted',
        1 => 'Connection Refused: unacceptable protocol version',
        2 => 'Connection Refused: identifier rejected',
        3 => 'Connection Refused: server unavailable',
        4 => 'Connection Refused: bad username or password',
        5 => 'Connection Refused: not authorized',
    ];

    /**
     * MQTT 5.0 reason code descriptions.
     *
     * @var array<int, string>
     */
    private const array V5_REASON_CODES = [
        0   => 'Success',
        128 => 'Unspecified error',
        129 => 'Malformed Packet',
        130 => 'Protocol Error',
        131 => 'Implementation specific error',
        132 => 'Unsupported Protocol Version',
        133 => 'Client Identifier not valid',
        134 => 'Bad User Name or Password',
        135 => 'Not authorized',
        136 => 'Server unavailable',
        137 => 'Server busy',
        138 => 'Banned',
        140 => 'Bad authentication method',
        144 => 'Topic Name invalid',
        149 => 'Packet too large',
        151 => 'Quota exceeded',
        153 => 'Payload format invalid',
        154 => 'Retain not supported',
        155 => 'QoS not supported',
        156 => 'Use another server',
        157 => 'Server moved',
        159 => 'Connection rate exceeded',
    ];

    /**
     * @param  bool  $sessionPresent  Whether a previous session is being resumed
     * @param  int  $returnCode  MQTT 3.1.1 return code or MQTT 5.0 reason code (0 = success)
     * @param  array<string, mixed>|null  $properties  MQTT 5.0 CONNACK properties. Possible keys:
     *                                                  - session_expiry_interval: int
     *                                                  - receive_maximum: int
     *                                                  - maximum_qos: int
     *                                                  - retain_available: int (0 or 1)
     *                                                  - maximum_packet_size: int
     *                                                  - assigned_client_identifier: string
     *                                                  - topic_alias_maximum: int
     *                                                  - reason_string: string
     *                                                  - user_properties: array<string, string>
     *                                                  - wildcard_subscription_available: int (0 or 1)
     *                                                  - subscription_identifier_available: int (0 or 1)
     *                                                  - shared_subscription_available: int (0 or 1)
     *                                                  - server_keep_alive: int
     *                                                  - response_information: string
     *                                                  - server_reference: string
     */
    public function __construct(
        public bool $sessionPresent,
        public int $returnCode,
        public ?array $properties = null,
    ) {
    }

    /**
     * Check if the connection was successful.
     */
    public function isSuccess(): bool
    {
        return $this->returnCode === 0;
    }

    /**
     * Get a human-readable description for the return/reason code.
     *
     * @param  MqttVersion|string  $version  MQTT version (MqttVersion enum or "3.1.1"/"5.0" string)
     */
    public function getReasonDescription(MqttVersion|string $version): string
    {
        if (\is_string($version)) {
            $version = $version === '5.0' ? MqttVersion::V5_0 : MqttVersion::V3_1_1;
        }

        $codes = $version === MqttVersion::V5_0 ? self::V5_REASON_CODES : self::V3_RETURN_CODES;

        return $codes[$this->returnCode] ?? 'Unknown';
    }

    /**
     * Get a property value from MQTT 5.0 CONNACK properties.
     *
     * @param  string  $key  Property name
     * @param  mixed  $default  Default value if property doesn't exist
     * @return mixed Property value or default
     */
    public function getProperty(string $key, mixed $default = null): mixed
    {
        return $this->properties[$key] ?? $default;
    }

    /**
     * Check if a property exists in MQTT 5.0 CONNACK properties.
     */
    public function hasProperty(string $key): bool
    {
        return isset($this->properties[$key]);
    }

    /**
     * Get the server to keep live value (MQTT 5.0).
     * If present, the client should use this value instead of the one sent in CONNECT.
     */
    public function getServerKeepAlive(): ?int
    {
        $val = $this->getProperty('server_keep_alive');

        return \is_int($val) ? $val : null;
    }

    /**
     * Get the assigned client identifier (MQTT 5.0).
     * Present when the server assigns an ID because the client connected with an empty ID.
     */
    public function getAssignedClientIdentifier(): ?string
    {
        $val = $this->getProperty('assigned_client_identifier');

        return \is_string($val) ? $val : null;
    }

    /**
     * Get the reason string (MQTT 5.0).
     * Provides additional human-readable information about the connection result.
     */
    public function getReasonString(): ?string
    {
        $val = $this->getProperty('reason_string');

        return \is_string($val) ? $val : null;
    }

    /**
     * Get the maximum QoS level supported by the broker (MQTT 5.0).
     * 0 = QoS 0 only, 1 = QoS 0 and 1, 2 = QoS 0, 1, and 2 (default if not present).
     */
    public function getMaximumQoS(): ?int
    {
        $val = $this->getProperty('maximum_qos');

        return \is_int($val) ? $val : null;
    }

    /**
     * Check if retain is available on the broker (MQTT 5.0).
     * Returns null if not specified (meaning retain is available).
     */
    public function isRetainAvailable(): ?bool
    {
        $val = $this->getProperty('retain_available');
        if ($val === null) {
            return null;
        }

        return $val === 1 || $val === true;
    }

    /**
     * Get the maximum packet size the broker will accept (MQTT 5.0).
     */
    public function getMaximumPacketSize(): ?int
    {
        $val = $this->getProperty('maximum_packet_size');

        return \is_int($val) ? $val : null;
    }

    /**
     * Get the receive maximum (MQTT 5.0).
     * Maximum number of QoS 1 and QoS 2 messages the broker is willing to process concurrently.
     */
    public function getReceiveMaximum(): ?int
    {
        $val = $this->getProperty('receive_maximum');

        return \is_int($val) ? $val : null;
    }

    /**
     * Get the topic alias maximum (MQTT 5.0).
     * Maximum number of topic aliases the broker will accept.
     */
    public function getTopicAliasMaximum(): ?int
    {
        $val = $this->getProperty('topic_alias_maximum');

        return \is_int($val) ? $val : null;
    }

    /**
     * Get user properties (MQTT 5.0).
     *
     * @return array<string, string>
     */
    public function getUserProperties(): array
    {
        $val = $this->getProperty('user_properties');

        if (! \is_array($val)) {
            return [];
        }

        // Ensure all keys and values are strings for type safety
        return array_filter($val, function ($value, $key) {
            return \is_string($key) && \is_string($value);
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Check if wildcard subscriptions are available (MQTT 5.0).
     * Returns null if not specified (meaning wildcard subscriptions are available).
     */
    public function isWildcardSubscriptionAvailable(): ?bool
    {
        $val = $this->getProperty('wildcard_subscription_available');
        if ($val === null) {
            return null;
        }

        return $val === 1 || $val === true;
    }

    /**
     * Check if subscription identifiers are available (MQTT 5.0).
     * Returns null if not specified (meaning subscription identifiers are available).
     */
    public function isSubscriptionIdentifierAvailable(): ?bool
    {
        $val = $this->getProperty('subscription_identifier_available');
        if ($val === null) {
            return null;
        }

        return $val === 1 || $val === true;
    }

    /**
     * Check if shared subscriptions are available (MQTT 5.0).
     * Returns null if not specified (meaning shared subscriptions are available).
     */
    public function isSharedSubscriptionAvailable(): ?bool
    {
        $val = $this->getProperty('shared_subscription_available');
        if ($val === null) {
            return null;
        }

        return $val === 1 || $val === true;
    }

    /**
     * Get response information (MQTT 5.0).
     * Used for request/response patterns.
     */
    public function getResponseInformation(): ?string
    {
        $val = $this->getProperty('response_information');

        return \is_string($val) ? $val : null;
    }

    /**
     * Get server reference (MQTT 5.0).
     * Alternative broker the client can connect to.
     */
    public function getServerReference(): ?string
    {
        $val = $this->getProperty('server_reference');

        return \is_string($val) ? $val : null;
    }
}
