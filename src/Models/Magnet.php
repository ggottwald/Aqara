<?php

namespace Aqara\Models;

/**
 * Class Magnet
 *
 * @package Aqara\Models
 *
 * @property bool $open
 */
class Magnet extends Subdevice
{
    public function __construct(array $attributes = [])
    {
        $this->type = 'magnet';
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

        if (!isset($state['status'])) {
            return;
        }

        if (isset($state['status'])) {
            $this->open = $state['status'] == 'open';
        }

        $this->emit($this->open ? 'open' : 'close');
    }

    /**
     * @return bool
     */
    public function isOpen()
    {
        return $this->open;
    }
}