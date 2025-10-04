# Changelog


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
