<?php

/*
 * This file is part of the 'octris/tpl' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Tpl\Compiler;

use \Octris\Tpl\Compiler as c;

/**
 * Class for defining a template parser grammar.
 *
 * @copyright   copyright (c) 2014-2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Grammar extends \Octris\Parser\Grammar
{
    /**
     * Known tokens.
     *
     * @type    string
     */
    const T_START                = '<syntax>';

    const T_BOOL                 = '<bool>';
    const T_NULL                 = '<null>';
    const T_NUMBER               = '<number>';
    const T_STRING               = '<string>';

    const T_VALUE                = '<value>';
    const T_PARAMETER            = '<parameter>';
    const T_PARAMETER_LIST       = '<parameter-list>';
    const T_MACRO_PARAMETER      = '<macro-parameter>';
    const T_MACRO_PARAMETER_LIST = '<macro-parameter-list>';

    const T_BLOCK                = '<block>';

    const T_CONSTANT             = '<constant>';
    const T_VARIABLE             = '"$..."';

    const T_ARRAY_DEF            = '<array>';
    const T_MACRO_DEF            = '<macro>';
    const T_ESCAPE_DEF           = '<escape>';
    const T_LET_DEF              = '<let>';
    const T_FUNCTION_DEF         = '<function>';
    const T_VARIABLE_DEF         = '<variable>';

    const T_MACRO                = '"@..."';
    const T_FUNCTION               = '"..."';

    const T_BRACE_OPEN           = '"("';
    const T_ESCAPE               = '"escape"';
    const T_LET                  = '"let"';
    const T_BRACE_CLOSE          = '")"';
    const T_PUNCT                = '","';
    const T_ARRAY_OPEN           = '"["';
    const T_ARRAY_CLOSE          = '"]"';
    const T_ARRAY_KEY            = '"=>"';
    const T_WHITESPACE           = '" "';

    const T_IF_OPEN              = '"#if"';
    const T_FOREACH_OPEN         = '"#foreach"';
    const T_FOR_OPEN             = '"#for"';
    const T_IF_ELSE              = '"#else"';
    const T_BLOCK_CLOSE          = '"#end"';
    const T_BLOCK_OPEN           = '"#[a-z][a-z-0-9_]*"';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // define tokens
        $this->addToken(self::T_IF_OPEN, '#if');
        $this->addToken(self::T_IF_ELSE, '#else');
        $this->addToken(self::T_FOREACH_OPEN, '#foreach');
        $this->addToken(self::T_FOR_OPEN, '#for');
        $this->addToken(self::T_BLOCK_CLOSE, '#end');
        $this->addToken(self::T_BLOCK_OPEN, '#[a-zA-Z][a-zA-Z0-9_]*(?=\()');

        $this->addToken(self::T_BRACE_OPEN, '\(');
        $this->addToken(self::T_BRACE_CLOSE, '\)');
        $this->addToken(self::T_PUNCT, '\,');

        $this->addToken(self::T_ESCAPE, 'escape(?=\()');
        $this->addToken(self::T_LET, 'let(?=\()');
        $this->addToken(self::T_FUNCTION, '[a-zA-Z][a-zA-Z0-9_]*(?=\()');
        $this->addToken(self::T_MACRO, '@[a-zA-Z][a-zA-Z0-9_]*(?=\()');

        $this->addToken(self::T_BOOL, '(true|false)');
        $this->addToken(self::T_NULL, 'null');
        $this->addToken(self::T_NUMBER, '[+-]?[0-9]+(\.[0-9]+|)');
        $this->addToken(self::T_STRING, "(?:(?:\"(?:\\\\\"|[^\"])*\")|(?:\'(?:\\\\\'|[^\'])*\'))");

        $this->addToken(self::T_VARIABLE, '\$[a-zA-Z_][a-zA-Z0-9_]*(:\$?[a-zA-Z_][a-zA-Z0-9_]*|:[0-9]+|)+');
        $this->addToken(self::T_CONSTANT, '[A-Z_][A-Z0-9_]*');

        $this->addToken(self::T_ARRAY_OPEN, '\[');
        $this->addToken(self::T_ARRAY_CLOSE, '\]');
        $this->addToken(self::T_ARRAY_KEY, '=>');

        $this->addToken(self::T_WHITESPACE, '\s+');

        // define grammar rules
        $this->addRule(self::T_START, ['$alternation' => [
            self::T_BLOCK,
            self::T_CONSTANT,
            self::T_VARIABLE_DEF,
            self::T_MACRO_DEF,
            self::T_ESCAPE_DEF,
            self::T_LET_DEF,
            self::T_FUNCTION_DEF
        ]], true);

        $this->addRule(self::T_VALUE, ['$alternation' => [
            self::T_BOOL, self::T_NULL, self::T_NUMBER, self::T_STRING
        ]]);
        $this->addRule(self::T_PARAMETER, ['$alternation' => [
            self::T_LET_DEF, self::T_FUNCTION_DEF, self::T_VARIABLE_DEF, self::T_CONSTANT, self::T_VALUE, self::T_ARRAY_DEF
        ]]);
        $this->addRule(self::T_PARAMETER_LIST, ['$option' => [
            ['$concatenation' => [
                self::T_PARAMETER,
                ['$repeat' => [
                    ['$concatenation' => [
                        self::T_PUNCT,
                        self::T_PARAMETER
                    ]]
                ]]
            ]]
        ]]);
        $this->addRule(self::T_MACRO_PARAMETER, ['$alternation' => [
            self::T_CONSTANT, self::T_VALUE
        ]]);
        $this->addRule(self::T_MACRO_PARAMETER_LIST, ['$option' => [
            ['$concatenation' => [
                self::T_MACRO_PARAMETER,
                ['$repeat' => [
                    ['$concatenation' => [
                        self::T_PUNCT,
                        self::T_MACRO_PARAMETER
                    ]]
                ]]
            ]]
        ]]);

        $this->addRule(self::T_ESCAPE_DEF, ['$concatenation' => [
            self::T_ESCAPE,
            self::T_BRACE_OPEN,
            ['$alternation' => [
                self::T_FUNCTION_DEF,
                self::T_VARIABLE_DEF,
                self::T_CONSTANT,
                self::T_STRING
            ]],
            self::T_PUNCT,
            self::T_CONSTANT,
            self::T_BRACE_CLOSE
        ]]);

        $this->addRule(self::T_LET_DEF, ['$concatenation' => [
            self::T_LET,
            self::T_BRACE_OPEN,
            self::T_VARIABLE_DEF,
            self::T_PUNCT,
            self::T_PARAMETER,
            self::T_BRACE_CLOSE
        ]]);

        $this->addRule(self::T_FUNCTION_DEF, ['$concatenation' => [
            self::T_FUNCTION,
            self::T_BRACE_OPEN,
            self::T_PARAMETER_LIST,
            self::T_BRACE_CLOSE
        ]]);

        $this->addRule(self::T_MACRO_DEF, ['$concatenation' => [
            self::T_MACRO,
            self::T_BRACE_OPEN,
            self::T_MACRO_PARAMETER_LIST,
            self::T_BRACE_CLOSE
        ]]);

        $this->addRule(self::T_ARRAY_DEF, ['$concatenation' => [
            self::T_ARRAY_OPEN,
            ['$option' => [
                ['$concatenation' => [
                    ['$option' => [
                        ['$concatenation' => [
                            self::T_STRING,
                            self::T_ARRAY_KEY
                        ]]
                    ]],
                    self::T_PARAMETER,
                    ['$repeat' => [
                        ['$concatenation' => [
                            self::T_PUNCT,
                            ['$option' => [
                                ['$concatenation' => [
                                    self::T_STRING,
                                    self::T_ARRAY_KEY
                                ]]
                            ]],
                            self::T_PARAMETER
                        ]]
                    ]]
                ]]
            ]],
            self::T_ARRAY_CLOSE
        ]]);

        $this->addRule(self::T_VARIABLE_DEF, ['$concatenation' => [ self::T_VARIABLE ]]);

        $this->addRule(self::T_BLOCK, ['$concatenation' => [
            ['$alternation' => [
                ['$concatenation' => [
                    self::T_IF_OPEN,
                    self::T_BRACE_OPEN,
                    self::T_PARAMETER,
                    self::T_BRACE_CLOSE
                ]],
                self::T_IF_ELSE,
                self::T_BLOCK_CLOSE,
                ['$concatenation' => [
                    ['$alternation' => [
                        self::T_FOREACH_OPEN,
                        self::T_FOR_OPEN,
                        self::T_BLOCK_OPEN,
                    ]],
                    self::T_BRACE_OPEN,
                    self::T_PARAMETER_LIST,
                    self::T_BRACE_CLOSE
                ]]
            ]],
        ]]);
    }
}
