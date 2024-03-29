<?php

/**
 * Aphiria
 *
 * @link      https://www.aphiria.com
 * @copyright Copyright (C) 2023 David Young
 * @license   https://github.com/aphiria/aphiria/blob/1.x/LICENSE.md
 */

declare(strict_types=1);

namespace Aphiria\Routing\UriTemplates\Compilers\Tries;

/**
 * Defines a root node of a trie
 */
final class RootTrieNode extends TrieNode
{
    /**
     * @inheritdoc
     */
    public function __construct(array $children = [])
    {
        /** @psalm-suppress ArgumentTypeCoercion Psalm does not pass array types via inheritdoc (#4504) - bug */
        parent::__construct($children, [], null);
    }
}
