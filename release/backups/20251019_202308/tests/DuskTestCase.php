<?php

declare(strict_types=1);

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;

class DuskTestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        // Skip starting local ChromeDriver if an external driver URL is provided
        $driverUrl = $_ENV['DUSK_DRIVER_URL'] ?? ($_SERVER['DUSK_DRIVER_URL'] ?? null);
        if (is_string($driverUrl) && $driverUrl !== '') {
            return;
        }

        if (! static::runningInSail()) {
            static::startChromeDriver(['--port=9515']);
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments(collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
            '--ignore-certificate-errors',
            '--ignore-ssl-errors',
            '--ignore-certificate-errors-spki-list',
            '--disable-web-security',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        $driverUrl = $_ENV['DUSK_DRIVER_URL'] ?? config('dusk.driver_url') ?? 'http://localhost:9515';
        $driverUrl = is_string($driverUrl) ? $driverUrl : 'http://localhost:9515';

        return RemoteWebDriver::create(
            $driverUrl,
            DesiredCapabilities::chrome()
                ->setCapability(ChromeOptions::CAPABILITY, $options)
                ->setCapability('acceptInsecureCerts', true)
        );
    }
}
