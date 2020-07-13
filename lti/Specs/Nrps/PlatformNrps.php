<?php
namespace UBC\LTI\Specs\Nrps;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Models\Nrps;

use UBC\LTI\Param;
use UBC\LTI\Specs\Nrps\Filters\CourseContextFilter;
use UBC\LTI\Specs\Nrps\Filters\MemberFilter;
use UBC\LTI\Specs\Nrps\Filters\NrpsUrlFilter;
use UBC\LTI\Specs\Nrps\Filters\PaginationFilter;
use UBC\LTI\Specs\Nrps\Filters\WhitelistFilter;
use UBC\LTI\Specs\Nrps\ToolNrps;

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
}
