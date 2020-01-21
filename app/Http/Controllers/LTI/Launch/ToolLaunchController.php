<?php

namespace App\Http\Controllers\LTI\Launch;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

class ToolLaunchController extends Controller
{
    /**
     * Handles LTI launch request
     *
     * @param Request $request
     */
    public function login(Request $request) {
		return "LtiLogin";
    }
}

