<?php

/*
 * This file is part of the 'octris/tpl' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Tpl\Extension\Macro;

/**
 * Macro for importing sub-template.
 *
 * @copyright   copyright (c) 2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
final class Import extends \Octris\Tpl\Extension\Macro {
    /**
     * Constructor.
     *
     * @param   string              $name               Name to register extension with.
     * @param   \Octris\Tpl         $tpl                Instance of template engine.
     * @param   array               $options            Optional options.
     */
    public function __construct($name, \Octris\Tpl $tpl, array $options = [])
    {
        $code_gen = function($filename) use ($tpl) {
            $ret = '';

            $c = clone($this->compiler);

            if (($file = $tpl->findFile($filename)) !== false) {
                $ret = $c->process($file, $this->escape);
            } else {
                throw new \Exception(sprintf('Unable to locate file "%s" in "%s"', $filename, implode(':', $tpl->getSearchPath())));
            }

            return $ret;
        };

        parent::__construct($name, $code_gen, [ 'env' => true ] + $options);
    }
}
