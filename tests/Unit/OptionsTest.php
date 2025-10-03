<?php

declare(strict_types=1);

use ScienceStories\Mqtt\Client\Options;
use ScienceStories\Mqtt\Protocol\MqttVersion;

test('Options can be created with default values', function () {
    $options = new Options('localhost');

    expect($options->clientId)->toBeString()
        ->and($options->version)->toBe(MqttVersion::V3_1_1)
        ->and($options->host)->toBe('localhost')
        ->and($options->port)->toBe(1883)
        ->and($options->keepAlive)->toBe(60)
        ->and($options->cleanSession)->toBeTrue();
});

test('Options can be created with custom client ID', function () {
    $options = new Options('localhost', clientId: 'test-client-123');

    expect($options->clientId)->toBe('test-client-123');
});

test('Options can be configured for MQTT 3.1.1', function () {
    $options = new Options('localhost', version: MqttVersion::V3_1_1);

    expect($options->version)->toBe(MqttVersion::V3_1_1);
});

test('Options withHost returns new instance with updated values', function () {
    $original = new Options('localhost');
    $updated  = $original->withHost('broker.example.com', 8883);

    expect($original->host)->toBe('localhost')
        ->and($original->port)->toBe(1883)
        ->and($updated->host)->toBe('broker.example.com')
        ->and($updated->port)->toBe(8883)
        ->and($updated)->not->toBe($original);
});

test('Options withUser returns new instance with credentials', function () {
    $original = new Options('localhost');
    $updated  = $original->withUser('user123', 'pass456');

    expect($original->username)->toBeNull()
        ->and($original->password)->toBeNull()
        ->and($updated->username)->toBe('user123')
        ->and($updated->password)->toBe('pass456')
        ->and($updated)->not->toBe($original);
});

test('Options withKeepAlive returns new instance', function () {
    $original = new Options('localhost');
    $updated  = $original->withKeepAlive(120);

    expect($original->keepAlive)->toBe(60)
        ->and($updated->keepAlive)->toBe(120)
        ->and($updated)->not->toBe($original);
});

test('Options withCleanSession returns new instance', function () {
    $original = new Options('localhost', cleanSession: true);
    $updated  = $original->withCleanSession(false);

    expect($original->cleanSession)->toBeTrue()
        ->and($updated->cleanSession)->toBeFalse()
        ->and($updated)->not->toBe($original);
});
