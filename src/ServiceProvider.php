<?php

namespace MxFrame\Tracy;

// Project
use App\Http\Kernel as AppKernel;

// Laravel
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Contracts\View\Factory as View;
use Illuminate\Foundation\Http\Kernel as LaravelKernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Factory as ViewFactory;

// MxFrame
//use MxFrame\Terminal\TerminalServiceProvider;
use MxFrame\Tracy\Exceptions\Handler;
use MxFrame\Tracy\Middleware\RenderBar;
use MxFrame\Tracy\Managers\BarManager;
use MxFrame\Tracy\Managers\DebuggerManager;

// MxFrame - Plugins
use Tracy\Bar;
use Tracy\BlueScreen;
use Tracy\Debugger;

class ServiceProvider extends LaravelServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * The controller namespace.
     *
     * @var string
     */
    protected $namespace = 'MxFrame\Tracy\Http\Controllers';

    /**
     * Boot the service.
     *
     * @param Kernel|LaravelKernel|AppKernel $kernel The Laravel kernel
     * @param View|ViewFactory               $view   The Laravel view
     * @param Router                         $router The Laravel router
     */
    public function boot(Kernel $kernel, View $view, Router $router)
    {
        // Check if running in console
        if ($this->app->runningInConsole() === true) {
            // Publish the config
            $this->publishes([__DIR__ . '/../config/tracy.php' => config_path('tracy.php')], 'config');

            // Leave the boot method
            return;
        }

        // Get the config
        $config = config('tracy');

        // Check if remote debug is enabled
        $remoteDebug = false;
        if (true === Arr::get($config, 'remote_debug', false)) {
            // Remote Debug is enabled, so check the headers
            $remoteDebugHeader = Arr::get($config, 'remote_debug_header', null);

            // Define the remote developer
            $remoteDeveloper = null;

            // Check the header
            if (! empty($remoteDebugHeader) &&
                ! empty($headerName = Arr::get($remoteDebugHeader, 'developer_name_tag', null)) &&
                ! empty($headerValues = Arr::get($remoteDebugHeader, 'developer_name_values', [])) &&
                is_array($headerValues)
            ) {
                // Check the current User Header with $_SERVER
                $serverHeaderName = 'HTTP_' . strtoupper(str_replace('-', '_', $headerName));
                if (! empty($_SERVER) && ! empty($_SERVER[$serverHeaderName]) && in_array($_SERVER[$serverHeaderName], $headerValues)) {
                    $remoteDeveloper = $_SERVER[$serverHeaderName];
                } else {
                    // Try it with the getallheaders()-function
                    $headers = getallheaders();
                    $headerNameTag = strtolower($headerName);
                    if (! empty($headers) && ! empty($headers[$headerNameTag]) && in_array($headers[$headerNameTag], $headerValues)) {
                        $remoteDeveloper = $headerValues[$headerNameTag];
                    }
                }
            }

            // Check the remote developer
            if (! empty($remoteDeveloper)) {
                // Get the allowed ips
                if (! empty($allowedIps = Arr::get($remoteDebugHeader, 'allowed_ips', []))) {
                    $ip = mx_get_client_ip();
                    if (null === $ip || ! in_array($allowedIps, $ip)) {
                        $remoteDeveloper = null;
                    }
                }
            }

            // Check the remote developer again
            if (! empty($remoteDeveloper)) {
                // Enable remote debug
                $remoteDebug = true;
            }
        }

        // Check if tracy is enabled
        if (false === $remoteDebug && false === Arr::get($config, 'enabled', true)) {
            // Leave the method, because debug is disabled
            return;
        }

        // Check if the Exception should be displayed by tracy
        if (true === $remoteDebug || true === Arr::get($config, 'show_exception', true)) {
            $this->app->extend(ExceptionHandler::class, function ($exceptionHandler, $app) {
                return new Handler($exceptionHandler, $app[DebuggerManager::class]);
            });
        }

        // Check if the Tracy Bar should be displayed
        if (true === $remoteDebug || true === Arr::get($config, 'show_bar', true)) {
            $this->handleRoutes($router, Arr::get($config, 'route', []));
            $kernel->prependMiddleware(RenderBar::class);
        }
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        // Merge the configs
        $this->mergeConfigFrom(__DIR__ . '/../config/tracy.php', 'tracy');

        // Get the merged config
        $config = Arr::get($this->app['config'], 'tracy');

//        if (Arr::get($config, 'panels.terminal') === true) {
//            $this->app->register(TerminalServiceProvider::class);
//        }

        // Register the BlueScreen
        $this->app->singleton(BlueScreen::class, function () {
            return Debugger::getBlueScreen();
        });

        // Register the Debug Bar
        $this->app->singleton(Bar::class, function ($app) use ($config) {
            $barManager = new BarManager(Debugger::getBar(), $app['request'], $app);
            return $barManager
                ->loadPanels(Arr::get($config, 'panels', []))
                ->getBar();
        });

        // Register the Debug Manager
        $this->app->singleton(DebuggerManager::class, function ($app) use ($config) {
            return (new DebuggerManager(
                DebuggerManager::init($config),
                $app[Bar::class],
                $app[BlueScreen::class],
                new Session
            ))->setUrlGenerator($app['url']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [ExceptionHandler::class];
    }

    /**
     * register routes.
     *
     * @param \Illuminate\Routing\Router $router
     * @param array                      $config
     */
    protected function handleRoutes(Router $router, $config = [])
    {
        if ($this->app->routesAreCached() === false) {
            $router->group(array_merge([
                'namespace' => $this->namespace,
            ], $config), function (Router $router) {
                require __DIR__ . '/../routes/web.php';
            });
        }
    }
}