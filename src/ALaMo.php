<?php
/**
 * @author Shubin Sergei <is.captain.fail@gmail.com>
 * @license MIT
 * 07.03.2020 2020
 */

namespace CFGit\ALaMo;


use CFGit\ALaMo\Contracts\ModuleAccessor;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Foundation\Application;
use Brick\VarExporter\VarExporter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ALaMo
{
    /** @var Application */
    protected $app;
    /** @var Filesystem */
    protected $fs;
    protected $modules = [];
    protected $available = [];
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->fs = $app->get('files');
    }

    public function setMainModule($module)
    {
        $m = $this->get($module);
        if ($m && $m->canBeMain()) {
            $this->register('main', $m, true);
        }
    }

    /**
     * @param $module
     * @param $config
     * @param $refresh
     * @return bool
     */
    public function available($module, $config = [], $refresh = false)
    {
        if (!isset($this->available[$module]) || $refresh) {
            $this->available[$module] = $config;
            return true;
        }
        return false;
    }

    public function getAvailableList()
    {
        return array_map(function ($i) {
            return $i[$this->app->getLocale()] ?? $i["en"];
        }, $this->available);
    }

    public function registerName(string $module, $accessorClassName, $refresh = false) {
        if (is_a($accessorClassName, ModuleAccessor::class, true)) {
            $accessor =  new $accessorClassName($this->app);
            $this->register($module, $accessor, $refresh);
            return $accessor;
        }
    }

    /**
     * @param string $module - module name
     * @param ModuleAccessor $accessor - module instance
     * @param bool $refresh - flag to rewrite accessor if is already exist
     * @return bool - true if success registred, false an else
     */
    public function register(string $module, ModuleAccessor $accessor, $refresh = false)
    {
        if (!isset($this->modules[$module]) || $refresh) {
            $this->modules[$module] = $accessor;
            $accessor->setLayout(config("__modules__.layouts.{$module}", config("__modules__.layouts.*", "layouts.app")));
            return true;
        }
        return false;
    }

    public function isEnabled($module)
    {
        return isset($this->modules[$module]) && $this->isInstalled($module) && !$this->isDisabled($module);
    }

    public function isDisabled($module)
    {
        return in_array($module, config("__modules__.disabled", []));
    }

    public function isInstalled($module)
    {
        return in_array($module, config("__modules__.installed", []));
    }

    protected function _storeConfig($config) {
        $this->fs->put(
            config_path("__modules__.php"),
            "<?php\n".VarExporter::export($config, VarExporter::ADD_RETURN)
        );
        Artisan::call("config:cache");
    }

    public function _removeFromConfig(&$config, $module)
    {
        if (($inx = array_search($module, $config)) !== false) {
            unset($config[$inx]);
            return true;
        }
        return false;
    }

    public function _putToConfig(&$config, $module)
    {
        if (!in_array($module, $config)) {
            $config[] = $module;
            return true;
        }
        return false;
    }


    /**
     * Resolve a migration instance from a file.
     *
     * @param  string  $file
     * @return object
     */
    protected function _resolve($file)
    {
        $class = Str::studly(implode('_', array_slice(explode('_', $file), 4)));

        return new $class;
    }

    protected function _migrateFile($method, $file, $batch) {
        if (Str::endsWith($file, ".php")) {
            require_once $file;
            /** @var Migration $migration */
            $name = Str::substr($file, 0, -4);
            $migration = $this->_resolve($name);
            $name = basename($name);
            if ($method === "up") {
                $migration->up();
                DB::table("migrations")->insert([
                    "migration" => $name,
                    "batch" => $batch,
                ]);
            } else {
                $migration->down();
                DB::table("migration")->delete([
                    "migration" => $name,
                ]);
            }
        }
    }

    protected function _migrate($module, $method = "up")
    {
        if (!in_array($method, ["up", "down"]))
        if (!Schema::hasTable("migrations")) return; //Database absent or empty
        $path = app_path("Module/{$module}/database/migrations");
        if ($this->fs->exists($path)) {
            $files = $this->fs->files($path);
            if (empty($files)) return;
            $batch = (DB::table("migrations")->max("batch")??0) + 1;
            foreach ($files as $file) {
                $this->_migrateFile($method, $file, $batch);
            }
        }
    }

    public function install($module)
    {
        if ($m = $this->get($module)) {
            $config = config('__modules__');
            $config['installed'] = is_array($config['installed']) ? $config['installed']: [];
            $config['disabled'] = is_array($config['disabled']) ? $config['disabled']: [];
            if ($this->_putToConfig($config['installed'], $module)) {
                !$m->defaultEnabled() && $this->_putToConfig($config['disabled'], $module);
                $this->_migrate($module, "up");
                $this->_storeConfig($config);
                $m->install();
            }
        }
    }

    public function remove($module)
    {
        if ($m = $this->get($module)) {
            $config = config('__modules__');
            $config['installed'] = is_array($config['installed']) ? $config['installed']: [];
            $config['disabled'] = is_array($config['disabled']) ? $config['disabled']: [];
            if ($this->_removeFromConfig($config['disabled'], $module) &&
                $this->_removeFromConfig($config['installed'], $module)) {
                $m->remove();
                $this->_migrate($module, "down");
                $this->_storeConfig($config);
            }
        }
    }

    public function enable($module)
    {
        if($m = $this->get($module)) {
            $config = config('__modules__');
            $config['installed'] = is_array($config['installed']) ? $config['installed']: [];
            $config['disabled'] = is_array($config['disabled']) ? $config['disabled']: [];
            if ($this->_removeFromConfig($config['disabled'], $module)) {
                $m->enable();
                $this->_storeConfig($config);
            }
        }
    }

    public function disable($module)
    {
        if($m = $this->get($module)) {
            $config = config('__modules__');
            $config['installed'] = is_array($config['installed']) ? $config['installed']: [];
            $config['disabled'] = is_array($config['disabled']) ? $config['disabled']: [];
            if ($this->_putToConfig($config['disabled'], $module)) {
                $m->disable();
                $this->_storeConfig($config);
            }
        }
    }

    /**
     * @param $module - module name
     * @return BaseModule|null
     */
    public function get($module)
    {
        return $this->modules[$module] ?? null;
    }
}
