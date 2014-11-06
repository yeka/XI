<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class Index extends \XI\Controller
{
    public function __construct()
    {
    }

    public function index(Request $request)
    {
        echo $request->getClientIp()."<br/>";
        echo $request->headers->get('User-Agent')."<br/>";
        $session = new Session();
        $session->start();
        $a = $session->getFlashBag()->get('something');
        print_r($a);
        $session->getFlashBag()->add('something', 'abuba');
    }

    public function database($a = 'a', $b = 'b')
    {
        $db_config = [
            'hostname' => 'localhost',
            'username' => 'root',
            'password' => 'usbw',
            'database' => 'sebarundangan',
            'dbdriver' => 'mysql',
            'dbprefix' => '',
            'pconnect' => TRUE,
            'db_debug' => TRUE,
            'cache_on' => FALSE,
            'cachedir' => '',
            'char_set' => 'utf8',
            'dbcollat' => 'utf8_general_ci',
            'swap_pre' => '',
            'autoinit' => TRUE,
            'stricton' => FALSE,
        ];

        $model = new \XI\Database\MySQL\Driver($db_config);
        $query = $model->get('user', 2, 0);
        foreach ($query->result() as $row) {
            print_r($row);
        }

        return $this->view('home', ['nama' => "Yakub $a $b"]);
    }
}