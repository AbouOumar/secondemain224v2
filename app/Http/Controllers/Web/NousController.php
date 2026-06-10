<?php
namespace App\Http\Controllers\Web;
use App\Http\Controllers\Controller;

class NousController extends Controller
{
    public function index()
    {
        return view('nous');
    }
}
