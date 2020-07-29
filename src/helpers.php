<?php
/**
 * @author Shubin Sergei <is.captain.fail@gmail.com>
 * @license GNU General Public License v3.0
 * 07.03.2020 2020
 */

use CFGit\ALaMo\ALaMo;
use CFGit\ALaMo\BaseModule;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;

if (!function_exists('alamo')) {
    /***
     * @param null $module
     * @return ALaMo|BaseModule|mixed
     */
    function alamo($module = null)
    {
        static $alamo = null;
        /** @var ALaMo $alamo */
        $alamo = $alamo ?? app('alamo');
        if (is_null($module)) {
            return $alamo;
        }
        return $alamo->get($module);
    }
}

if (!function_exists('module')) {
    function module($module = null) {
        return alamo($module);
    }
}


if (!function_exists('moduleView')) {
    /**
     * Get the evaluated view contents for the given view.
     *
     * @param string|null $view
     * @param Arrayable|array $data
     * @param array $mergeData
     * @return View|Factory
     */
    function moduleView($view = null, $data = [], $mergeData = [])
    {
        $factory = view();
        if (func_num_args() === 0) return $factory;
        $mView = "__modules__." . str_replace("::", ".", $view);
        if ($factory->exists($mView)) $view = $mView;
        return call_user_func_array([$factory, 'make'], [$view, $data, $mergeData]);
    }
}
