<?php

/*
 * This file is part of the 'octris/tpl' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Tpl\Extension;

/**
 * Extension bundle base class.
 *
 * @copyright   copyright (c) 2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
abstract class AbstractBundle
{
    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * Return extensions.
     *
     * @return  array
     */
    public function getExtensions()
    {
        return [];
    }

    /**
     * Return constants to add.
     *
     * @return  array
     */
    public function getConstants()
    {
        return [];
    }
}
