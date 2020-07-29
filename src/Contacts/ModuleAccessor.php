<?php
/**
 * @author Shubin Sergei <is.captain.fail@gmail.com>
 * @license GNU General Public License v3.0
 * 07.03.2020 2020
 */

namespace CFGit\ALaMo\Contracts;

interface ModuleAccessor
{

    public function remove();

    public function install();

    public function enable();

    public function disable();


    /**
     * @param string $layout
     * @return $this
     */
    public function setLayout(string $layout);

    /**
     * @return string
     */
    public function getLayout();

    /**
     * @return bool
     */
    public function canBeMain();

    /**
     * @return bool
     */
    public function defaultEnabled();
}
