<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\TestCase as BaseTestCase;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Prepare for Dusk test execution.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->browse(function ($browser) {
            $browser->driver->manage()->deleteAllCookies();
        });
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver()
    {
        $options = (new ChromeOptions)->addArguments([
            '--disable-gpu',
            '--headless',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--disable-extensions',
            '--disable-plugins',
            '--disable-images',
            '--disable-javascript',
            '--window-size=1920,1080',
        ]);

        return RemoteWebDriver::create(
            'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY,
                $options
            )
        );
    }

    /**
     * Determine if the tests should run in headless mode.
     */
    protected function headless(): bool
    {
        return true;
    }
}
