<?php

namespace TCG\Voyager\Providers;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use TCG\Voyager\Commands\AdminCommand;
use TCG\Voyager\Commands\BreadMigrateCommand;
use TCG\Voyager\Commands\ControllersCommand;
use TCG\Voyager\Commands\InstallCommand;
use TCG\Voyager\Commands\MakeModelCommand;

class ConsoleCommandServiceProvider extends ServiceProvider
{
    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        'Admin' => 'voyager.command.admin',
        'Controllers' => 'voyager.command.controllers',
        'Install' => 'voyager.command.install',
        'BreadMigrate' => 'voyager.command.migrate',
    ];

    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $appCommands = [
        'MakeModel' => 'voyager.command.model',
    ];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->runningInConsole()) {
            $this->registerCommands($this->commands);
        }

        if (!$this->app->runningInConsole() || config('app.env') === 'testing') {
            $this->registerCommands($this->appCommands);
        }
    }

    /**
     * Register the given commands.
     *
     * @param  array  $commands
     * @return void
     */
    protected function registerCommands(array $commands)
    {
        foreach (array_keys($commands) as $command) {
            $this->{"register{$command}Command"}();
        }

        $this->commands(array_values($commands));
    }

    protected function registerAdminCommand()
    {
        $this->app->singleton('voyager.command.admin', function () {
            return new AdminCommand();
        });
    }

    protected function registerControllersCommand()
    {
        $this->app->singleton('voyager.command.controllers', function ($app) {
            return new ControllersCommand($app['files']);
        });
    }

    protected function registerInstallCommand()
    {
        $this->app->singleton('voyager.command.install', function () {
            return new InstallCommand();
        });
    }

    protected function registerBreadMigrateCommand()
    {
        $this->app->singleton('voyager.command.migrate', function ($app) {
            return new BreadMigrateCommand($app['migrator'], $app[Dispatcher::class]);
        });
    }

    protected function registerMakeModelCommand()
    {
        $this->app->singleton('voyager.command.model', function ($app) {
            return new MakeModelCommand();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array_merge(array_values($this->commands), array_values($this->appCommands));
    }
}