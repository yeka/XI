<?php

namespace XI\Templating;

use Symfony\Component\HttpFoundation\Response;

interface TemplatingInterface
{
    /**
     * @param string $file
     * @param array $data
     * @return Response
     */
    public function render($file, array $data);

}