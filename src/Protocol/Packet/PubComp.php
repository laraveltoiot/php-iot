<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Protocol\Packet;

/**
 * PUBCOMP packet model for MQTT 3.1.1 and 5.0.
 *
 * The PUBCOMP packet is sent by the receiver in response to a PUBREL packet with QoS level 2.
 * It is the fourth and final packet in the QoS 2 four-packet handshake, confirming completion.
 *
 * QoS 2 Flow (Four-Packet Handshake):
 * 1. Sender sends PUBLISH packet with QoS 2 and Packet Identifier
 * 2. Receiver sends PUBREC packet with matching Packet Identifier
 * 3. Sender sends PUBREL packet to release the message
 * 4. Receiver sends PUBCOMP packet to confirm completion ← This packet
 *
 * After PUBCOMP is sent/received, both parties can safely discard the Packet Identifier
 * and consider the QoS 2 message delivery complete (exactly once guarantee fulfilled).
 *
 * Key Differences Between MQTT Versions:
 * - MQTT 3.1.1: Simple acknowledgment with Packet Identifier only
 * - MQTT 5.0: Adds Reason Code and Properties for enhanced error reporting
 *
 * MQTT 3.1.1 Structure:
 * - Fixed Header: Type (7), flags (0), Remaining Length (2)
 * - Variable Header: Packet Identifier (2 bytes)
 *
 * MQTT 5.0 Structure:
 * - Fixed Header: Type (7), flags (0), Remaining Length (2+)
 * - Variable Header: Packet Identifier (2 bytes) + Reason Code (1 byte) + Properties
 *
 * MQTT 5.0 Reason Codes:
 * - 0x00 (0): Success - Message delivery complete
 * - 0x92 (146): Packet Identifier not found
 *
 * MQTT 5.0 Properties:
 * - reason_string: Human-readable explanation
 * - user_properties: Custom key-value pairs for application metadata
 */
final class PubComp
{
    /**
     * MQTT 5.0 reason code descriptions.
     *
     * @var array<int, string>
     */
    private const array V5_REASON_CODES = [
        0x00 => 'Success',
        0x92 => 'Packet Identifier not found',
    ];

    /**
     * @param  int  $packetId  Packet identifier matching the PUBREL packet (1-65535)
     * @param  int  $reasonCode  MQTT 5.0 reason code (0 = success, 0x92 = not found). Always 0 for MQTT 3.1.1.
     * @param  array<string, mixed>|null  $properties  MQTT 5.0 PUBCOMP properties. Possible keys:
     *                                                  - reason_string: string (human-readable explanation)
     *                                                  - user_properties: array<string, string> (custom metadata)
     */
    public function __construct(
        public int $packetId,
        public int $reasonCode = 0,
        public ?array $properties = null,
    ) {
    }

    /**
     * Check if the acknowledgment indicates success.
     * For MQTT 3.1.1, always returns true (no reason codes).
     * For MQTT 5.0, returns true if reason code is 0x00 (Success).
     */
    public function isSuccess(): bool
    {
        return $this->reasonCode === 0x00;
    }

    /**
     * Check if the acknowledgment indicates an error.
     * For MQTT 3.1.1, always returns false (no reason codes).
     * For MQTT 5.0, returns true if reason code is not 0x00.
     */
    public function isError(): bool
    {
        return $this->reasonCode !== 0x00;
    }

    /**
     * Get a human-readable description for the reason code (MQTT 5.0).
     *
     * @return string Reason code description or "Unknown" if not recognized
     */
    public function getReasonDescription(): string
    {
        return self::V5_REASON_CODES[$this->reasonCode] ?? 'Unknown';
    }

    /**
     * Get a property value from MQTT 5.0 PUBCOMP properties.
     *
     * @param  string  $key  Property name
     * @param  mixed  $default  Default value if a property doesn't exist
     * @return mixed Property value or default
     */
    public function getProperty(string $key, mixed $default = null): mixed
    {
        return $this->properties[$key] ?? $default;
    }

    /**
     * Check if a property exists in MQTT 5.0 PUBCOMP properties.
     */
    public function hasProperty(string $key): bool
    {
        return isset($this->properties[$key]);
    }

    /**
     * Get the reason string (MQTT 5.0).
     * Provides additional human-readable information about the completion result.
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
}
