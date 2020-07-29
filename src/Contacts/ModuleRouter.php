<?php
/**
 * @author Shubin Sergei <is.captain.fail@gmail.com>
 * @license GNU General Public License v3.0
 * 07.03.2020 2020
 */

namespace CFGit\ALaMo\Contracts;

interface ModuleRouter
{
    public function route($name, $callback);

    public function apply();
}
