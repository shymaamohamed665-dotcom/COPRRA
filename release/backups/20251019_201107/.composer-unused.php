<?php

use Icanhazstring\Composer\Unused\Configuration\Configuration;
use Icanhazstring\Composer\Unused\Configuration\Exclusion\PackageExclusion;
use Icanhazstring\Composer\Unused\Configuration\AdditionalFiles;

return Configuration::create()
    ->withAdditionalFiles(
        AdditionalFiles::create()
            ->withDirectories([
                'app',
                'config',
                'resources/views',
                'database/migrations',
            ])
    )
    ->withExclusions(
        PackageExclusion::fromList([
            'blade-ui-kit/blade-heroicons',
            'livewire/livewire',
            'spatie/laravel-backup',
            'doctrine/dbal',
        ])
    );
