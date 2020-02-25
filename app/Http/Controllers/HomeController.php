<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function home()
    {
        return view('welcome');
    }
}
