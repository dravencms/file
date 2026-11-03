<?php declare(strict_types=1);
namespace Dravencms\File;
use Dravencms\Application\IRouterFactory;
use Nette\Application\Routers\RouteList;
final class RouteFactory implements IRouterFactory
{
    public function createRouter(): RouteList
    {
        $routes = new RouteList();
        $routes->addRoute('files/<provider [a-z][a-z0-9.-]*>/<reference [a-zA-Z0-9_-]+>/<sum [a-f0-9]{32,64}>/<variant [a-zA-Z0-9_-]+>', 'Files:File:default');
        return $routes;
    }
}
