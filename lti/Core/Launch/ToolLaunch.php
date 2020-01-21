<?php
namespace UBC\LTI\Core\Launch;

use Illuminate\Support\Facades\Log;

use UBC\LTI\Core\Config;

// the main idea is that we supply this object with the params that we receive
// and get the appropriate response params back
class ToolLaunch
{
	public function test()
	{
		Config::$log::debug("$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$");
		return "test";
	}

	// first stage of the LTI launch on the tool side, we need to verify that
	// the platform has sent us all the information we need
	public function verifyLogin()
	{
	}

	// second stage of LTI launch on the tool side, we need to send an auth
	// request back to the platform, this function returns the params that
	// should be sent
	public function getLoginResponse()
	{
	}

	// third stage of the LTI launch on the tool side, we need to verify the
	// authentication response sent back by the platform
	public function verifyAuth()
	{
	}
}
