<?php

namespace XI;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */

class ControllerResolver implements ControllerResolverInterface
{
    public function getController(Request $request)
    {
        // $pathInfo = $request->getPathInfo();
        // Do CodeIgniter magic here to find controller based on pathInfo
        $controller_class = 'ABC\DEF\MyController';
        return [new $controller_class(), 'second'];
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
