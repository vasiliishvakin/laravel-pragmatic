<?php

declare(strict_types=1);

namespace Pragmatic\Json;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Pragmatic\Support\FactoryContainer;

final class JsonFactoryContainer extends FactoryContainer
{
    public function __construct(Container $app)
    {
        parent::__construct($app);

        $this->registerJsonDrivers();
    }

    protected function registerJsonDrivers(): void
    {
        $config = config('toolbox.json.drivers', []);

        foreach ($config as $name => $settings) {
            $this->singleton($name, fn () => $this->app->make(
                JsonManagerInstance::class,
                [
                    'factoryContainer' => $this,
                    'driver' => $this->createDriver($settings),
                ]
            ));
        }
    }

    protected function createDriver(array $settings): JsonDriverContract
    {
        $driverClass = $settings['driver'] ?? null;
        $params = $settings['params'] ?? [];

        if (! is_string($driverClass) || ! class_exists($driverClass)) {
            throw new InvalidArgumentException('Invalid JSON driver class: '.($driverClass ?? 'null'));
        }

        return $this->app->make($driverClass, $params);
    }
}
