<?php

/*
 * Opulence
 *
 * @link      https://www.opulencephp.com
 * @copyright Copyright (C) 2019 David Young
 * @license   https://github.com/opulencephp/Opulence/blob/master/LICENSE.md
 */

namespace Opulence\Routing\Tests;

use Closure;
use InvalidArgumentException;
use Opulence\Routing\RouteAction;
use PHPUnit\Framework\TestCase;

/**
 * Tests the route action
 */
class RouteActionTest extends TestCase
{
    /** @const The name of the class used in our method action */
    private const CLASS_NAME = 'Foo';
    /** @const The name of the method used in our method action */
    private const METHOD_NAME = 'bar';
    /** @var RouteAction An instance that uses a closure as the action */
    private $closureAction;
    /** @var Closure The closure used in the closure action */
    private $closure;
    /** @var RouteAction An instance that uses a method as the action */
    private $methodAction;

    public function setUp(): void
    {
        $this->closure = function () {
            // Don't do anything
        };
        $this->closureAction = new RouteAction(null, null, $this->closure);
        $this->methodAction = new RouteAction(self::CLASS_NAME, self::METHOD_NAME, null);
    }

    public function testConstructorOnNullArguments(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Must specify either a class name or closure');

        new RouteAction(null, null, null);
    }

    public function testCorrectClassNameIsReturned(): void
    {
        $this->assertEquals(self::CLASS_NAME, $this->methodAction->className);
    }

    public function testCorrectMethodNameIsReturned(): void
    {
        $this->assertEquals(self::METHOD_NAME, $this->methodAction->methodName);
    }

    public function testCorrectClosureInstanceIsReturned(): void
    {
        $this->assertSame($this->closure, $this->closureAction->closure);
    }

    public function testMethodFlagSetCorrectly(): void
    {
        $this->assertFalse($this->closureAction->usesMethod());
        $this->assertTrue($this->methodAction->usesMethod());
    }

    public function testNullClosureIsReturnedByMethodAction(): void
    {
        $this->assertNull($this->methodAction->closure);
    }
}
