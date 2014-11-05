<?php

class Index extends \XI\Controller
{
    public function __construct()
    {
    }

    public function index($a, $b)
    {
        return $this->view('home', ['nama' => "Yakub $a $b"]);
    }
} 