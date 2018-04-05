<?php

namespace Aqara\Models;

/**
 * Class Motion
 *
 * @package Aqara\Models
 *
 * @property bool $motion
 * @property int  $noMotion
 * @property int  $lux
 */
class Motion extends Subdevice
{
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

        parent::handleState($state);

        $this->lux = $state['lux'] ?? null;

        if (isset($state['status']) || isset($state['no_motion'])) {
            $this->motion = isset($state['status']) && $state['status'] === 'motion';

            // in case of inactivity, json contains only 'no_motion' field
            // with seconds from last motion as the value (reports '120', '180', '300', '600', '1200' and finally '1800')
            $this->noMotion = $state['no_motion'] ?? 0;

            if ($this->motion) {
                $this->emit('motion');
            } elseif ($this->noMotion) {
                $this->emit('noMotion');
            }
        }
    }
}