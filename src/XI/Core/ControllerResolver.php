<?php

namespace XI\Core;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

/**
 * @author Yakub Kristianto <yakub1986@gmail.com>
 */

class ControllerResolver implements ControllerResolverInterface
{
    private $apppath;
    private $args;

    public function __construct($apppath)
    {
        $this->apppath = $apppath;
    }

    public function getController(Request $request)
    {
        $pathInfo = explode('/', trim($request->getPathInfo(), '/'));
        empty($pathInfo[0]) && $pathInfo[0] = 'index';
        empty($pathInfo[1]) && $pathInfo[1] = 'index';

        if (file_exists($file = "{$this->apppath}controller/{$pathInfo[0]}.php")) {
            include($file);
            $class = $pathInfo[0];
            $method = $pathInfo[1];
            $this->args = array_slice($pathInfo, 2);
        }

        return [new $class(), $method];
    }

    public function getArguments(Request $request, $controller)
    {
        $r = new \ReflectionMethod($controller[0], $controller[1]);
        $parameters = $r->getParameters();

        $attributes = $request->attributes->all();
        $arguments = array();
        foreach ($parameters as $param) {
            if (array_key_exists($param->name, $attributes)) {
                $arguments[] = $attributes[$param->name];
            } elseif ($param->getClass() && $param->getClass()->isInstance($request)) {
                $arguments[] = $request;
            } elseif ($this->args) {
                $arguments[] = array_shift($this->args);
            } elseif ($param->isDefaultValueAvailable()) {
                $arguments[] = $param->getDefaultValue();
            } else {
                if (is_array($controller)) {
                    $repr = sprintf('%s::%s()', get_class($controller[0]), $controller[1]);
                } elseif (is_object($controller)) {
                    $repr = get_class($controller);
                } else {
                    $repr = $controller;
                }

                throw new \RuntimeException(sprintf('Controller "%s" requires that you provide a value for the "$%s" argument (because there is no default value or because there is a non optional argument after this one).', $repr, $param->name));
            }
        }

        return $arguments;
    }
}
