<?php
namespace UBC\LTI\Specs\Launch;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use UBC\LTI\Specs\Launch\LoginHandler;
use UBC\LTI\Specs\Launch\AuthReqHandler;
use UBC\LTI\Specs\Launch\AuthRespHandler;
use UBC\LTI\Specs\Launch\MidwayHandler;
use UBC\LTI\Utils\LtiException;
use UBC\LTI\Utils\LtiLog;
use UBC\LTI\Utils\Param;

use App\Models\LtiSession;

/**
 * There's 3 different ways we can do a launch.
 * 1. Regular launch where shim relays requests from the originating platform
 *    to the target tool. The shim acts as both a platform and a tool.
 * 2. Midway only where shim only acts as a tool since we only need access
 *    to midway.
 * 3. Tool only where shim only acts as a platform, launching into target tool.
 *    This is meant for when tools expect launches to have short sessions, so
 *    we need a fresh launch after visiting midway to avoid session issues.
 *
 * The launch is split up into different stages. Each stage has their own
 * handler. The director's job is to call the handlers appropriately.
 */
class LaunchDirector
{
    private LtiLog $ltiLog;
    private Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->ltiLog = new LtiLog('Launch Director');
    }

    /**
     * 1st stage of launch.
     */
    public function login(): Response
    {
        $handler = new LoginHandler($this->request,
                                    $this->ltiLog->getStreamId());
        $handler->recvLogin();
        return $handler->sendLogin();
    }

    /**
     * 2nd stage of launch.
     */
    public function authReq(): Response
    {
        $session = $this->getSession(Param::LOGIN_HINT);
        $handler = new AuthReqHandler($this->request, $session);
        $handler->recvAuth();
        return $handler->sendAuth();
    }

    /**
     * 3rd stage of launch.
     */
    public function authResp(): Response
    {
        $session = $this->getSession(Param::STATE);
        $handler = new AuthRespHandler($this->request, $session);
        $handler->recvAuth();
        return $handler->sendAuth();
    }

    /**
     * Midway for interacting with the shim.
     */
    public function midway(): Response
    {
        $session = $this->getSession(Param::MIDWAY_SESSION);
        $handler = new MidwayHandler($this->request, $session);
        $handler->recv();
        return $handler->send();
    }

    /**
     * Given a param where the LtiSession's encrypted ID should be stored,
     * decode the encrypted ID and get the LtiSession entry. This is necessary
     * because different stages of the launch uses different params to pass
     * state information, which means we store LtiSession under different
     * params for each stage.
     */
    private function getSession(string $sessionParam): LtiSession
    {
        if (!$this->request->has($sessionParam))
            throw new LtiException($this->ltiLog->msg(
                'Missing '. $sessionParam.' in auth request', $this->request));

        $session = LtiSession::decodeEncryptedId(
            $this->request->input($sessionParam));
        $this->ltiLog->setStreamId($session->log_stream);
        return $session;
    }
}
