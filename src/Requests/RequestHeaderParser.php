<?php

/**
 * Aphiria
 *
 * @link      https://www.aphiria.com
 * @copyright Copyright (C) 2023 David Young
 * @license   https://github.com/aphiria/aphiria/blob/1.x/LICENSE.md
 */

declare(strict_types=1);

namespace Aphiria\Routing\Requests;

/**
 * Defines a parser that reads the request headers from the $_SERVER super global
 */
final class RequestHeaderParser
{
    /** @var array<string, true> These headers do not have the HTTP_ prefix */
    private static array $specialCaseHeaders = [
        'AUTH_TYPE' => true,
        'CONTENT_LENGTH' => true,
        'CONTENT_TYPE' => true,
        'PHP_AUTH_DIGEST' => true,
        'PHP_AUTH_PW' => true,
        'PHP_AUTH_TYPE' => true,
        'PHP_AUTH_USER' => true
    ];

    /**
     * Parses headers from the $_SERVER super global
     *
     * @param array<string, mixed> $server The $_SERVER super global
     * @return array<string, mixed> The mapping of header names => values
     */
    public function parseHeaders(array $server): array
    {
        $headers = [];

        /** @psalm-suppress MixedAssignment The value could legitimately be mixed */
        foreach ($server as $key => $value) {
            $uppercasedKey = \strtoupper($key);

            if (isset(self::$specialCaseHeaders[$uppercasedKey]) || \str_starts_with($uppercasedKey, 'HTTP_')) {
                $value = (array)$value;
                $headers[self::normalizeName($key)] = $value;
            }
        }

        return $headers;
    }

    /**
     * Normalizes a name
     *
     * @param string $name The name to normalize
     * @return string The normalized name
     */
    private static function normalizeName(string $name): string
    {
        $dashedName = \str_replace('_', '-', $name);

        if (\stripos($dashedName, 'HTTP-') === 0) {
            $dashedName = \substr($dashedName, 5);
        }

        return $dashedName;
    }
}
