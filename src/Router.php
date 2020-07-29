<?php
/**
 * @author Shubin Sergei <is.captain.fail@gmail.com>
 * @license GNU General Public License v3.0
 * 07.03.2020 2020
 */

namespace CFGit\ALaMo;


use CFGit\ALaMo\Contracts\ModuleRouter;
use Illuminate\Support\Facades\Route;

class Router implements ModuleRouter
{
    protected $_scope;
    protected $routes = [

    ];
    public function scope($moduleName)
    {
        $this->_scope = $moduleName;
        $this->routes[$moduleName] = $this->routes[$moduleName] ?? [];
        return $this;
    }

    public function route($name, $callback)
    {
        $scope = $this->_scope;
        $this->routes[$scope][] = function () use ($scope, $name, $callback) {
            $config = config(
                "__modules__.routeConfig.{$scope}.{$name}",
                config("__modules__.routeConfig.default.{$name}", false)
            );
            $slug = config(
                "__modules__.slug.{$scope}.".app()->getLocale(),
                config(
                    "__modules__.slug.{$scope}.*",
                    mb_strtolower($scope)
                )
            );
            $config && $slug && Route::group($config, function() use ($slug, $callback) {
                Route::group([
                    'prefix' => $slug,
                    'as' => $slug,
                ], $callback);
            });
        };
    }

    public function apply()
    {
        foreach ($this->routes as $moduleName => $routes) {
            Route::group([
                "namespace" => "\\App\\Module\\{$moduleName}\\Controllers",
            ], function() use ($routes) {
                array_map('call_user_func', $routes);
            });
        }
    }
}
