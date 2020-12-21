<?php
namespace UBC\LTI\Specs\Core;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

use Symfony\Component\HttpFoundation\Response as HttpResp;

use App\Models\ReturnUrl;

use UBC\LTI\Utils\LtiException;
use UBC\LTI\Utils\LtiLog;
use UBC\LTI\Utils\Param;

class PlatformReturnUrl
{
    private LtiLog $ltiLog;
    private ReturnUrl $returnUrl;
    private Request $request;

    public function __construct(Request $request, ReturnUrl $returnUrl)
    {
        $this->request = $request;
        $this->returnUrl = $returnUrl;
        $this->ltiLog = new LtiLog('ReturnUrl (Platform)');
    }

    public function getReturnUrl(string $token): RedirectResponse
    {
        // This is a very basic security measure. Only people who know the
        // access token for this endpoint are allowed. This is just to prevent
        // a lazy attack where you access the return url endpoint (just
        // incrementing the return url id) to find out what the original return
        // url is.
        if ($this->returnUrl->token != $token) {
            $this->ltiLog->error("Invalid token: $token", $this->request,
                                 $this->returnUrl);
            return abort(HttpResp::HTTP_NOT_FOUND);
        }
        // tools may send messages back to the platform as queries, we need to
        // also add them to the original url
        $ltiMsg = $this->request->query(Param::LTI_MSG);
        $ltiErrorMsg = $this->request->query(Param::LTI_ERRORMSG);
        $ltiLog = $this->request->query(Param::LTI_LOG);
        $ltiErrorLog = $this->request->query(Param::LTI_ERRORLOG);

        $queries = [];
        if ($ltiMsg) $queries[Param::LTI_MSG] = $ltiMsg;
        if ($ltiErrorMsg) {
            // not shim errors, so we're only logging at info, just in case
            $this->ltiLog->info("lti_errormsg: $ltiErrorMsg", $this->request,
                                   $this->returnUrl);
            $queries[Param::LTI_ERRORMSG] = $ltiErrorMsg;
        }
        if ($ltiLog) {
            $this->ltiLog->info("lti_log: $ltiLog", $this->request,
                                $this->returnUrl);
            $queries[Param::LTI_LOG] = $ltiLog;
        }
        if ($ltiErrorLog) {
            // not shim errors, so we're only logging at info, just in case
            $this->ltiLog->info("lti_errorlog: $ltiErrorLog", $this->request,
                                   $this->returnUrl);
            $queries[Param::LTI_ERRORLOG] = $ltiErrorLog;
        }

        $url = $this->returnUrl->getUrl($queries);
        $this->ltiLog->notice("Redirected to: $url", $this->request,
                              $this->returnUrl);
        return redirect()->away($url);
    }
}
