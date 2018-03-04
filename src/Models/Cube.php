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
    /**
     * @param array $state
     */
    public function handleState($state)
    {
        if (isset($state['rotate'])) {
            $this->status = 'rotate';
            $this->rotateDegrees = $state['rotate'];
        } else {
            $this->status = $state['status'];
            $this->rotateDegrees = null;
        }

        $this->emit('update');
    }
}