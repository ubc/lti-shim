<?php
namespace UBC\LTI\Specs\Nrps;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Models\Nrps;

use UBC\LTI\LtiException;
use UBC\LTI\Param;
use UBC\LTI\Specs\Nrps\Filters\CourseContextFilter;
use UBC\LTI\Specs\Nrps\Filters\MemberFilter;
use UBC\LTI\Specs\Nrps\Filters\NrpsUrlFilter;
use UBC\LTI\Specs\Nrps\Filters\PaginationFilter;
use UBC\LTI\Specs\Nrps\Filters\WhitelistFilter;
use UBC\LTI\Specs\Nrps\ToolNrps;
use UBC\LTI\Specs\Security\AccessToken;

class PlatformNrps
{
    private Request $request;
    private Nrps $nrps;
    private array $filters;

    public function __construct(Request $request, Nrps $nrps)
    {
        $this->request = $request;
        $this->nrps = $nrps;
        $this->filters = [
            new CourseContextFilter,
            new MemberFilter,
            new NrpsUrlFilter,
            new PaginationFilter,
            new WhitelistFilter
        ];
    }

    public function getNrps(): Response
    {
        AccessToken::verify($this->getAccessToken());

        // access token good, proxy the request
        $toolNrps = new ToolNrps($this->request, $this->nrps);
        $nrpsData = $toolNrps->getNrps();

        // apply all filters to the nrpsData
        foreach ($this->filters as $filter) {
            $nrpsData = $filter->filter($nrpsData, $this->nrps);
        }

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

        return $response;
    }

    /**
     * Laravel has a convenient function to get the access token for us, in the
     * form of request->bearerToken(). Unfortunately, it does not follow spec
     * and requires bearer to be case sensitive, e.g. it works with:
     *   "authorization: Bearer <access token>"
     * But not with:
     *   "authorization: bearer <access token>"
     *
     * So we have to have our own function to get the access token.
     */
    private function getAccessToken()
    {
        $authHeader = $this->request->header('authorization');
        if (!$authHeader) throw new LtiException('Missing authorization header');
        // make sure we have a bearer token, no matter how it's capitalized
        $tokenType = substr($authHeader, 0, 6);
        if (strcasecmp(Param::TOKEN_TYPE_VALUE, $tokenType) != 0)
            throw new LtiException('Unknown authorization token type: ' .
                $tokenType);
        return substr($authHeader, 7);
    }
}
