<?php
/**
* Local file cache for MediaWiki API queries
*/
class Cache {
	private $path;	# path to the cache directory
	
	function __construct( $path ) {
		# use separate caches for separate MW installs
		$this->path = 'cache/' . $path;
		
		if( ! file_exists( $this->path ) ){
			Log::msg( "Intializing cache directory '$this->path'." );
			mkdir( $this->path, 0700, true );
		}
		
		if( ! is_dir( $this->path ) ) {
			throw new Exception( "Path '$this->path' is not a directory." );
		}
		
		if( ! is_writable( $this->path ) ) {
			throw new Exception( "Path '$this->path' is not writable." );  
		}
	}

	function flush( $filename = null ){
		if( $filename ){
			Log::msg( "Deleting cache file '$this->path/$filename'." );
			return unlink( "$this->path/$filename" );
		}
		
		Log::msg( "Deleting all cache files in '$this->path'." );
		foreach( glob("$this->path/*") as $filename ){
		    unlink( $filename );
		}
	}
	
	function fetch( $hash ){
		$filename = "$this->path/$hash";
		if( ! file_exists( $filename ) ){
			return false;
		}
		
		$result = file_get_contents( $filename );
		$bytes = strlen( $result );
		Log::msg( "Fetching $bytes bytes from cache file '$filename'." );
		return $result;
	}
	
	function store( $hash, $data ){
		$path = "$this->path/$hash";
		$bytes = strlen( $data );
		Log::msg( "Storing $bytes bytes in cache file '$path'." );
		return file_put_contents( "$this->path/$hash", $data );
	}
}