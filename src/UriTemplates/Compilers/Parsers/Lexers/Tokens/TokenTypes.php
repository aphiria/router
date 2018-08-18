<?php

/*
 * Opulence
 *
 * @link      https://www.opulencephp.com
 * @copyright Copyright (C) 2017 David Young
 * @license   https://github.com/opulencephp/route-matcher/blob/master/LICENSE.md
 */

namespace Opulence\Routing\UriTemplates\Compilers\Parsers\Lexers\Tokens;

/**
 * Defines the various token types
 */
class TokenTypes
{
    /** @const A text token type */
    public const T_TEXT = 'T_TEXT';
    /** @const A number token type */
    public const T_NUMBER = 'T_NUMBER';
    /** @const A punctuation token type */
    public const T_PUNCTUATION = 'T_PUNCTUATION';
    /** @const A quoted string token type */
    public const T_QUOTED_STRING = 'T_QUOTED_STRING';
    /** @const A variable token type */
    public const T_VARIABLE = 'T_VARIABLE';
}