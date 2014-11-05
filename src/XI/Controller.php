<?php

namespace XI;

use Symfony\Component\HttpFoundation\Response;

class Controller
{
    private $apppath;

    public function setAppPath($apppath)
    {
        $this->apppath = $apppath;
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