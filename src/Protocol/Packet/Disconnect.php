<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Protocol\Packet;

use ScienceStories\Mqtt\Protocol\MqttVersion;

/**
 * MQTT DISCONNECT packet model for MQTT 3.1.1 and 5.0.
 *
 * The DISCONNECT packet is sent from the client to the broker (or vice versa in MQTT 5.0)
 * to indicate a graceful shutdown of the connection.
 *
 * Key Differences Between MQTT Versions:
 * - MQTT 3.1.1: Simple packet with no body (2 bytes total: fixed header only)
 * - MQTT 5.0: Includes optional reason code and properties for enhanced error reporting
 *
 * MQTT 3.1.1:
 * - Fixed header only: Type (14), Flags (0), Remaining Length (0)
 * - Always initiated by client
 * - No reason code or properties
 * - Indicates clean disconnect
 *
 * MQTT 5.0 Reason Codes:
 * - 0x00 (0): Normal disconnection
 * - 0x04 (4): Disconnect with Will Message
 * - 0x80 (128): Unspecified error
 * - 0x81 (129): Malformed Packet
 * - 0x82 (130): Protocol Error
 * - 0x83 (131): Implementation specific error
 * - 0x87 (135): Not authorized
 * - 0x89 (137): Server busy
 * - 0x8B (139): Server shutting down
 * - 0x8D (141): Keep Alive timeout
 * - 0x8E (142): Session taken over
 * - 0x8F (143): Topic Filter invalid
 * - 0x93 (147): Receive Maximum exceeded
 * - 0x94 (148): Topic Alias invalid
 * - 0x95 (149): Packet too large
 * - 0x96 (150): Message rate too high
 * - 0x97 (151): Quota exceeded
 * - 0x98 (152): Administrative action
 * - 0x99 (153): Payload format invalid
 * - 0x9A (154): Retain not supported
 * - 0x9B (155): QoS not supported
 * - 0x9C (156): Use another server
 * - 0x9D (157): Server moved
 * - 0x9E (158): Shared Subscriptions not supported
 * - 0x9F (159): Connection rate exceeded
 * - 0xA0 (160): Maximum connect time
 * - 0xA1 (161): Subscription Identifiers not supported
 * - 0xA2 (162): Wildcard Subscriptions not supported
 *
 * MQTT 5.0 Properties:
 * - session_expiry_interval: u32 (seconds until session expires after disconnect)
 * - reason_string: string (human-readable reason for disconnect)
 * - user_properties: array<string, string> (custom metadata)
 * - server_reference: string (alternative server to connect to)
 *
 * Usage Examples:
 * ```php
 * // MQTT 3.1.1 disconnect (simple, no parameters)
 * $disconnect = new Disconnect();
 *
 * // MQTT 5.0 normal disconnect
 * $disconnect = new Disconnect(reasonCode: 0x00);
 *
 * // MQTT 5.0 disconnect with Will Message
 * $disconnect = new Disconnect(reasonCode: 0x04);
 *
 * // MQTT 5.0 disconnect with reason and properties
 * $disconnect = new Disconnect(
 *     reasonCode: 0x8D,  // Keep Alive timeout
 *     properties: [
 *         'reason_string' => 'Client keepalive timeout',
 *         'user_properties' => [
 *             'client_version' => '1.0.0',
 *             'timeout_seconds' => '120',
 *         ],
 *     ]
 * );
 *
 * // MQTT 5.0 server shutdown notification
 * $disconnect = new Disconnect(
 *     reasonCode: 0x8B,  // Server shutting down
 *     properties: [
 *         'reason_string' => 'Server maintenance',
 *         'server_reference' => 'backup.mqtt.example.com',
 *     ]
 * );
 *
 * // MQTT 5.0 session takeover
 * $disconnect = new Disconnect(
 *     reasonCode: 0x8E,  // Session taken over
 *     properties: [
 *         'reason_string' => 'Another client connected with same Client ID',
 *     ]
 * );
 * ```
 */
final class Disconnect
{
    /**
     * MQTT 5.0 reason code descriptions for DISCONNECT.
     *
     * @var array<int, string>
     */
    private const array V5_REASON_CODES = [
        0x00 => 'Normal disconnection',
        0x04 => 'Disconnect with Will Message',
        0x80 => 'Unspecified error',
        0x81 => 'Malformed Packet',
        0x82 => 'Protocol Error',
        0x83 => 'Implementation specific error',
        0x87 => 'Not authorized',
        0x89 => 'Server busy',
        0x8B => 'Server shutting down',
        0x8D => 'Keep Alive timeout',
        0x8E => 'Session taken over',
        0x8F => 'Topic Filter invalid',
        0x93 => 'Receive Maximum exceeded',
        0x94 => 'Topic Alias invalid',
        0x95 => 'Packet too large',
        0x96 => 'Message rate too high',
        0x97 => 'Quota exceeded',
        0x98 => 'Administrative action',
        0x99 => 'Payload format invalid',
        0x9A => 'Retain not supported',
        0x9B => 'QoS not supported',
        0x9C => 'Use another server',
        0x9D => 'Server moved',
        0x9E => 'Shared Subscriptions not supported',
        0x9F => 'Connection rate exceeded',
        0xA0 => 'Maximum connect time',
        0xA1 => 'Subscription Identifiers not supported',
        0xA2 => 'Wildcard Subscriptions not supported',
    ];

    /**
     * @param  int  $reasonCode  MQTT 5.0 reason code (0x00 = normal, 0x80+ = error). Ignored for MQTT 3.1.1.
     * @param  array<string, mixed>|null  $properties  MQTT 5.0 DISCONNECT properties. Possible keys:
     *                                                  - session_expiry_interval: int (seconds until session expires)
     *                                                  - reason_string: string (human-readable disconnect reason)
     *                                                  - user_properties: array<string, string> (custom metadata)
     *                                                  - server_reference: string (alternative server hostname)
     */
    public function __construct(
        public int $reasonCode = 0x00,
        public ?array $properties = null,
    ) {
    }

    /**
     * Check if the disconnect was normal (not an error).
     * For MQTT 3.1.1, always returns true (no reason codes).
     * For MQTT 5.0, returns true if reason code is 0x00 or 0x04.
     */
    public function isNormal(): bool
    {
        return $this->reasonCode === 0x00 || $this->reasonCode === 0x04;
    }

    /**
     * Check if the disconnect was due to an error.
     * For MQTT 3.1.1, always returns false.
     * For MQTT 5.0, returns true if reason code >= 0x80.
     */
    public function isError(): bool
    {
        return $this->reasonCode >= 0x80;
    }

    /**
     * Get a human-readable description for the reason code.
     *
     * @param  MqttVersion|string  $version  MQTT version (MqttVersion enum or "3.1.1"/"5.0" string)
     */
    public function getReasonDescription(MqttVersion|string $version = MqttVersion::V5_0): string
    {
        // MQTT 3.1.1 has no reason codes
        if ($version === MqttVersion::V3_1_1 || $version === '3.1.1') {
            return 'Normal disconnection';
        }

        return self::V5_REASON_CODES[$this->reasonCode] ?? 'Unknown';
    }

    /**
     * Get a property value from MQTT 5.0 DISCONNECT properties.
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
     * Check if a property exists in MQTT 5.0 DISCONNECT properties.
     */
    public function hasProperty(string $key): bool
    {
        return isset($this->properties[$key]);
    }

    /**
     * Get the reason string (MQTT 5.0).
     * Provides additional human-readable information about the disconnection.
     */
    public function getReasonString(): ?string
    {
        $val = $this->getProperty('reason_string');

        return \is_string($val) ? $val : null;
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
     * Get the server reference (MQTT 5.0).
     * Alternative server hostname the client can connect to.
     */
    public function getServerReference(): ?string
    {
        $val = $this->getProperty('server_reference');

        return \is_string($val) ? $val : null;
    }

    /**
     * Get the session expiry interval (MQTT 5.0).
     * Number of seconds until the session expires after disconnect.
     */
    public function getSessionExpiryInterval(): ?int
    {
        $val = $this->getProperty('session_expiry_interval');

        return \is_int($val) ? $val : null;
    }
}
