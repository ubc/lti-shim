<?php
// An implementation of the log interface using Laravel's log mechanism
namespace UBC\LTI\Log;

use Illuminate\Support\Facades\Log;

use LogInterface;

class LaravelLog implements LogInterface
{
	public static function debug(string $msg) { Log::debug($msg); }
	public static function info(string $msg) { Log::info($msg); }
	public static function warning(string $msg) { Log::warning($msg); }
	public static function error(string $msg) { Log::error($msg); }
}
