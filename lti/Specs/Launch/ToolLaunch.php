<?php
namespace UBC\LTI\Specs\Launch;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use App\Models\Platform;
use App\Models\PlatformClient;

use UBC\LTI\LTIException;
use UBC\LTI\Specs\RequestChecker;

// the main idea is that we supply this object with the params that we receive
// and get the appropriate response params back
class ToolLaunch
{
    private Request $request; // laravel request object
    private RequestChecker $checker;

    private bool $hasLogin = false; // true if checkLogin() passed
    private Platform $platform;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->checker = new RequestChecker($request);
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
        $this->checker->requireParams($requiredParams);
        // TODO: check that target_link_uri points to itself

        // check that the request is coming from a platform we know
        $iss = $this->request->input('iss');
        $platform = Platform::where('iss', $iss)->first();
        if (!$platform) throw new LTIException("Unknown platform iss: $iss");
        $this->platform = $platform;
        // TODO: store login_hint for checking against the id_token
        // TODO: store lti_deployment_id for checking against the id_token

        $this->hasLogin = true;
    }

    // second stage of LTI launch on the tool side, we need to send an auth
    // request back to the platform, this function returns the params that
    // should be sent
    public function getLoginResponse(): array
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
        // client_id is either given in the request or stored in the database
        if ($this->request->filled('client_id')) {
            $resp['client_id'] = $this->request->input('client_id');
            // TODO: if this is an unknown client_id, should we add it to the
            // list of known client_id?
        } else {
            $iss = $this->request->input('iss');
            $client = PlatformClient::where('platform_id', $this->platform->id)
                ->first();
            if (!$client) throw new LTIException("No client_id found for $iss");
            $resp['client_id'] = $client->client_id;
        }
        // lti_message_hint needs to be passed as is back to the platform
        if ($this->request->filled('lti_message_hint')) {
            $resp['lti_message_hint'] = $this->request->input('lti_message_hint');
        }

        // TODO: real redirect_uri
        $resp['redirect_uri'] = 'http://localhost/lti/launch/tool/auth';

        // TODO: state is only recommended in auth request but required in auth
        // response, so we should fill in something here, need clarification
        // from spec people
        $resp['state'] = 'blah';

        // TODO: real nonce
        $resp['nonce'] = 'fakenonce';

        return $resp;
    }

    // third stage of the LTI launch on the tool side, we need to check the
    // authentication response sent back by the platform
    public function checkAuth()
    {
        $requiredParams = [
            'state',
            'id_token'
        ];
        $this->checker->requireParams($requiredParams);
        // TODO: validate id_token
        // TODO: return the url to redirect to?
    }
}
