<?php

namespace App\Controllers;
use App\Models\DiskonModel;

class Home extends BaseController
{
    public function __construct(){
        $this->diskon = new DiskonModel;
    }

    public function index()
    {
        $data = [
            'diskon' => $this->diskon->findAll()
        ];
        return view('home', $data);
    }

    public function contact()
    {
        return view('contact');
    }
}