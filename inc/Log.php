<?php
/**
* Log helper
*/
class Log {
	private static $enabled = false;

	static function on(){  self::$enabled = true;  }
	static function off(){ self::$enabled = false; }
	
	function msg( $message ) {
		if( self::$enabled ){
			file_put_contents('php://stderr', $message . "\n" );
		}
	}
}
