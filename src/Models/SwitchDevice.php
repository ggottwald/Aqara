<?php

namespace Aqara\Models;

/**
 * Class SwitchDevice
 *
 * @package Aqara\Models
 */
class SwitchDevice extends Subdevice
{
    const CLICK              = 'click';
    const DOUBLE_CLICK       = 'double_click';
    const LONG_CLICK_PRESS   = 'long_click_press';
    const LONG_CLICK_RELEASE = 'long_click_release';

    public function __construct(array $attributes = [])
    {
        $this->type = 'switch';
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

        if (isset($state['status'])
            && in_array($state['status'], [
                static::CLICK,
                static::DOUBLE_CLICK,
                static::LONG_CLICK_PRESS,
                static::LONG_CLICK_RELEASE,
            ])) {
            $this->emit($state['status']);
        } elseif (isset($state['channel_0'])
            && in_array($state['channel_0'], [
                static::CLICK,
                static::DOUBLE_CLICK,
                static::LONG_CLICK_PRESS,
                static::LONG_CLICK_RELEASE,
            ])) {
            $this->emit($state['channel_0'], ['channel' => 0]);
        } elseif (isset($state['channel_1'])
            && in_array($state['channel_1'], [
                static::CLICK,
                static::DOUBLE_CLICK,
                static::LONG_CLICK_PRESS,
                static::LONG_CLICK_RELEASE,
            ])) {
            $this->emit($state['channel_1'], ['channel' => 1]);
        }
    }
}