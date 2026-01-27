<?php

namespace Duitku\Laravel\Tests;

use Duitku\Laravel\DuitkuServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            DuitkuServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('duitku.merchant_code', 'TEST_MERCHANT');
        $app['config']->set('duitku.api_key', 'TEST_API_KEY');
        $app['config']->set('duitku.sandbox_mode', true);
    }
}
