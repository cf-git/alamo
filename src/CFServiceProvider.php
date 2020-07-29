<?php
/**
 * Module created by request special for uamade.ua
 * @author Shubin Sergie <is.captain.fail@gmail.com>
 * @license MIT
 * 08.02.2020 2020
 */

namespace CFGit\ALaMo;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

/**
 * Class ModuleServiceProvider
 * @package App\Module
 *
 * @property array $modules
 * @property Filesystem $fs
 * @property Repository $repository
 */
class CFServiceProvider extends ServiceProvider
{
    protected $fs = null;
    protected $modules = null;
    protected $enabled = null;
    protected $repository = null;

    public function __construct(Application $app)
    {
        parent::__construct($app);
    }

    public function register()
    {
        $this->app->singleton("alamo", function (Application $app) {
            return new ALaMo($app);
        });
        $this->app->singleton("alamo.router", function (Application $app) {
            return new Router;
        });

        $this->fs = $this->app->get('files');
        $this->repository = $this->app->get('config');

        if (!$this->fs->exists(app_path('Module'))) {
            $this->fs->makeDirectory(app_path('Module'));
        }

        $cpl = Str::length(app_path("Module"))+1;
        $this->modules = array_map(function ($path) use ($cpl) {
            return Str::substr($path, $cpl);
        }, $this->fs->directories(app_path('Module')));
        $this->provideModule("Modules");
        foreach ($this->modules as $module) {
            ($module === "Modules") || $this->provideModule($module);
        }
    }

    public function provideModule($module)
    {
        $isModules = $module === "Modules";
        if ($this->fs->exists(app_path("Module/{$module}/module.json"))) {

            if ($this->fs->exists(app_path("Module/{$module}/Module.php"))) {
                alamo()->registerName($module, "\\App\\Module\\{$module}\\Module");
            }
            if (!$isModules) {
                $config = json_decode($this->fs->get(app_path("Module/{$module}/module.json")), 1);
                alamo()->available($module, $config ?? ['en' => ['name' => $module]]);
            }
        }
        foreach (["{$module}ServiceProvider","ModuleServiceProvider","ServiceProvider"] as $providerClass) {
            if ($this->fs->exists(app_path("Module/{$module}/{$providerClass}.php"))) {
                $providerName = "\\App\\Module\\{$module}\\{$providerClass}";
                $this->app->register(new $providerName($this->app));
                break;
            }
        }
        if ($isModules || alamo()->isEnabled($module)) {
            $this->loadJsonTranslationsFrom(app_path("Module/{$module}/resources/lang"));
            $this->loadTranslationsFrom(app_path("Module/{$module}/resources/lang"), $module);
            $this->loadViewsFrom(app_path("Module/{$module}/resources/view"), $module);
            $this->_loadConfigs(app_path("Module/{$module}/config"), $module);
        }
    }

    public function boot()
    {
        if (! $this->app->routesAreCached()) {
            /** @var Router $router */
            $router = app("alamo.router");
            foreach ($this->modules as $module) {
                if (!$this->fs->exists(app_path("Module/{$module}/routes.php"))) continue;
                call_user_func(require app_path("Module/{$module}/routes.php"), $router->scope($module));
            }

            if ($this->app->has("alamo.RouteWrapper")) {
                $this->app->get("alamo.RouteWrapper")(function () use ($router) {
                    $router->apply();
                });
            } else {
                $router->apply();
            }
        }
    }

    protected function _loadConfigs($entryFolder, $module, $prefix = "")
    {
        if ($this->fs->exists($entryFolder)) {
            $configs = $this->fs->files($entryFolder);
            /** @var \Symfony\Component\Finder\SplFileInfo $configFile */
            foreach ($configs as $configFile) {
                $key = $configFile->getBasename('.' . $configFile->getExtension());
                $path = $configFile->getRealPath();
                $this->repository->set("{$module}::{$prefix}{$key}", require $path);
            }
            foreach ($this->fs->directories($entryFolder) as $directory) {
                $prefix = ($prefix ? "{$prefix}" : "");
                $prefix .= trim(mb_substr($directory, mb_strlen($entryFolder)), "/");
                $prefix .= ".";
                $this->_loadConfigs($directory, $module, $prefix);
            }
        }
    }
}
