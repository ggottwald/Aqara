<?php

namespace Aqara\Models;

/**
 * Class Sensor
 *
 * @package Aqara\Models
 *
 * @property float $temperature
 * @property float $humidity
 * @property float $pressure
 */
class Sensor extends Subdevice
{
    public function __construct(array $attributes = [])
    {
        $this->type = 'sensor';
        parent::__construct($attributes);
    }

    /**
     * @param array $state
     */
    public function handleState($state)
    {
        if (!is_array($state)) {
            return;
        }

        parent::handleState($state);

        if (isset($state['temperature'])) {
            $this->temperature = $state['temperature'] / 100;
        }

        if (isset($state['humidity'])) {
            $this->humidity = $state['humidity'] / 100;
        }

        if (isset($state['pressure'])) {
            $this->pressure = $state['pressure'] / 1000;
        }

        $this->emit('update');
    }
}