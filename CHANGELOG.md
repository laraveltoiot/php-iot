# Changelog

## [1.0.3] - 2025-01-03

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


## [1.0.2] - 2025-01-03

## [1.0.1] - 2025-01-03

## [1.0.0] - 2025-01-03

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
