<?php

/*
 * This file is part of the 'octris/tpl' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Tpl\Extension\Fun;

/**
 * Tracks changes of a provided value and returns true if change was detected,
 * otherwise false.
 *
 * @copyright   copyright (c) 2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
final class OnChange extends \Octris\Tpl\Extension\Fun {
    /**
     * Stores state of values.
     *
     * @param   array
     */
    protected $states = [];
    
    /**
     * Constructor.
     */
    public function __construct()
    {
    }
    
    /**
     * Execute onchange.
     * 
     * @param   string      $id         Uniq identifier of event.
     * @param   mixed       $value      Value of observed variable.
     * @return  bool                    Returns true if variable value change was detected.
     */
    public function __invoke($id, $value)
    {
        if (!isset($this->states[$id])) {
            $this->states[$id] = null;
        }

        $return = ($this->states[$id] !== $value);

        $this->states[$id] = $value;

        return $return;
    }
}