<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Protocol\Packet;

use ScienceStories\Mqtt\Client\WillOptions;

/**
 * MQTT CONNECT packet model.
 * - Used by both MQTT 3.1.1 and MQTT 5.0 encoders.
 * - For MQTT 3.1.1, the cleanSession flag indicates whether to start a new session or resume an existing one.
 * - For MQTT 5.0, cleanSession maps to Clean Start flag, and optional properties are carried in $properties.
 */
final class Connect
{
    /**
     * @param  string  $clientId  Client identifier (max 23 chars for v3.1.1, longer allowed in v5)
     * @param  int  $keepAlive  Keep alive interval in seconds (0 = disabled)
     * @param  bool  $cleanSession  Clean Session (v3.1.1) / Clean Start (v5) flag
     * @param  string|null  $username  Username for authentication
     * @param  string|null  $password  Password for authentication
     * @param  WillOptions|null  $will  Last Will and Testament message
     * @param  array<string, mixed>|null  $properties  MQTT 5 CONNECT properties. Supported keys:
     *                                                 - session_expiry_interval: int (seconds, 0 = session ends at disconnect)
     *                                                 - receive_maximum: int (max QoS 1 and QoS 2 packets the client is willing to process concurrently)
     *                                                 - maximum_packet_size: int (maximum packet size the client is willing to accept)
     *                                                 - topic_alias_maximum: int (max topic aliases the client will accept)
     *                                                 - request_response_information: bool (whether to include response information in CONNACK)
     *                                                 - request_problem_information: bool (whether to include reason string and user properties in CONNACK)
     *                                                 - user_properties: array<string, string> (additional metadata)
     *                                                 - authentication_method: string (authentication method name)
     *                                                 - authentication_data: string (binary authentication data)
     */
    public function __construct(
        public string       $clientId, // example: 'my-client-1'
        public int          $keepAlive = 60,
        public bool         $cleanSession = true,
        public ?string      $username = null,
        public ?string      $password = null,
        public ?WillOptions $will = null,
        public ?array       $properties = null,
    ) {
    }
}
