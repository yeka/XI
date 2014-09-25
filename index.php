<?php
$time = microtime(true);
require_once('vendor/autoload.php');

class ABC
{
	protected $name;
	protected $msg;
	
	public function __construct($name = null, $message = null)
	{
		$this->name = $name;
		$this->msg = $message;
	}

	public function appendMessage($msg)
	{
		$this->msg = $this->msg." ".$msg;
	}

	public function hello()
	{
		$name = $this->name ? : 'I';
		$msg = $this->msg ? : 'hello';
		return "$name say $msg";
	}
}

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

use Symfony\Component\Routing\Loader\YamlFileLoader as YamlRoutingLoader;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

use Symfony\Component\HttpFoundation\Request;

function example1()
{
	// Manual usage - symfony/dependency-injection
	$container = new ContainerBuilder();
	$container->setParameter('name', 'Yakub K.');
	$container->setParameter('message', 'hi');
	$container->register('abc', 'ABC')
		->addArgument('%name%')
		->addArgument('hello kitty')
		->addMethodCall('appendMessage', ['%message%'])
	;

	$abc = $container->get('abc');
	echo $abc->hello();
}

function example2()
{
	// Using loader + symfony/config + symfony/yaml
	$container = new ContainerBuilder();
	$loader = new YamlFileLoader($container, new FileLocator(__DIR__));
	$loader->load('services.yml');

	$abc = $container->get('abc');
	echo $abc->hello();
}

function exampleRouting1()
{
	// look inside *this* directory
	$locator = new FileLocator(array(__DIR__));
	$loader = new YamlRoutingLoader($locator);
	$routes = $loader->load('routes.yml');
	
	$request = Request::createFromGlobals();
	$context = new RequestContext();
	$context->fromRequest($request);

	$matcher = new UrlMatcher($routes, $context);

	$parameters = $matcher->match('/foo');
	print_r($parameters);

	try {
		$parameters = $matcher->matchRequest($request);
		print_r($parameters);
	} catch (\Exception $e) {
		echo "No route match";
	}
}

//example1();
//example2();
exampleRouting1();
echo "\n".number_format(memory_get_peak_usage() / 1024 / 1024, 4).' MB';
echo "\n".number_format(microtime(true) - $time, 4).' s';