<?php
namespace UBC\LTI\Specs\Ags;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Models\Ags;
use App\Models\AgsLineitem;

use UBC\LTI\Utils\LtiException;
use UBC\LTI\Utils\LtiLog;
use UBC\LTI\Specs\Ags\PlatformLineitem;

class PlatformAgs
{
    private Ags $ags;
    private LtiLog $ltiLog;
    private Request $request;

    public function __construct(Request $request, Ags $ags)
    {
        $this->request = $request;
        $this->ags = $ags;
        $this->ltiLog = new LtiLog('AGS (Platform)');
    }

    /********* Lineitem Service *********/

    public function getLineitems(): Response
    {
        $lineitems = new PlatformLineitem($this->request, $this->ags);
        return $lineitems->getLineitems();
    }

    public function postLineitems(): Response
    {
        $lineitems = new PlatformLineitem($this->request, $this->ags);
        return $lineitems->postLineitems();
    }

    public function getLineitem(AgsLineitem $lineitem): Response
    {
        $lineitems = new PlatformLineitem($this->request, $this->ags);
        return $lineitems->getLineitem($lineitem);
    }

    public function putLineitem(AgsLineitem $lineitem): Response
    {
        $lineitems = new PlatformLineitem($this->request, $this->ags);
        return $lineitems->putLineitem($lineitem);
    }

    public function deleteLineitem(AgsLineitem $lineitem): Response
    {
        $lineitems = new PlatformLineitem($this->request, $this->ags);
        return $lineitems->deleteLineitem($lineitem);
    }
}
