<?php
namespace UBC\LTI\Specs\Launch;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use Jose\Component\Checker\InvalidClaimException;
use Jose\Easy\Build;
use Jose\Easy\JWT;
use Jose\Easy\Load;

use App\Models\Platform;
use App\Models\PlatformClient;

use UBC\LTI\KeyStorage;
use UBC\LTI\LTIException;
use UBC\LTI\Specs\ParamChecker;

// the main idea is that we supply this object with the params that we receive
// and get the appropriate response params back
class ToolLaunch
{
    private Request $request; // laravel request object
    private ParamChecker $checker;

    private bool $hasLogin = false; // true if checkLogin() passed
    private Platform $platform;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->checker = new ParamChecker($request->input());
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

        // check that the request is coming from a platform we know
        $iss = $this->request->input('iss');
        $platform = Platform::where('iss', $iss)->first();
        if (!$platform) throw new LTIException("Unknown platform iss: $iss");
        $this->platform = $platform;
        // make sure that target_link_uri is pointing to us
        $target = $this->request->input('target_link_uri');
        if (strpos($target, config('app.url')) !== 0)
            throw new LTIException("target_link_uri is some other site: $target");

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
            'redirect_uri' => config('lti.auth_resp_url')
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

        // storing values into state reduces the amount of bookkeeping we need
        // to do on our side, so I'm putting values that requires verification
        // against the id_token into the state. To guarantee that the state was
        // generated by us, I'm using the same kind of signed JWT that LTI use
        $resp['state'] = $this->createState();

        // TODO: real nonce security
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
        $state = $this->checkState($this->request->input('state'));
        $idToken = $this->checkIdToken($this->request->input('id_token'), $state);

        // pass data over to the PlatformLaunch using sessions
        // Laravel session automatically split on periods to create a
        // multi-dimensional array, so the lti uri keys in id_token gets split
        // into subarrays. This makes it hard to look up by uri keys, so
        // instead we're storing the id_token in the original serialized form.
        session([
            'original_iss' => $state->claims->get('expected_iss'),
            'login_hint' => $state->claims->get('login_hint'),
            'id_token' => $this->request->input('id_token')
        ]);
    }

    // verify the signature & params in the id_token
    private function checkIdToken(string $token, JWT $state): JWT
    {
        $jwk = KeyStorage::getPlatformPublicKey();
        $jwt = Load::jws($token)
            ->algs(['RS256']) // The algorithms allowed to be used
            ->exp() // We check the "exp" claim
            ->iat(5000) // We check the "iat" claim. Leeway is 5000ms
            ->aud($state->claims->get('client_id')) // Allowed audience
            ->iss($state->claims->get('expected_iss')) // Allowed issuer
            ->key($jwk); // Key used to verify the signature
        try {
            $jwt = $jwt->run();
        } catch(InvalidClaimException $e) {
            throw new LTIException('Invalid id_token: ' . $e->getMessage(),0, $e);
        }

        $requiredValues = [
            'https://purl.imsglobal.org/spec/lti/claim/message_type' =>
                'LtiResourceLinkRequest',
            'https://purl.imsglobal.org/spec/lti/claim/version' => '1.3.0'
        ];
        if ($state->claims->get('lti_deployment_id')) {
            $requireValues['https://purl.imsglobal.org/spec/lti/claim/deployment_id'] = $state->claims->get('lti_deployment_id');
        }
        $checker = new ParamChecker($jwt->claims->all());
        $checker->requireValues($requiredValues);

        return $jwt;
    }

    // verify the signature on state and return the JWT, throws LTIException if
    // signature could not be verified
    private function checkState(string $token): JWT
    {
        $jwk = KeyStorage::getMyPublicKey();
        $jwt = Load::jws($token)
            ->algs(['RS256']) // The algorithms allowed to be used
            ->exp() // We check the "exp" claim
            ->iat(5000) // We check the "iat" claim. Leeway is 5000ms
            ->iss(config('lti.iss'))
            ->key($jwk);
        try {
            $jwt = $jwt->run();
        } catch(InvalidClaimException $e) {
            throw new LTIException('Invalid state in auth response.', 0, $e);
        }
        return $jwt;
    }

    // create a JWT storing params that we expect to also see in id_token
    private function createState(): string
    {
        $jwk = KeyStorage::getMyPrivateKey();
        $time = time();
        // TODO: store login_hint for checking against the id_token
        // TODO: store lti_deployment_id for checking against the id_token
        $jws = Build::jws()
            ->exp($time + 3600)
            ->iat($time)
            ->alg('RS256')
            ->iss(config('lti.iss'))
            ->claim('expected_iss', $this->request->input('iss'))
            ->claim('client_id', $this->request->input('client_id'))
            ->claim('login_hint', $this->request->input('login_hint'));
        if ($this->request->has('lti_deployment_id')) {
            $jws = $jws->claim('lti_deployment_id',
                                $this->request->input('lti_deployment_id'));
        }
        $jws = $jws->sign($jwk);
        return $jws;
    }
}
