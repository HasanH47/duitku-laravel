<?php

namespace Duitku\Laravel\Tests;

use Duitku\Laravel\DuitkuServiceProvider;
use Illuminate\Support\Facades\Http;
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
        $app['config']->set('duitku.user_id', '3551');
        $app['config']->set('duitku.email', 'merchant@example.test');
        Http::preventStrayRequests();
    }
}
