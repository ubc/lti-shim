<?php
namespace UBC\LTI\Specs\Nrps;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Models\Nrps;

use UBC\LTI\Utils\LtiException;
use UBC\LTI\Utils\LtiLog;
use UBC\LTI\Utils\Param;
use UBC\LTI\Specs\Nrps\Filters\CourseContextFilter;
use UBC\LTI\Specs\Nrps\Filters\MemberFilter;
use UBC\LTI\Specs\Nrps\Filters\NrpsUrlFilter;
use UBC\LTI\Specs\Nrps\Filters\PaginationFilter;
use UBC\LTI\Specs\Nrps\Filters\WhitelistFilter;
use UBC\LTI\Specs\Nrps\ToolNrps;
use UBC\LTI\Specs\Security\AccessToken;

class PlatformNrps
{
    private AccessToken $tokenHelper;
    private LtiLog $ltiLog;
    private Nrps $nrps;
    private Request $request;
    private array $filters;

    public function __construct(Request $request, Nrps $nrps)
    {
        $this->request = $request;
        $this->nrps = $nrps;
        $this->ltiLog = new LtiLog('NRPS (Platform)');
        $this->tokenHelper = new AccessToken($this->ltiLog);
        $this->filters = [
            new CourseContextFilter($this->ltiLog),
            new MemberFilter($this->ltiLog),
            new NrpsUrlFilter($this->ltiLog),
            new PaginationFilter($this->ltiLog),
            new WhitelistFilter($this->ltiLog)
        ];
    }

    public function getNrps(): Response
    {
        $this->ltiLog->info('NRPS request received at ' .
            $this->request->fullUrl(), $this->request, $this->nrps);
        $this->tokenHelper->verify(AccessToken::fromRequestHeader(
            $this->request, $this->ltiLog));

        // access token good, proxy the request
        $toolNrps = new ToolNrps($this->request, $this->nrps, $this->ltiLog);
        $nrpsData = $toolNrps->getNrps();

        $this->ltiLog->debug('Pre-filter data: ' . json_encode($nrpsData),
            $this->request, $this->nrps);
        $this->ltiLog->debug('Applying filters', $this->request, $this->nrps);
        // apply all filters to the nrpsData
        foreach ($this->filters as $filter) {
            $nrpsData = $filter->filter($nrpsData, $this->nrps);
        }
        $this->ltiLog->debug('Post-filter data: ' . json_encode($nrpsData),
            $this->request, $this->nrps);

        // the link param is special, it contains NRPS result pagination links
        // and is filtered by the PaginationFilter, it needs to be sent in the
        // response header. So we need to separate it out from the response body
        // here.
        $linkHeader = [];
        if (isset($nrpsData[Param::LINK])) {
            $linkHeader = $nrpsData[Param::LINK];
            unset($nrpsData[Param::LINK]);
        }

        $response = response($nrpsData);
        if ($linkHeader) $response->header(Param::LINK, $linkHeader);

        $this->ltiLog->notice('NRPS request completed', $this->request,
                            $this->nrps, $this->nrps->course_context);

        return $response;
    }
}
