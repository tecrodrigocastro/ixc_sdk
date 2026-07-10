<?php

namespace RedRodrigo\IxcSdk\Laravel;

use Illuminate\Support\ServiceProvider;
use RedRodrigo\IxcSdk\Contracts\HttpClientInterface;
use RedRodrigo\IxcSdk\Http\CachingHttpClient;
use RedRodrigo\IxcSdk\Http\GuzzleHttpClient;
use RedRodrigo\IxcSdk\IxcClient;

class IxcServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/ixc.php', 'ixc');

        $this->app->singleton(HttpClientInterface::class, function ($app) {
            $config = $app['config']['ixc'];

            $http = new GuzzleHttpClient(
                baseUrl: (string) $config['base_url'],
                userId: (string) $config['user_id'],
                token: (string) $config['token'],
            );

            if (empty($config['cache']['enabled'])) {
                return $http;
            }

            return new CachingHttpClient(
                inner: $http,
                cache: $app['cache']->store($config['cache']['store'] ?? null),
                ttl: (int) ($config['cache']['ttl'] ?? 300),
            );
        });

        $this->app->singleton(IxcClient::class, fn ($app) => new IxcClient(
            $app->make(HttpClientInterface::class)
        ));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/ixc.php' => $this->app->configPath('ixc.php'),
            ], 'ixc-config');
        }
    }
}
