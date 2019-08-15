<?php

namespace Aqara\Models;

/**
 * Class Smoke
 *
 * @package Aqara\Models
 *
 * @property bool $alarm
 * @property int $density
 */
class Smoke extends Subdevice
{
    public function __construct(array $attributes = [])
    {
        $this->type = 'smoke';
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

        if (isset($state['density'])) {
            $this->density = $state['density'];
        }

        if (isset($state['alarm'])) {
            $this->alarm = $state['alarm'] != '0';

            if ($this->alarm) {
                $this->emit('alarm');
            }
        }
    }
}
