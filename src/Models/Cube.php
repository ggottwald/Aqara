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
        if (!is_array($state)) {
            return;
        }
        
        if (isset($state['rotate'])) {
            $this->status = 'rotate';
            $this->rotateDegrees = $state['rotate'];
        } else {
            $this->status = $state['status'] ?? null;
            $this->rotateDegrees = null;
        }

        $this->emit('update');
    }
}