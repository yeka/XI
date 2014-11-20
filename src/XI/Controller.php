<?php

namespace XI;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use XI\Templating\TemplatingInterface;

/**
 * @author Yakub Kristianto <yakub1986@gmail.com>
 */

class Controller
{
    private $apppath;

    /** @var TemplatingInterface */
    private $templating;

    /** @var ContainerInterface */
    protected $container;

    public function setAppPath($apppath)
    {
        $this->apppath = $apppath;
    }

    public function setTemplateEngine(TemplatingInterface $template)
    {
        $this->templating = $template;
    }

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function view($template, $data)
    {
        return $this->container->get('templating')->render($template, $data);
    }

}