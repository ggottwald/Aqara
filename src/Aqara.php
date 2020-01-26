<?php

namespace Aqara;

use Aqara\Models\Gateway;
use Aqara\Models\Response;
use Evenement\EventEmitter;
use React\Datagram\Socket;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use RuntimeException;

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
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @var Socket
     */
    protected $socket;

    public function __construct()
    {
        $this->loop = Factory::create();

        $this->createReceiver();

        $this->socket->on('message', function ($data) {
            $this->handleMessage($data);
        });

        $this->triggerWhois();
    }

    protected function createReceiver()
    {
        $stream = @stream_socket_server('udp://0.0.0.0:' . static::SERVER_PORT, $errno, $errstr, STREAM_SERVER_BIND);
        if ($stream === false) {
            throw new RuntimeException('Unable to create receiving socket: ' . $errstr, $errno);
        }

        $socket = socket_import_stream($stream);
        if ($stream === false) {
            throw new RuntimeException('Unable to access underlying socket resource');
        }

        // allow multiple processes to bind to the same address
        $ret = socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
        if ($ret === false) {
            throw new RuntimeException('Unable to enable SO_REUSEADDR');
        }

        // join multicast group and bind to port
        $ret = socket_set_option(
            $socket,
            IPPROTO_IP,
            MCAST_JOIN_GROUP,
            ['group' => static::MULTICAST_ADDRESS, 'interface' => 0]
        );
        if ($ret === false) {
            throw new RuntimeException('Unable to join multicast group');
        }

        $this->socket = new Socket($this->loop, $stream);
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
                if ($parsed['model'] === 'gateway' && !isset($this->gatewayList[$response->sid])) {
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
        $this->loop->futureTick(function () {
            $this->loop->stop();
        });
        $this->loop->run();
    }

    /**
     * @return LoopInterface
     */
    public function getLoop()
    {
        return $this->loop;
    }

    /**
     * @param callable $listener
     */
    public function futureTick($listener)
    {
        $this->loop->futureTick($listener);
    }

    public function close()
    {
        $this->loop->stop();
        $this->socket->close();
    }
}
