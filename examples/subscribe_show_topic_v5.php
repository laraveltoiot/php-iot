<?php

declare(strict_types=1);

use ScienceStories\Mqtt\Client\Options;
use ScienceStories\Mqtt\Protocol\MqttVersion;
use ScienceStories\Mqtt\Transport\TcpTransport;
use ScienceStories\Mqtt\Util\ConsoleLogger;

require __DIR__.'/../vendor/autoload.php';

$config = require __DIR__.'/config.php';

$host       = $config['host'];
$port       = $config['port']     ?? (($config['scheme'] ?? 'tcp') === 'tls' ? 8883 : 1883);
$scheme     = $config['scheme']   ?? 'tls';
$username   = $config['username'] ?? null;
$password   = $config['password'] ?? null;
$tlsOptions = $config['tls']      ?? null;

// Choose an explicit topic (no wildcards) so it's crystal clear where to publish.
// You can change this string to anything you want to test.
$topic = 'test/php-iot/demo';

$options = new Options(host: $host, port: $port, version: MqttVersion::V5_0);
$options = $options
    ->withClientId('php-iot-sub-show-topic-v5')
    ->withKeepAlive(30)
    ->withCleanSession(true);
if ($username !== null) {
    $options = $options->withUser($username, $password);
}
if ($scheme === 'tls') {
    $options = $options->withTls($tlsOptions);
}

$logger = new ConsoleLogger();
$client = new ScienceStories\Mqtt\Client\Client($options, new TcpTransport(), logger: $logger);
$client->connect();

// Subscribe to the exact topic and show it clearly so you can publish from another program.
echo "\n==============================================\n";
echo " Subscriber is now listening on topic:\n";
echo "   {$topic}\n";
echo " Publish a message to THIS topic from another program to test.\n";
echo " For example, using mosquitto_pub:\n";
echo "   mosquitto_pub -h {$host} -p {$port} -t '{$topic}' -m 'hello'".(($scheme === 'tls') ? ' --cafile <ca.pem> [--insecure]' : '')."\n";
echo "==============================================\n\n";

$client->subscribe([$topic], 0);

// Run for 2 minutes, printing any received messages.
$deadline = microtime(true) + 120.0;
while (microtime(true) < $deadline) {
    $msg = $client->awaitMessage(0.5);
    if ($msg) {
        fprintf(STDERR, "[%s] %s => %s\n", date('H:i:s'), $msg->topic, $msg->payload);
    }
}

$client->disconnect('finished');
