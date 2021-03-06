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
 * Class for building block extensions.
 *
 * @copyright   copyright (c) 2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Block extends \Octris\Tpl\Extension\AbstractExtension {
    /**
     * Code generator.
     *
     * @param   array               $args               Function arguments definition.
     * @param   array               $env                Engine environment.
     * @return  array                                   Template code for head and foot.
     */
    final public function getCode(array $args, array $env)
    {
        list($head, $foot) = $this->getFn($env)(...$args);
        
        if (__CLASS__ != static::class) {
            $head = '$this->library[static::class]->head(' . $head . ')';
            $foot = '$this->library[static::class]->foot(' . $foot . ')';
        }

        return [ $head, $foot ];
    }
}
