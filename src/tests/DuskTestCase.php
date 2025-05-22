<?php

namespace Tests;

use Laravel\Dusk\TestCase as BaseTestCase;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication;

    protected static $chromeDriverProcess;
    /**
     * Dusk テストの実行準備を行います。
     *
     * @beforeClass
     * @return void
     */
    public static function prepare(): void
    {
        // static::startChromeDriver();
    }

    protected function driver(): RemoteWebDriver
    {
        $options = [];

        $options = (new \Facebook\WebDriver\Chrome\ChromeOptions)
            ->addArguments([
                '--window-size=1920,1080',
                '--disable-gpu',
                '--headless',
                '--no-sandbox',
                '--disable-dev-shm-usage',
            ]);

        if (env('CHROME_BIN')) {
            $options->setBinary(env('CHROME_BIN'));
        }

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }
}
