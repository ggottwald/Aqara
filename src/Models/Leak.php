<?php

namespace Aqara\Models;

/**
 * Class Leak
 *
 * @package Aqara\Models
 *
 * @property bool $leaking
 */
class Leak extends Subdevice
{
    public function __construct(array $attributes = [])
    {
        $this->type = 'leak';
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

        if (isset($state['status'])) {
            $status = $state['status'];

            if ($status === 'leak') {
                $this->leaking = true;
            } elseif ($status === 'no_leak') {
                $this->leaking = false;
            }
        }

        $this->emit('update');
    }
}