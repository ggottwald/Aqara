# Aqara
Get Xiaomi Smart Home device status of gateway and sub-devices.
Supports magic cube, water leak sensor, door/window sensor, motion sensor and mini switch.

This package was inspired by the node package https://github.com/marvinroger/node-lumi-aqara

# Example

```php
$aqara = new Aqara();

$aqara->on('gateway', function ($gateway) {
    $gateway->on('subdevice', function ($device) {
        $device->on('update', function () use ($device) {
            echo var_export($device, true) . chr(10);
        });
    });
});

$aqara->run();
```

## License

This project is licensed under the GNU AFFERO GENERAL PUBLIC LICENSE - see the [LICENSE.md](/LICENSE.md) file for details.