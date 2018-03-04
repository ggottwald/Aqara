<?php

namespace Aqara\Models;

use Evenement\EventEmitterTrait;

/**
 * Class Subdevice
 *
 * @package Aqara\Models
 * @property string $sid
 * @property int    $voltage
 */
abstract class Subdevice extends DataModel
{
    use EventEmitterTrait;

    /**
     * @param array $state
     */
    public function handleState($state)
    {

    }
}