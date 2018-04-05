<?php

namespace Aqara\Models;

/**
 * Class Cube
 *
 * @package Aqara\Models
 *
 * @property string $status
 * @property int    $rotateDegrees
 */
class Cube extends Subdevice
{
    const STATUS_MOVE      = 'move';
    const STATUS_FLIP_90   = 'flip90';
    const STATUS_FLIP_180  = 'flip180';
    const STATUS_ROTATE    = 'rotate';
    const STATUS_SHAKE_AIR = 'shake_air';
    const STATUS_TAP_TWICE = 'tap_twice';

    public function __construct(array $attributes = [])
    {
        $this->type = 'cube';
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

        if (isset($state['rotate'])) {
            $this->status        = 'rotate';
            $this->rotateDegrees = $state['rotate'];
        } else {
            $this->status        = $state['status'] ?? null;
            $this->rotateDegrees = null;
        }

        $this->emit('update');
    }
}