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

example2();
echo "\n".memory_get_peak_usage();
echo "\n".(microtime(true) - $time);