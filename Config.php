<?php
// Holds instance specific LTI configurations. Basically, we've tried to
// abstract away things like logging and storage behind interfaces. And this
// Config object tells us what logger or storage class to actually use. This is
// meant to allow for the possibility of extracting all this out as a
// standalone library in the future.
namespace UBC\LTI\Core;

use UBC\LTI\Log\LogInterface;
use UBC\LTI\Log\EchoLog;

class Config
{
	public static $log;

	// defaults to the trivial implementations
	public static function defaultInit()
	{
		$log = new EchoLog();
		self::init($log);
	}

	// override the trivial implementation defaults
	public static function init(LogInterface $log)
	{
		self::$log = $log;
	}
}
// php can't handle non-trivial expressions in initializers, so we have to
// call the default init separately to properly get a default config
Config::defaultInit();
