<?php
namespace UBC\LTI\Specs\Nrps;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use App\Models\Nrps;

use UBC\LTI\Specs\Nrps\ToolNrps;

class PlatformNrps
{
    private Request $request;
    private Nrps $nrps;

    public function __construct(Request $request, Nrps $nrps)
    {
        $this->request = $request;
        $this->nrps = $nrps;
    }

    public function getNrps(): array
    {
        $toolNrps = new ToolNrps($this->request, $this->nrps);
        $response = $toolNrps->getNrps();
        // TODO actual filtering
        return $response;
    }
}
