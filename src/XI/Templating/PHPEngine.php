<?php

namespace XI\Templating;

use Symfony\Component\HttpFoundation\Response;

class PHPEngine implements TemplatingInterface
{
    protected $view_path;

    public function __construct(array $config)
    {
        $this->view_path = $config['view_path'];
    }

    /**
     * @param string $file
     * @param array $data
     * @return Response
     */
    public function render($file, array $data)
    {
        $_xi_view = $this->getTemplate($file);
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
        if (substr($template, -4) != '.php') {
            $template .= '.php';
        }

        if (file_exists($file = $this->view_path . $template)) {
            return $file;
        }

        throw new \Exception('Template not found in '.$file);
    }

}