# Changelog


## [1.0.10] - 2025-10-04

### Added
- Unsubscribe packet model (Unsubscribe.php) with comprehensive documentation for MQTT 3.1.1 and 5.0
- Detailed explanation of topic filter matching in Unsubscribe (including wildcard support)
- 5 usage examples in Unsubscribe.php demonstrating various unsubscription patterns
- Packet structure documentation for both MQTT 3.1.1 and 5.0 versions
- Comparison between SUBSCRIBE and UNSUBSCRIBE packets for clarity
- Important notes about MQTT version differences (3.1.1 implicit success vs 5.0 reason codes)
- MQTT 5.0 properties documentation (user_properties support)

### Notes
- Unsubscribe packet model completes the packet model collection alongside Connect, Publish, Subscribe, etc.
- Provides consistency in documentation and code structure across all packet types
- Topic filters in UNSUBSCRIBE must match exactly the filters used in SUBSCRIBE (including wildcards)
- No QoS levels in UNSUBSCRIBE (QoS is only relevant for subscriptions, not unsubscriptions)

## [1.0.9] - 2025-10-04

### Added
- Disconnect packet model (Disconnect.php) with comprehensive documentation for MQTT 3.1.1 and 5.0 disconnect handling
- MQTT 5.0 disconnect reason codes (27 codes: 0x00=Normal, 0x04=Disconnect with Will, 0x80+=errors)
- Helper methods in Disconnect: isNormal(), isError(), getReasonDescription()
- MQTT 5.0 property accessors in Disconnect: getReasonString(), getUserProperties(), getServerReference(), getSessionExpiryInterval()
- PingReq packet model (PingReq.php) with comprehensive keepalive mechanism documentation
- PingResp packet model (PingResp.php) with comprehensive broker response documentation
- New PING example (ping_example.php) demonstrating manual ping, latency testing, and auto-ping behavior for both MQTT versions
- New DISCONNECT example (disconnect_example.php) demonstrating graceful disconnects for both MQTT versions
- Comprehensive usage examples in Disconnect.php showing various disconnect scenarios (6 examples)
- Detailed explanation of keepalive mechanism in PingReq.php with configuration examples
- Timeout handling and broken connection detection in PingResp.php
- MQTT 5.0 disconnect use cases documentation (server shutdown, session takeover, keepalive timeout, quota exceeded, server moved)

### Enhanced
- ping_example.php with three examples for MQTT 3.1.1: manual ping, multiple pings with statistics, auto-ping observation (25-second demonstration)
- ping_example.php with MQTT 5.0 examples: manual ping, connection health check
- disconnect_example.php with MQTT 3.1.1 simple disconnect and comprehensive packet structure explanation
- disconnect_example.php with MQTT 5.0 examples: normal disconnect, disconnect with Will Message, connection duration tracking
- Comprehensive summary sections explaining MQTT 5.0 reason codes, properties, and use cases
- Key differences documentation between MQTT 3.1.1 and 5.0 for both ping and disconnect functionality

### Notes
- PINGREQ/PINGRESP packets are identical in MQTT 3.1.1 and 5.0 (2 bytes, no properties)
- DISCONNECT in MQTT 3.1.1 is simple (2 bytes), while MQTT 5.0 adds reason codes and properties
- Auto-ping triggers at ~90% of keepalive interval to prevent connection timeouts
- Keepalive interval configurable via Options::withKeepAlive() method
- All examples tested with live broker demonstrating real-world behavior

## [1.0.8] - 2025-10-04

### Added
- PubAck packet model (PubAck.php) with comprehensive documentation for MQTT 3.1.1 and 5.0 QoS 1 acknowledgments
- PubRec packet model (PubRec.php) with comprehensive documentation for MQTT 3.1.1 and 5.0 QoS 2 step 1
- PubRel packet model (PubRel.php) with comprehensive documentation for MQTT 3.1.1 and 5.0 QoS 2 step 2
- PubComp packet model (PubComp.php) with comprehensive documentation for MQTT 3.1.1 and 5.0 QoS 2 step 3
- MQTT 5.0 reason code descriptions for all QoS acknowledgment packets (0x00=Success, 0x10=No matching subscribers, 0x80+=errors)
- Helper methods in all QoS packets: isSuccess(), isError(), getReasonDescription()
- MQTT 5.0 property accessors in all QoS packets: getReasonString(), getUserProperties()
- DecoderInterface methods: decodePubAck(), decodePubRec(), decodePubRel(), decodePubComp()
- V311\Decoder implementations for all four QoS acknowledgment packets (simple packet ID only)
- V5\Decoder implementations for all four QoS acknowledgment packets with reason codes and properties
- V5\Decoder::parseQoSAckProperties() helper method for parsing reason_string and user_properties
- New QoS 0 example (qos0_example.php) demonstrating fire-and-forget publishing with both MQTT versions
- Comprehensive QoS flow documentation in all packet models explaining four-packet handshake

### Enhanced
- Client.php property types: lastPubAck changed from ?int to ?PubAck, lastPubComp changed from ?int to ?PubComp
- Client.php::publish() QoS 1 handling to check PubAck object with reason code logging
- Client.php::publish() QoS 2 handling to check PubComp object with reason code logging
- Client.php::loopOnce() PUBACK handler to use decoder and log reason codes and success status
- Client.php::loopOnce() PUBREC handler to use decoder and log reason codes and success status
- Client.php::loopOnce() PUBREL handler to use decoder and log reason codes and success status
- Client.php::loopOnce() PUBCOMP handler to use decoder and log reason codes and success status
- All QoS acknowledgment logging to include packetId, reasonCode, and success status for better debugging
- Type safety throughout QoS handling with proper packet objects instead of raw integers

### Fixed
- MQTT 5.0 reason code inspection now fully supported for QoS 1 and QoS 2 flows
- Proper error detection and reporting for failed QoS acknowledgments
- Type safety in QoS acknowledgment handling with packet objects

## [1.0.7] - 2025-10-04

### Added
- UnsubAck packet model (UnsubAck.php) with comprehensive documentation for MQTT 3.1.1 and 5.0
- MQTT 5.0 reason code descriptions for UNSUBACK (0x00=Success, 0x11=No subscription existed, 0x80+=errors)
- Helper methods in UnsubAck: isSuccess(), hasFailures(), getReasonDescription(), getAllReasonDescriptions()
- Utility method in UnsubAck: getFailedIndices()
- MQTT 5.0 property accessors in UnsubAck: getReasonString(), getUserProperties()
- UnsubscribeResult class for structured unsubscribe operation results
- New UNSUBSCRIBE example (unsubscribe_example.php) is demonstrating subscribe/unsubscribe workflow for both MQTT versions
- Comprehensive documentation for UNSUBSCRIBE packet encoding in both V311\Encoder and V5\Encoder

### Enhanced
- V311\Decoder::decodeUnsubAck() to return UnsubAck object instead of a raw array
- V5\Decoder::decodeUnsubAck() to return UnsubAck object with MQTT 5.0 properties parsing
- DecoderInterface::decodeUnsubAck() return type from array to UnsubAck
- Client.php unsubscribe workflow to handle UnsubAck objects throughout
- V311\Encoder::encodeUnsubscribe() with a comprehensive docblock explaining packet structure
- V5\Encoder::encodeUnsubscribe() with detailed packet structure and properties field documentation

### Fixed
- Type safety in UNSUBACK handling (UnsubAck object instead of raw arrays)
- Simplified UNSUBACK handling in Client::loopOnce() by removing version-specific logic

## [1.0.6] - 2025-10-04

### Added
- SubAck packet model (SubAck.php) with comprehensive documentation for MQTT 3.1.1 and 5.0
- MQTT 3.1.1 return code descriptions (0x00-0x02 = granted QoS, 0x80 = failure)
- MQTT 5.0 reason code descriptions (0x00-0x02 = granted QoS, 0x80+ = various failures)
- Helper methods in SubAck: isSuccess(), hasFailures(), getReasonDescription(), getAllReasonDescriptions()
- Utility methods in SubAck: getGrantedQoS(), getFailedIndices()
- MQTT 5.0 property accessors in SubAck: getReasonString(), getUserProperties()
- New SUBACK inspection example (suback_example.php) demonstrating detailed broker response inspection
- SubAck object in SubscribeResult for detailed inspection of subscription results

### Enhanced
- V311\Decoder::decodeSubAck() to return SubAck object instead of raw array
- V5\Decoder::decodeSubAck() to return SubAck object with MQTT 5.0 properties parsing
- DecoderInterface::decodeSubAck() return type from array to SubAck
- SubscribeResult with SubAck object property and comprehensive documentation
- Client.php to handle SubAck objects throughout subscription workflow

### Fixed
- Type safety in SUBACK handling (SubAck object instead of raw arrays)

## [1.0.5] - 2025-10-04

### Added
- Subscribe packet model (Subscribe.php) with comprehensive documentation explaining MQTT 3.1.1 vs. 5.0 differences
- Detailed usage examples in Subscribe.php showing topic filters, wildcards, QoS levels, and MQTT 5.0 subscription options
- New focused subscription examples (subscribe_v3.php, subscribe_v5.php) demonstrating subscription features
- subscribe_v3.php: MQTT 3.1.1 subscription with configurable topic variable, multiple filters, wildcards, and message listening
- subscribe_v5.php: MQTT 5.0 subscription with three advanced examples (No Local, Retain Handling, all options with user properties)
- Comprehensive inline documentation for Subscribe packet covering topic filters, wildcards, and subscription options
- 6 detailed usage examples in Subscribe.php demonstrating various subscription patterns

### Enhanced
- V311\Encoder::encodeSubscribe() with a comprehensive docblock explaining packet structure and inline comments
- V5\Encoder::encodeSubscribe() with detailed packet structure and subscription options byte layout documentation
- Subscribe packet model explaining No Local, Retain As Published, Retain Handling options for MQTT 5.0
- Both subscription examples with configurable topic variables prominently marked for easy customization
- Message handlers displaying full message details including QoS, retain flag, and MQTT 5.0 properties

## [1.0.4] - 2025-10-04

### Added
- Comprehensive documentation for Publish a packet explaining MQTT 3.1.1 vs. 5.0 differences
- Detailed usage examples in Publish.php showing QoS levels, retain flag, and MQTT 5.0 properties
- New focused publishing examples (publish_v3.php, publish_v5.php) demonstrating all QoS levels and properties
- publish_v3.php: MQTT 3.1.1 publishing with QoS 0/1/2 and retained messages
- publish_v5.php: MQTT 5.0 publishing with comprehensive properties demonstration (content_type, message_expiry_interval, user_properties, response_topic, correlation_data, payload_format_indicator)

### Enhanced
- Publish.php expanded from 37 to 110 lines with detailed documentation
- V311\Encoder::encodePublish() with comprehensive docblock and inline comments
- V5\Encoder::encodePublish() with detailed packet structure explanation
- Publish a packet model with extensive property documentation and 4 usage examples

## [1.0.3] - 2025-10-04

### Added
- EncoderInterface contract is defining standard interface for packet encoders (V311\Encoder and V5\Encoder)
- DecoderInterface contract is defining standard interface for packet decoders (V311\Decoder and V5\Decoder)
- Comprehensive MQTT 5.0 property accessors in ConnAck class (15+ methods)
- Reason code/return code description methods for both MQTT versions in ConnAck
- Connection status helper methods (isSuccess(), getReasonDescription())
- New focused connection testing examples (connect_v3.php, connect_v5.php)
- Comprehensive inline documentation for a Connect packet explaining MQTT 3.1.1 and 5.0 differences
- Detailed PacketType enum documentation explaining each packet type's purpose

### Enhanced
- ConnAck.php with 300+ lines of functionality for inspecting broker responses
- Client.php to use EncoderInterface and DecoderInterface for better type safety
- V311\Encoder and V5\Encoder to implement EncoderInterface
- V311\Decoder and V5\Decoder to implement DecoderInterface
- Connect the packet model with detailed property documentation for both MQTT versions
- PacketType enum with bidirectional flow indicators and usage descriptions

### Fixed
- PHPStan type safety issue in ConnAck::getUserProperties() method
- Proper type filtering for user properties to ensure array<string, string> compliance

### Removed
- connect_online.php (replaced with focused v3 and v5 connection examples)


## [1.0.2] - 2025-10-04

## [1.0.1] - 2025-10-04

## [1.0.0] - 2025-10-04

### Added
- Initial release of PHP IoT MQTT Client
- MQTT 3.1.1 protocol support
- MQTT 5.0 protocol support
- TLS/SSL encryption support
- Auto-reconnect with exponential backoff strategy
- QoS 0, 1, 2 quality of service levels
- Modern PHP 8.4+ implementation with strict types
- PSR-3 logging support
- PSR-14 event dispatcher integration

### Notes
- Development phase: Core MQTT client functionality implemented
- ready for basic MQTT operations
- Tested with popular MQTT brokers (HiveMQ)
