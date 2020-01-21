<?php
// A trivial implementation of the log interface
namespace UBC\LTI\Log;

use UBC\LTI\Log\LogInterface;

class EchoLog implements LogInterface
{
	public static function debug(string $msg) { self::out($msg); }
	public static function info(string $msg) { self::out($msg); }
	public static function warning(string $msg) { self::out($msg); }
	public static function error(string $msg) { self::out($msg); }

	private static function out(string $msg) { echo $msg, PHP_EOL; }
}
