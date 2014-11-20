<?php

namespace XI\Templating;

use Symfony\Component\HttpFoundation\Response;

class MustacheEngine implements TemplatingInterface
{
    protected $view_path;
    protected $mustache;

    public function __construct(array $config)
    {
        $this->view_path = $config['view_path'];
        $this->initMustache();
    }

    public function initMustache()
    {
        $this->mustache = new \Mustache_Engine([
            // 'template_class_prefix' => '__MyTemplates_',
            // 'cache' => dirname(__FILE__).'/tmp/cache/mustache',
            // 'cache_file_mode' => 0666, // Please, configure your umask instead of doing this :)
            // 'cache_lambda_templates' => true,
            'loader' => new \Mustache_Loader_FilesystemLoader($this->view_path, ['extension' => 'ms']),
            // 'partials_loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__).'/views/partials'),
            // 'helpers' => array('i18n' => function($text) {
                // do something translatey here...
            // }),
            // 'escape' => function($value) {
            //     return htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
            // },
            // 'charset' => 'ISO-8859-1',
            // 'logger' => new Mustache_Logger_StreamLogger('php://stderr'),
            // 'strict_callables' => true,
            // 'pragmas' => [Mustache_Engine::PRAGMA_FILTERS],
        ]);
    }

    /**
     * @param string $file
     * @param array $data
     * @return Response
     */
    public function render($file, array $data)
    {
        $text = $this->mustache->render($file, $data);
        return new Response($text);
    }

}