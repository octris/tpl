<?php

/*
 * This file is part of the 'octris/tpl' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris;

use \Octris\Tpl\Compiler as compiler;

/**
 * Main class of template engine.
 *
 * @copyright   copyright (c) 2010-2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Tpl
{
    /**
     * Escape types.
     */
    const ESC_NONE        = '';
    const ESC_AUTO        = 'auto';
    const ESC_ATTR        = 'attr';
    const ESC_CSS         = 'css';
    const ESC_HTML        = 'html';
    const ESC_HTMLCOMMENT = 'htmlcomment';
    const ESC_JS          = 'js';
    const ESC_TAG         = 'tag';
    const ESC_URI         = 'uri';

    /**
     * Global template data.
     *
     * @type    array
     */
    protected $data = [];

    /**
     * Configured caching backend.
     *
     * @type    \Octris\Tpl\CacheInterface
     */
    protected $tpl_cache;

    /**
     * Stores pathes to look into when searching for template to load.
     *
     * @type    array
     */
    protected $searchpath = [];

    /**
     * Postprocessors.
     *
     * @type    array
     */
    protected $postprocessors = [];

    /**
     * Extension library.
     *
     * @type    \Octris\Tpl\Library
     */
    protected $library;

    /**
     * Character encoding of template.
     *
     * @type    string
     */
    protected $encoding;

    /**
     * Constructor.
     *
     * @param   string                  $encoding   Character encoding.
     */
    public function __construct($encoding = 'utf-8')
    {
        $this->tpl_cache = new Tpl\Cache\Transient();
        $this->library = new Tpl\Library(new Tpl\Extension\Bundle\Internal($this));
    }

    /**
     * Set caching backend.
     *
     * @param   \Octris\Tpl\Cache      $cache      Instance of caching backend.
     */
    public function setCache(\Octris\Tpl\CacheInterface $cache)
    {
        $this->tpl_cache = $cache;
    }

    /**
     * Set values for multiple template variables.
     *
     * @param   iterable        $array      Key/value array with values.
     */
    public function setValues(iterable $array)
    {
        foreach ($array as $k => $v) {
            $this->setValue($k, $v);
        }
    }

    /**
     * Set value for one template variable. Note, that resources are not allowed as values.
     *
     * @param   string      $name       Name of template variable to set value of.
     * @param   mixed       $value      Value to set for template variable.
     */
    public function setValue($name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * Add a single extension.
     *
     * @param   \Octris\Tpl\Extension\AbstractExtension $extension          A single extension to add.
     */
    public function addExtension(\Octris\Tpl\Extension\AbstractExtension $extension)
    {
        $this->library->addExtension($extension);
    }

    /**
     * Add an extension bundle.
     *
     * @param   \Octris\Tpl\Extension\AbstractBundle    $bundle             Extension bundle to add.
     */
    public function addExtensionBundle(\Octris\Tpl\Extension\AbstractBundle $bundle)
    {
        $this->library->addExtensionBundle($bundle);
    }

    /**
     * Add a post-processor.
     *
     * @param   \Octris\Tpl\PostprocessInterface       $processor          Instance of class for postprocessing.
     */
    public function addPostprocessor($processor)
    {
        $this->postprocessors[] = $processor;
    }

    /**
     * Register pathname for looking up templates in.
     *
     * @param   string|array        $pathname       Name(s) of path to register.
     */
    public function addSearchPath($pathname)
    {
        foreach ((array)$pathname as $path) {
            if (!in_array($path, $this->searchpath)) {
                $this->searchpath[] = $path;
            }
        }
    }

    /**
     * Return list of search pathes.
     *
     * @return  array                       Search pathes.
     */
    public function getSearchPath()
    {
        return $this->searchpath;
    }

    /**
     * Lookup a template file in the configured searchpathes.
     *
     * @param   string      $filename       Name of file to lookup.
     */
    public function findFile($filename)
    {
        $return = false;

        foreach ($this->searchpath as $path) {
            $test = $path . '/' . $filename;

            if (file_exists($test) && is_readable($test)) {
                if (($dir = dirname($filename)) !== '') {
                    // add complete path of file for future relativ path lookups
                    $this->addSearchPath($path . '/' . $dir);
                }

                $return = $test;
                break;
            }
        }

        return $return;
    }

    /**
     * Returns iterator for iterating over all templates in all search pathes.
     *
     * @return  \ArrayIterator                                  Instance of ArrayIterator.
     */
    public function getTemplatesIterator()
    {
        foreach ($this->searchpath as $path) {
            $len = strlen($path);

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $filename => $cur) {
                $rel = substr($filename, $len);

                yield $rel => $cur;
            }
        }
    }

    /**
     * Executes template toolchain -- compiler and compressors.
     *
     * @param   string      $tplname    Filename of template to process.
     * @param   string      $escape     Escaping to use.
     * @param   bool        $force      Force compilation, do not fetch from cache.
     * @return  string                  Processed template.
     */
    protected function process($tplname, $escape, $force = false)
    {
        $uri = $this->tpl_cache->getURI($tplname);

        if ($force || ($tpl = $this->tpl_cache->getContents($uri)) === false) {
            $c = new Tpl\Compiler($this->library);

            if (($filename = $this->findFile($tplname)) !== false) {
                $tpl = $c->process($filename, $escape);

                foreach ($this->postprocessors as $processor) {
                    $tpl = $processor->postProcess($tpl);
                }

                $this->tpl_cache->putContents($uri, $tpl);
            } else {
                die(sprintf(
                    'unable to locate file "%s" in "%s"',
                    $tplname,
                    implode(':', $this->searchpath)
                ));
            }
        }

        return $tpl;
    }

    /**
     * Lint template.
     *
     * @param   string      $filename       Name of template file to compile.
     * @param   string      $escape         Optional escaping to use.
     */
    public function lint($filename, $escape = self::ESC_HTML)
    {
        $inp = ltrim(preg_replace('/\/\/+/', '/', preg_replace('/\.\.?\//', '/', $filename)), '/');

        $c = new Tpl\Lint($this->library);

        if (($filename = $this->findFile($inp)) !== false) {
            $tpl = $c->process($filename, $escape);
        } else {
            die(sprintf(
                'unable to locate file "%s" in "%s"',
                $inp,
                implode(':', $this->searchpath)
            ));
        }
    }

    /**
     * Compile template.
     *
     * @param   string      $filename       Name of template file to compile.
     * @param   string      $escape         Optional escaping to use.
     */
    public function compile($filename, $escape = self::ESC_HTML)
    {
        $this->process($filename, $escape, true);
    }

    /**
     * Check if a template exists.
     *
     * @param   string      $filename       Filename of template to check.
     * @return  bool                        Returns true if template exists.
     */
    public function templateExists($filename)
    {
        $inp = ltrim(preg_replace('/\/\/+/', '/', preg_replace('/\.\.?\//', '/', $filename)), '/');

        return (($filename = $this->findFile($inp)) !== false);
    }

    /**
     * Return an instance of a template sandbox to render.
     *
     * @param   string      $filename       Filename of template to render.
     * @param   string      $escape         Optional escaping to use.
     * @return  \Octris\Tpl\Sandbox
     */
    public function getSandbox($filename, $escape = self::ESC_HTML)
    {
        $tpl = $this->process($filename, $escape);

        return new Tpl\Sandbox($this->library, $this->encoding, $filename, $tpl, $this->data);
    }
}
