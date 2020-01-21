<?php
namespace UBC\LTI\Specs\Launch;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use UBC\LTI\LTIException;


// the main idea is that we supply this object with the params that we receive
// and get the appropriate response params back
class ToolLaunch
{
	private Request $request; // laravel request object

	private bool $hasLogin = false; // true if checkLogin() passed

	public function __construct(Request $request)
	{
		$this->request = $request;
	}

	// first stage of the LTI launch on the tool side, we need to check that
	// the platform has sent us all the information we need
	public function checkLogin()
	{
		$requiredParams = [
			'iss',
			'login_hint',
			'target_link_uri'
		];
		$this->checkRequiredParameters($requiredParams);
		// TODO: check that iss is a known platform
		// TODO: check that target_link_uri points to itself
		// TODO: if client_id exists, check that it is known is under the iss
		// TODO: store login_hint for checking against the id_token
		// TODO: store lti_deployment_id for checking against the id_token

		$this->hasLogin = true;
	}

	// second stage of LTI launch on the tool side, we need to send an auth
	// request back to the platform, this function returns the params that
	// should be sent
	public function getLoginResponse()
	{
		// cannot generate the login response if we don't have a valid login
		if (!$this->hasLogin) $this->checkLogin();
		$resp = [
			'scope' => 'openid',
			'response_type' => 'id_token',
			'login_hint' => $this->request->input('login_hint'),
			'response_mode' => 'form_post',
			'prompt' => 'none',
		];

		if ($this->request->filled('client_id'))
		{
			$resp['client_id'] = $this->request->input('client_id');
		}
		else
		{
			// TODO: retrieve client_id based on iss
		}

		if ($this->request->filled('lti_message_hint'))
		{
			$resp['lti_message_hint'] = $this->request->input('lti_message_hint');
		}

		// TODO: redirect_uri
		// TODO: real nonce

		$resp['nonce'] = 'fakenonce';

		return $resp;
	}

	// third stage of the LTI launch on the tool side, we need to check the
	// authentication response sent back by the platform
	public function checkAuth()
	{
		// unlike login, only POST requests are allowed for the auth response
		if (!$this->isMethod('post'))
		{
			throw new LTIException(
				'Authentication response must be a POST request');
		}
		$requiredParams = [
			'state',
			'id_token'
		];
		$this->checkRequiredParameters($requiredParams);
		// TODO: validate id_token
		// TODO: return the url to redirect to?
	}

	// given a list of required parameters, make sure that the request has those
	// params
	private function checkRequiredParameters(array $requiredParams)
	{
		foreach ($requiredParams as $requiredParam)
		{
			if (!$this->request->filled($requiredParam))
			{
				throw new LTIException(
					"Missing required parameter '$requiredParam'");
			}
		}
	}
}
