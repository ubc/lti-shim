<?php
namespace UBC\LTI\Specs\Nrps;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use App\Models\Nrps;

use UBC\LTI\Specs\Nrps\Filters\CourseContextFilter;
use UBC\LTI\Specs\Nrps\Filters\NrpsUrlFilter;
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
            new NrpsUrlFilter,
            new WhitelistFilter
        ];
    }

    public function getNrps(): array
    {
        $toolNrps = new ToolNrps($this->request, $this->nrps);
        $response = $toolNrps->getNrps();
        // apply all filters to the response
        foreach ($this->filters as $filter) {
            $response = $filter->filter($response, $this->nrps->deployment_id,
                $this->nrps->tool_id);
        }
        return $response;
    }
}
