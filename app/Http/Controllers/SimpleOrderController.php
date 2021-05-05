<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use DateTime;

class SimpleOrderController
{
    public function index()
    {
        return view('simple_order.index');
    }

    public function create(){
        return view('simple_order.c');
    }
}
