<?php
/**
* Make queries using the MediaWiki API
*/
class Query {
	private $cache;				# an instance of the Cache class
	private $stream_context;	# method & headers for HTTP requests to api.php
	private $url;				# URL to a MediaWiki instances api.php

	function __construct( $url ) {
		$this->cache = new Cache( md5( $url ) );
		$this->url = $url;
		$this->stream_context = stream_context_create(
			array(
				'http' => array(
					'method' => 'GET',
					'header' => "User-Agent: WikiMapper 0.1\r\n",
		)));
	}
	
	/**
	 * Make a query URL from $this->url and an associative array of parameters
	 */
	function make_query( $parameters ){
		ksort( $parameters );
		$q = array();
		foreach( $parameters as $name => $value ){
			$q[] = $name . '=' . $value;
		}
		
		return $this->url . '?' . join( '&', $q );
	}
	
	function query( $parameters ){
		$query = $this->make_querystring( $parameters );
		
		$result = $this->cache->fetch( md5($query) );

		if( ! $result ){	# if the results aren't cached ...
			Log::msg( "Running query '$query'." );
			$result = file_get_contents( $query, false, $this->stream_context);
			
			$this->cache->store( md5( $query ), $result );
		}
		
		if( $result['warning'] ){	# we shouldn't get warnings
			throw new Exception(
				"Query\n\t$query\ngenerated warning\n" .
				print_r( $result['warning'], true)
			);
		}
		
		return $result;
	}
}