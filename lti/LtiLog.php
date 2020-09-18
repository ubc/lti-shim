<?php
namespace UBC\LTI;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use App\Models\CourseContext;
use App\Models\LtiFakeUser;
use App\Models\LtiRealUser;
use App\Models\LtiSession;
use App\Models\Nrps;

use UBC\LTI\LtiException;

/**
 * A wrapper around Laravel logging that takes care of some of the log header
 * output info. The first param for debug(), info(), etc is expected to be the
 * message. Any additional params are additional objects that provide context
 * for the LTI request. Supported so far are: Laravel request, LtiSession, and
 * Nrps classes.
 *
 * My original intent was to allow this to be used either statically or
 * instanced, similar to how Laravel's logging can be chained. Unfortunately,
 * that turns out to require more php dark magic than I'm comfortable with, so
 * this needs to be instanced.
 *
 * You can provide a prefix that is added to the head of all messages in the
 * constructor or set it using setPrefix().
 */
class LtiLog
{
    private const DELIMITER = ' ~ ';
    private const DEBUG = 1;
    private const INFO = 2;
    private const NOTICE = 3;
    private const WARNING = 4;
    private const ERROR = 5;
    private const CRITICAL = 6;
    private const ALERT = 7;
    private const EMERGENCY = 8;

    private string $prefix = ''; // string we add to all messages

    public function __construct(string $prefix='')
    {
        $this->setPrefix($prefix);
    }

    public function setPrefix(string $prefix)
    {
        $this->prefix = $prefix;
    }

    public function debug(...$params)
    {
        $this->log(self::DEBUG, $params);
    }

    public function info(...$params)
    {
        $this->log(self::INFO, $params);
    }

    public function notice(...$params)
    {
        $this->log(self::NOTICE, $params);
    }

    public function warning(...$params)
    {
        $this->log(self::WARNING, $params);
    }

    public function error(...$params)
    {
        $this->log(self::ERROR, $params);
    }

    public function critical(...$params)
    {
        $this->log(self::CRITICAL, $params);
    }

    public function alert(...$params)
    {
        $this->log(self::ALERT, $params);
    }

    public function emergency(...$params)
    {
        $this->log(self::EMERGENCY, $params);
    }

    /**
     * Instead of writing to the LTI log, just return the message that would be
     * generated.
     */
    public function msg(...$params)
    {
        if (count($params) < 1)
            throw new LtiException('Invalid log message');
        return $this->getMsg($params);
    }

    private function getMsg(array $params)
    {
        $components = [];
        if ($this->prefix) $components[] = $this->prefix;
        for ($i = 1; $i < count($params); $i++) {
            $obj = $params[$i];
            if ($obj instanceof Request) {
                $components[] = 'From ' . $obj->ip();
            }
            elseif ($obj instanceof LtiSession) {
                $components[] = 'LtiSession: ' . $obj->id;
            }
            elseif ($obj instanceof Nrps) {
                $components[] = 'Nrps: ' . $obj->id;
            }
            elseif ($obj instanceof CourseContext) {
                $components[] = 'Course: ' . $obj->id . ' ' . $obj->label .
                    ' - ' . $obj->title;
            }
            elseif ($obj instanceof LtiRealUser) {
                $components[] = 'Real User: ' . $obj->id . ' ' . $obj->name;
            }
            elseif ($obj instanceof LtiFakeUser) {
                $components[] = 'Fake User: ' . $obj->id . ' ' . $obj->name;
            }
            elseif ($obj instanceof \Exception) {
                $components[] = 'Exception: ' . $obj->getMessage();
            }
            else {
                Log::channel('lti')->warning('Unknown object given in lti log');
            }
        }
        $components[] = $params[0];
        return implode(self::DELIMITER, $components);
    }

    private function log($level, $params)
    {
        if (count($params) < 1)
            throw new LtiException('Invalid log message');
        $msg = $this->getMsg($params);
        switch($level) {
        case self::DEBUG:
            Log::channel('lti')->debug($msg);
            break;
        case self::INFO:
            Log::channel('lti')->info($msg);
            break;
        case self::NOTICE:
            Log::channel('lti')->notice($msg);
            break;
        case self::WARNING:
            Log::channel('lti')->warning($msg);
            break;
        case self::ERROR:
            Log::channel('lti')->error($msg);
            break;
        case self::CRITICAL:
            Log::channel('lti')->critical($msg);
            break;
        case self::ALERT:
            Log::channel('lti')->alert($msg);
            break;
        case self::EMERGENCY:
            Log::channel('lti')->emergency($msg);
            break;
        default:
            Log::channel('lti')->warning("Invalid log level " . $level .
                ", redirecting to debug.");
            Log::channel('lti')->debug($msg);
        }
    }
}
