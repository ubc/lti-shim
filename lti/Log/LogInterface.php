<?php
namespace UBC\LTI\Log;

interface LogInterface
{
	public static function debug(string $msg);
	public static function info(string $msg);
	public static function warning(string $msg);
	public static function error(string $msg);
}
