<?php

namespace XI;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Yakub Kristianto <yakub1986@gmail.com>
 */

class Controller
{
    private $apppath;

    /** @var ContainerInterface */
    protected $container;

    public function setAppPath($apppath)
    {
        $this->apppath = $apppath;
    }

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function view($template, $data)
    {
        $_xi_view = $this->getTemplate($template);
        unset($template);

        extract($data);
        ob_start();
        include($_xi_view);
        $text = ob_get_contents();
        @ob_end_clean();

        return new Response($text);
    }

    private function getTemplate($template)
    {
        if (file_exists($file = "{$this->apppath}view/{$template}.php")) {
            return $file;
        }

        throw new \Exception('Template not found in '.$file);
    }
} 