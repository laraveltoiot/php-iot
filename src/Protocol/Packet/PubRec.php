<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Protocol\Packet;

/**
 * PUBREC packet model for MQTT 3.1.1 and 5.0.
 *
 * The PUBREC packet is the response to a PUBLISH packet with QoS level 2 (Exactly once delivery).
 * It is the first acknowledgment in the QoS 2 four-packet handshake.
 *
 * QoS 2 Flow (Four-Packet Handshake):
 * 1. Sender sends a PUBLISH packet with QoS 2 and Packet Identifier
 * 2. Receiver sends a PUBREC packet with matching Packet Identifier ← This packet
 * 3. Sender sends PUBREL packet to release the message
 * 4. Receiver sends a PUBCOMP packet to confirm completion
 *
 * Key Differences Between MQTT Versions:
 * - MQTT 3.1.1: Simple acknowledgment with Packet Identifier only
 * - MQTT 5.0: Adds Reason Code and Properties for enhanced error reporting
 *
 * MQTT 3.1.1 Structure:
 * - Fixed Header: Type (5), flags (0), Remaining Length (2)
 * - Variable Header: Packet Identifier (2 bytes)
 *
 * MQTT 5.0 Structure:
 * - Fixed Header: Type (5), flags (0), Remaining Length (2+)
 * - Variable Header: Packet Identifier (2 bytes) + Reason Code (1 byte) + Properties
 *
 * MQTT 5.0 Reason Codes:
 * - 0x00 (0): Success - Message accepted
 * - 0x10 (16): No matching subscribers
 * - 0x80 (128): Unspecified error
 * - 0x83 (131): Implementation specific error
 * - 0x87 (135): Not authorized
 * - 0x90 (144): Topic Name invalid
 * - 0x91 (145): Packet Identifier in use
 * - 0x97 (151): Quota exceeded
 * - 0x99 (153): Payload format invalid
 *
 * MQTT 5.0 Properties:
 * - reason_string: Human-readable error explanation
 * - user_properties: Custom key-value pairs for application metadata
 */
final class PubRec
{
    /**
     * MQTT 5.0 reason code descriptions.
     *
     * @var array<int, string>
     */
    private const array V5_REASON_CODES = [
        0x00 => 'Success',
        0x10 => 'No matching subscribers',
        0x80 => 'Unspecified error',
        0x83 => 'Implementation specific error',
        0x87 => 'Not authorized',
        0x90 => 'Topic Name invalid',
        0x91 => 'Packet Identifier in use',
        0x97 => 'Quota exceeded',
        0x99 => 'Payload format invalid',
    ];

    /**
     * @param  int  $packetId  Packet identifier matching the PUBLISH packet (1-65535)
     * @param  int  $reasonCode  MQTT 5.0 reason code (0 = success, 0x80+ = error). Always 0 for MQTT 3.1.1.
     * @param  array<string, mixed>|null  $properties  MQTT 5.0 PUBREC properties. Possible keys:
     *                                                  - reason_string: string (human-readable error explanation)
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
     * For MQTT 5.0, returns true if reason code is 0x00 (Success) or 0x10 (No matching subscribers).
     */
    public function isSuccess(): bool
    {
        return $this->reasonCode === 0x00 || $this->reasonCode === 0x10;
    }

    /**
     * Check if the acknowledgment indicates an error.
     * For MQTT 3.1.1, always returns false (no reason codes).
     * For MQTT 5.0, returns true if reason code is 0x80 or higher.
     */
    public function isError(): bool
    {
        return $this->reasonCode >= 0x80;
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
     * Get a property value from MQTT 5.0 PUBREC properties.
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
     * Check if a property exists in MQTT 5.0 PUBREC properties.
     */
    public function hasProperty(string $key): bool
    {
        return isset($this->properties[$key]);
    }

    /**
     * Get the reason string (MQTT 5.0).
     * Provides additional human-readable information about the acknowledgment result.
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
