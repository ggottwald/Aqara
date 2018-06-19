<?php

namespace Aqara;

use Aqara\Models\Gateway;
use Aqara\Models\Response;
use Clue\React\Multicast\Factory;
use Evenement\EventEmitter;
use React\EventLoop\ExtEventLoop;
use React\EventLoop\LibEventLoop;
use React\EventLoop\LibEvLoop;
use React\EventLoop\StreamSelectLoop;

class Aqara extends EventEmitter
{
    const MULTICAST_ADDRESS = '224.0.0.50';

    const SERVER_PORT = 9898;

    const DISCOVERY_PORT = 4321;

    /**
     * @var Gateway[]
     */
    protected $gatewayList = [];

    /**
     * @var ExtEventLoop|LibEventLoop|LibEvLoop|StreamSelectLoop
     */
    protected $loop;

    protected $socket;

    public function __construct()
    {
        $this->loop = \React\EventLoop\Factory::create();
        $factory = new Factory($this->loop);
        $this->socket = $factory->createReceiver(static::MULTICAST_ADDRESS . ':' . static::SERVER_PORT);

        $this->socket->on('message', function ($data) {
            $this->handleMessage($data);
        });

        $this->triggerWhois();
    }

    protected function triggerWhois()
    {
        $message = '{"cmd": "whois"}';
        $this->socket->send($message, static::MULTICAST_ADDRESS . ':' . static::DISCOVERY_PORT);
    }

    protected function handleMessage($message)
    {
        $handled = false;

        $parsed = @json_decode($message, true);

        if (is_null($parsed) || !isset($parsed['cmd'])) {
            return;
        }

        $response = new Response($parsed);

        switch ($response->cmd) {
            case 'heartbeat':
                if (!isset($this->gatewayList[$response->sid])) {
                    $handled = true;
                    $this->triggerWhois();
                }
                break;

            case 'iam':
                $handled = true;
                if (isset($this->gatewayList[$response->sid])) {
                    break;
                }
                $parsed['sendUnicast'] = function ($payload) use ($response) {
                    $this->socket->send($payload, $response->ip . ':' . static::SERVER_PORT);
                };

                $gateway = new Gateway($parsed);
                $gateway->on('offline', function () use ($response) {
                    /** @var Response $response */
                    unset($this->gatewayList[$response->sid]);
                });
                $this->gatewayList[$response->sid] = $gateway;
                $this->emit('gateway', [$gateway]);
                break;
        }

        if (!$handled) {
            foreach ($this->gatewayList as $gateway) {
                if ($gateway instanceof Gateway
                    && $gateway->handleMessage($response)) {
                    break;
                }
            }
        }
    }

    public function run()
    {
        $this->loop->run();
    }

    public function tick()
    {
        $this->loop->tick();
    }

    public function close()
    {
        $this->socket->close();
    }
}