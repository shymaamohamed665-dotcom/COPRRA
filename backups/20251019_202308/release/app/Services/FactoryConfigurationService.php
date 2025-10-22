<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Factories\Factory;

final readonly class FactoryConfigurationService
{
    public function __construct(private string $factoryClass = Factory::class) {}

    public function configureNaming(): void
    {
        $factoryClass = $this->factoryClass;
        $factoryClass::guessFactoryNamesUsing(
            static fn (string $modelName): string => 'Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }
}
