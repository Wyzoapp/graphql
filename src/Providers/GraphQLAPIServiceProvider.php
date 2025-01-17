<?php

namespace Wyzo\GraphQLAPI\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Wyzo\GraphQLAPI\WyzoGraphql;
use Wyzo\GraphQLAPI\Console\Commands\Install as InstallGraphQL;
use Wyzo\GraphQLAPI\Facades\WyzoGraphql as WyzoGraphqlFacade;

class GraphQLAPIServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function boot()
    {
        include __DIR__.'/../Http/helpers.php';

        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'wyzo_graphql');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'wyzo_graphql');

        $this->overrideModels();

        $this->publishesDefault();

        $this->app->register(ModuleServiceProvider::class);
    }

    /**
     * Override the existing models
     */
    public function overrideModels()
    {
        // Admin Models
        $this->app->concord->registerModel(
            \Wyzo\User\Contracts\Admin::class,
            \Wyzo\GraphQLAPI\Models\Admin\Admin::class
        );

        // Customer Models
        $this->app->concord->registerModel(
            \Wyzo\Customer\Contracts\Customer::class,
            \Wyzo\GraphQLAPI\Models\Customer\Customer::class
        );

        // Customer Models
        $this->app->concord->registerModel(
            \Wyzo\CartRule\Contracts\CartRule::class,
            \Wyzo\GraphQLAPI\Models\CartRule\CartRule::class
        );
    }

    /**
     * Publish all Default theme page.
     *
     * @return void
     */
    protected function publishesDefault()
    {
        $this->publishes([
            __DIR__.'/../Config/lighthouse.php' => config_path('lighthouse.php'),
        ], ['graphql-api-lighthouse']);
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCommands();

        $this->registerFacades();

        $this->registerConfig();
    }

    /**
     * Register the console commands of this package.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallGraphQL::class,
            ]);
        }
    }

    /**
     * Register Bouncer as a singleton.
     *
     * @return void
     */
    protected function registerFacades()
    {
        $loader = AliasLoader::getInstance();

        $loader->alias('wyzo_graphql', WyzoGraphqlFacade::class);

        $this->app->singleton('wyzo_graphql', function () {
            return app()->make(WyzoGraphql::class);
        });
    }

    /**
     * Register package config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/menu.php',
            'menu.admin'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/acl.php',
            'acl'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/system.php',
            'core'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/auth/guards.php',
            'auth.guards'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/auth/providers.php',
            'auth.providers'
        );
    }
}
