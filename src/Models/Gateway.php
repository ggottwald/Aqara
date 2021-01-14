<?php

namespace Aqara\Models;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;

/**
 * Class Gateway
 *
 * @package Aqara\Models
 *
 * @property string $ip
 * @property int $port
 * @property bool $ready
 * @property string $rgb
 * @property string $brightness
 * @property Subdevice[] $subDevices
 * @property \Closure $sendUnicast
 *
 * @method sendUnicast(string $payload)
 */
class Gateway extends Response implements EventEmitterInterface
{
    use EventEmitterTrait;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (!is_array($this->subDevices)) {
            $this->subDevices = [];
        }

        $payload = '{"cmd": "get_id_list"}';
        $this->sendUnicast($payload);
    }

    /**
     * @param Response $response
     * @return bool
     */
    public function handleMessage($response)
    {
        switch ($response->cmd) {
            case 'get_id_list_ack':
                // TODO: refresh key
                $payload = '{"cmd": "read", "sid": "' . $this->sid . '"}';
                $this->sendUnicast($payload);

                $state = @json_decode($response->data, true);

                if (!is_array($state)) {
                    return false;
                }

                foreach ($state as $sid) {
                    $payload = '{"cmd": "read", "sid": "' . $sid . '"}';
                    $this->sendUnicast($payload);
                }
                break;

            case 'read_ack':
                $state = @json_decode($response->data, true);

                if ($response->sid == $this->sid) {
                    $this->handleState($state);
                    $this->ready = true;
                    $this->emit('ready');
                    return true;
                }

                $this->registerSubDevice($response);
                return $this->handleSubDeviceState($response->sid, $state);
            case 'heartbeat':
                if ($response->sid == $this->sid) {
                    $this->emit('heartbeat');
                }
                break;
            case 'report':
                $state = @json_decode($response->data, true);
                if ($response->sid == $this->sid) {
                    $this->handleState($state);
                    return true;
                }

                $this->registerSubDevice($response);
                return $this->handleSubDeviceState($response->sid, $state);
        }

        return true;
    }

    /**
     * @param Response $response
     */
    protected function registerSubDevice($response)
    {
        if (empty($response->sid)) {
            return;
        }

        if (isset($this->subDevices[$response->sid])) {
            return;
        }

        switch ($response->model) {
            case 'magnet':
            case 'sensor_magnet.aq2':
                $subDevice = new Magnet(
                    [
                        'sid' => $response->sid,
                        'model' => $response->model,
                    ]
                );
                break;
            case 'switch':
            case 'sensor_switch.aq2':
            case '86sw1':
            case '86sw2':
            case 'remote.b1acn01';
            case 'remote.b186acn01':
            case 'remote.b286acn01':
                $subDevice = new SwitchDevice(
                    [
                        'sid' => $response->sid,
                        'model' => $response->model,
                    ]
                );
                break;
            case 'motion':
            case 'sensor_motion.aq2':
                $subDevice = new Motion(
                    [
                        'sid' => $response->sid,
                        'model' => $response->model,
                    ]
                );
                break;
            case 'sensor_ht':
            case 'weather.v1':
                $subDevice = new Sensor(
                    [
                        'sid' => $response->sid,
                        'model' => $response->model,
                    ]
                );
                break;
            case 'sensor_wleak.aq1':
                $subDevice = new Leak(
                    [
                        'sid' => $response->sid,
                        'model' => $response->model,
                    ]
                );
                break;
            case 'cube':
                $subDevice = new Cube(
                    [
                        'sid' => $response->sid,
                        'model' => $response->model,
                    ]
                );
                break;
            case 'smoke':
                $subDevice = new Smoke(
                    [
                        'sid' => $response->sid,
                        'model' => $response->model,
                    ]
                );
                break;
            default:
                $subDevice = new class(
                    [
                        'sid' => $response->sid,
                        'model' => $response->model,
                    ]
                ) extends Subdevice {
                    public function __construct(array $attributes = [])
                    {
                        $this->type = 'unknown';
                        parent::__construct($attributes);
                    }
                };
        }

        if (isset($subDevice)) {
            $subDevices = $this->subDevices;
            $subDevices[$response->sid] = $subDevice;
            $this->subDevices = $subDevices;
            $this->emit('subdevice', [$subDevice]);
        }
    }

    /**
     * @param string $sid
     * @param array $state
     * @return bool
     */
    protected function handleSubDeviceState($sid, $state)
    {
        if (empty($sid)) {
            return false;
        }

        if (!is_array($state)) {
            return false;
        }

        if (isset($this->subDevices[$sid])) {
            $subDevice = $this->subDevices[$sid];
            $subDevice->handleState($state);
        }
        return true;
    }

    /**
     * @param array $state
     */
    protected function handleState($state)
    {
        if (is_array($state)
            && isset($state['rgb'])) {
            $hexValue = dechex($state['rgb']);

            if (strlen($hexValue) >= 7) {
                $this->brightness = strrev(substr(strrev($hexValue), 6));
                $this->rgb = strrev(substr(strrev($hexValue), 0, 6));
                $this->emit('lightState', ['rgb' => $this->rgb, 'brightness' => $this->brightness]);
            }
        }
    }
}
