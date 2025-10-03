<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Easy;

use Psr\Log\LoggerInterface;
use ScienceStories\Mqtt\Client\Client;
use ScienceStories\Mqtt\Client\Options as ClientOptions;
use ScienceStories\Mqtt\Client\PublishOptions;
use ScienceStories\Mqtt\Contract\ClientInterface;
use ScienceStories\Mqtt\Protocol\MqttVersion;
use ScienceStories\Mqtt\Protocol\QoS;
use ScienceStories\Mqtt\Transport\TcpTransport;
use ScienceStories\Mqtt\Util\RandomId;

final class Mqtt
{
    /**
     * One-shot send: connect, publish, disconnect.
     *
     * @param  array<string,mixed>|null  $tlsOptions
     * @param  array<string,mixed>|null  $properties  MQTT v5 publish properties
     */
    public static function send(
        string $host,
        int $port,
        string $topic,
        string $payload,
        string $version = 'v3',
        bool $tls = false,
        ?string $username = null,
        ?string $password = null,
        ?QoS $qos = null,
        bool $retain = false,
        ?array $tlsOptions = null,
        ?array $properties = null,
        ?string $clientId = null,
        int $keepAlive = 60,
        bool $cleanStart = true,
        ?LoggerInterface $logger = null,
    ): void {
        $client = self::connect(
            host: $host,
            port: $port,
            version: $version,
            tls: $tls,
            username: $username,
            password: $password,
            tlsOptions: $tlsOptions,
            clientId: $clientId,
            keepAlive: $keepAlive,
            cleanStart: $cleanStart,
            logger: $logger,
        );

        try {
            $client->publish($topic, $payload, new PublishOptions(
                qos: $qos ?? QoS::AtMostOnce,
                retain: $retain,
                dup: false,
                properties: $properties,
            ));
        } finally {
            $client->disconnect();
        }
    }

    /**
     * Connect and return a fully configured ClientInterface for longer sessions.
     *
     * @param  array<string,mixed>|null  $tlsOptions
     */
    public static function connect(
        string $host,
        int $port,
        string $version = 'v3',
        bool $tls = false,
        ?string $username = null,
        ?string $password = null,
        ?array $tlsOptions = null,
        ?string $clientId = null,
        int $keepAlive = 60,
        bool $cleanStart = true,
        ?LoggerInterface $logger = null,
    ): ClientInterface {
        $ver = $version === 'v5' ? MqttVersion::V5_0 : MqttVersion::V3_1_1;
        $id  = $clientId !== null && $clientId !== '' ? $clientId : ('php-iot-'.RandomId::clientId(8));

        $opts = new ClientOptions(
            host: $host,
            port: $port,
            version: $ver,
        );
        $opts = $opts
            ->withClientId($id)
            ->withKeepAlive($keepAlive)
            ->withCleanSession($cleanStart);

        if ($username !== null) {
            $opts = $opts->withUser($username, $password);
        }
        if ($tls) {
            $opts = $opts->withTls($tlsOptions ?? [
                'ssl' => [
                    'verify_peer'      => true,
                    'verify_peer_name' => true,
                ],
            ]);
        }

        $client = new Client($opts, new TcpTransport(), logger: $logger);
        $client->connect();

        return $client;
    }
}
