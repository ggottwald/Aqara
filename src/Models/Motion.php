<?php

namespace Aqara\Models;

/**
 * Class Motion
 *
 * @package Aqara\Models
 *
 * @property string $status
 */
class Motion extends Subdevice
{
    const MOTION = 'motion';

    public function __construct(array $attributes = [])
    {
        $this->type = 'motion';
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

        $this->status = $state['status'] ?? null;

        $this->emit('update');
    }
}