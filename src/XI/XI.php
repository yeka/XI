<?php

namespace XI;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernel;

class XI
{
    protected $container;

    public function __construct($app_path)
    {
        $container = new ContainerBuilder();
        $this->container = $container;

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__));
        $loader->load($app_path.'config/config.yml');
        $loader->load($this->getFileLocation().'Resources/config.yml');

        $container->setParameter('app_path', $app_path);
        $container->compile();
    }

    public function run()
    {
        $request = Request::createFromGlobals();

        /** @var HttpKernel $kernel */
        $kernel = $this->container->get('kernel');

        $response = $kernel->handle($request);
        $response->send();

        $kernel->terminate($request, $response);
    }

    public function getFileLocation()
    {
        return __DIR__.'/';
    }
} 