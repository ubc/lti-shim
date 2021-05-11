<?php
namespace UBC\LTI\Specs\DeepLink;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use Jose\Easy\Build;
use Jose\Easy\JWT;
use Jose\Easy\Load;

use App\Models\CourseContext;
use App\Models\Deployment;
use App\Models\LtiFakeUser;
use App\Models\LtiRealUser;
use App\Models\LtiSession;
use App\Models\Platform;

use UBC\LTI\Specs\JwsUtil;
use UBC\LTI\Specs\Launch\Filters\AgsFilter;
use UBC\LTI\Specs\Launch\Filters\CourseContextFilter;
use UBC\LTI\Specs\Launch\Filters\DeepLinkFilter;
use UBC\LTI\Specs\Launch\Filters\DeploymentFilter;
use UBC\LTI\Specs\Launch\Filters\GradebookMessageFilter;
use UBC\LTI\Specs\Launch\Filters\LaunchPresentationFilter;
use UBC\LTI\Specs\Launch\Filters\NrpsFilter;
use UBC\LTI\Specs\Launch\Filters\UserFilter;
use UBC\LTI\Specs\Launch\Filters\WhitelistFilter;
use UBC\LTI\Specs\ParamChecker;
use UBC\LTI\Specs\Security\Nonce;
use UBC\LTI\Utils\LtiException;
use UBC\LTI\Utils\LtiLog;
use UBC\LTI\Utils\Param;
use UBC\LTI\Utils\UriUtil;

/**
 * SECOND STAGE of LTI launch, the Authorization Request
 *
 * We first receive an auth req from the target tool.
 * We restore state from LtiSession.
 * We then send an auth req to the originating platform.
 */
class ReturnHandler
{
    private LtiLog $ltiLog;
    private LtiSession $session;
    private ParamChecker $checker;
    private Request $request;

    private array $filters;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->ltiLog = new LtiLog('Launch (Auth Resp)');
        $this->checker = new ParamChecker($request->input(), $this->ltiLog);
    }

    /**
     * Returns the OIDC login parameters we should send to the target tool. This
     * is the shim acting as a platform.
     */
    public function sendReturn(): Response
    {
        return response('Not Implemented', 501);
        /*
        return response()->view(
            'lti/launch/auto_submit_form',
            [
                'title' => 'Deep Link Return',
                'formUrl' => '',
                'params' => ''
            ]
        );
         */
    }

    /**
     * Load the LtiSession from the state param.
     * TODO: modify for deep link return's 'data' value
     */
    private function loadSession()
    {
        if (!$this->request->has(Param::STATE))
            throw new LtiException($this->ltiLog->msg(
                'Missing state in auth response', $this->request));

        $this->session = LtiSession::decodeEncryptedId(
            $this->request->input(Param::STATE));
        $this->ltiLog->setStreamid($this->session->log_stream);
    }

}
