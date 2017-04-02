<?php
namespace Opulence\Router;

use InvalidArgumentException;
use Opulence\Router\Matchers\IRouteMatcher;
use Opulence\Router\Matchers\RouteMatcher;

/**
 * Defines the router
 */
class Router implements IRouter
{
    /** @var RouteCollection The list of routes */
    private $routes = null;
    /** @var IRouteMatcher The route matcher */
    private $routeMatcher = null;

    /**
     * @param RouteCollection|array $routes The list of routes
     * @param IRouteMatcher|null $routeMatcher The route matcher
     */
    public function __construct($routes, IRouteMatcher $routeMatcher = null)
    {
        if (is_array($routes)) {
            $this->routes = new RouteCollection();
            $this->routes->addMany($routes);
        } elseif ($routes instanceof RouteCollection) {
            $this->routes = $routes;
        } else {
            throw new InvalidArgumentException('Routes must either be an array or a RouteCollection');
        }

        $this->routes = $routes;
        $this->routeMatcher = $routeMatcher ?? new RouteMatcher();
    }

    /**
     * @inheritdoc
     */
    public function route(string $httpMethod, string $uri, array $headers = [])
    {
        $matchedRoute = null;

        if ($this->routeMatcher->tryMatch($httpMethod, $uri, $headers, $this->routes, $matchedRoute)) {
            return $matchedRoute;
        }

        throw new RouteNotFoundException();
    }
}
