<?php
/**
* Map a MediaWiki instance
*/
class Crawler {
	private $query;					# an instance of the Query class	
	private $store;					# an instance of the Store class
	private $throttle = 1000;		# milliseconds to sleep between requests
	private $url;					# URL to a MediaWiki instances api.php
	
	function __construct( $url ) {
		$this->url = $url;
		
		$this->query = new Query( md5( $url ) );
		$this->store = new Store( md5( $url ) );
	}
	
	function start( $initial_page_title ){
		# get and store info for our initial page
		$this->query_and_store(
			array(
				'action' => 'query',
				'titles' => urlencode( $initial_page_title ),
				'format' => 'php',
				'prop'   => 'info',
			)
		);
		
		$this->process_queue();
	}
	
	function query_and_store( $parameters ){		
		# if we've processed this pageid, don't process it again
		if(
			$parameters['pageid']
			&& $this->store->in_log( $parameters['pageid'] )
		){
			Log::msg( "Skipping processed page #$parameters[pageid]." );
			return true;
		}
		
		$result = $this->query->run( $parameters );
		
		foreach( $result['query']['pages'] as $page ){
			
			/* Force missing to have a value if it is set.
			 * Without this sqlite can't tell the difference between
			 * a result set that that omits missing and that has missing = ''
			*/
			if( array_key_exists( 'missing', $page ) ){
				$page['missing'] = 1;
			}
			
			$this->store->page( $page );
			
			# if we've run generator=links, queue page and record link
			if( $parameters['generator'] == 'links' ){
				$this->store->enqueue( $page['pageid'] );
				$this->store->store_link(
					$parameters['pageids'], $page['pageid']
				);
			}
		}
		
		if( $result['query-continue'] ){
			foreach( $result['query-continue'] as $continue ){
				foreach( $continue as $key => $value ){
					$parameters[$key] = $value;
				}
			}

			$this->query_and_store( $parameters );
		}
	}

	function process_queue(){
		$pageid = $this->store->queue_shift(); # shift a pageid off top of queue
		
		# fetch linked page for page $pageid
		$this->query_and_store(
			array(
				'action'    => 'query',
				'pageids'   => $pageid,
				'format'    => 'php',
				'prop'      => 'info',
				'generator' => 'links',
				'gpllimit'  => 500,
			)
		);
		
		$this->store->log( $pageid );	# note that we've processed the pageid
		
		if( $this->throttle ){
			usleep( $this->throttle );
		}
	}
	
	function throttle( $milliseconds ){ $this->throttle = $milliseconds; }
}