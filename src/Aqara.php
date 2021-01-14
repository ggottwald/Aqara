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

    /**
     * Aqara constructor.
     *
     * @param LoopInterface $loop
     */
    public function __construct($loop = null)
    {
        $this->loop = $loop instanceof LoopInterface ? $loop : Factory::create();
        $this->initReceiver();
    }

    protected function initReceiver()
    {
        $this->createReceiver();

        $this->socket->on(
            'message',
            function ($data) {
                $this->handleMessage($data);
            }
        );

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
        $parsed = @json_decode($message, true);

        if (empty($parsed['cmd'])) {
            return;
        }

        $response = new Response($parsed);

        switch ($response->cmd) {
            case 'heartbeat':
                if ($response->model === 'gateway'
                    && !empty($response->sid)
                    && !isset($this->gatewayList[$response->sid])) {
                    $this->createGateway($response);
                    $this->triggerWhois();
                }
                break;

            case 'iam':
                if (isset($this->gatewayList[$response->sid])) {
                    return;
                }
                $this->createGateway($response);
                return;
        }

        foreach ($this->gatewayList as $gateway) {
            if ($gateway instanceof Gateway
                && $gateway->handleMessage($response)) {
                break;
            }
        }
    }

    /**
     * @param Response $response
     */
    protected function createGateway($response)
    {
        if (empty($response->sid)) {
            // missing sid
            return;
        }

        if (!empty($response->ip)) {
            $ip = $response->ip;
        } elseif (!empty($response->data)) {
            $parsed = @json_decode($response->data, true);
            if (empty($parsed['ip'])) {
                // missing ip
                return;
            }
            $ip = $parsed['ip'];
        } else {
            // missing ip
            return;
        }

        $sid = $response->sid;

        $response->sendUnicast = function ($payload) use ($ip) {
            $this->socket->send($payload, $ip . ':' . static::SERVER_PORT);
        };

        $gateway = new Gateway($response->toArray());
        $gateway->ip = $ip;
        $gateway->on(
            'offline',
            function () use ($sid) {
                unset($this->gatewayList[$sid]);
            }
        );
        $this->gatewayList[$sid] = $gateway;
        $this->emit('gateway', [$gateway]);
    }

    public function resetReceiver()
    {
        $this->close();
        $this->initReceiver();
    }

    public function run()
    {
        $this->loop->run();
    }

    public function tick()
    {
        $this->loop->futureTick(
            function () {
                $this->loop->stop();
            }
        );
        $this->loop->run();
    }

    /**
     * @return LoopInterface
     */
    public function getLoop()
    {
        return $this->loop;
    }

    public function close()
    {
        if ($this->socket instanceof Socket) {
            $this->socket->close();
        }
    }
}
