<?php
/**
 * @author Shubin Sergei <is.captain.fail@gmail.com>
 * @license MIT
 * 07.03.2020 2020
 */

namespace CFGit\ALaMo;

use CFGit\ALaMo\Contracts\ModuleAccessor;
use Illuminate\Contracts\Foundation\Application;

abstract class BaseModule implements ModuleAccessor
{
    protected $app = null;
    protected $layout = null;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @param string $layout
     * @return $this|ModuleAccessor
     */
    public function setLayout(string $layout)
    {
        $this->layout = $layout;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * @return bool
     */
    public function canBeMain()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function defaultEnabled()
    {
        return true;
    }

    public function enable()
    {

    }

    public function disable()
    {

    }
}
