<?php
/**
* Map a MediaWiki instance
*/
class Crawler {
	private $lang_skip;				# regex of MediaWiki language codes
	private $query;					# an instance of the Query class
	private $max_depth;				# maxth depth of tree to fetch
	private $ns_skip;				# a list of namespaces to skip	
	private $store;					# an instance of the Store class
	private $url;					# URL to a MediaWiki instances api.php
	
	function __construct( $url ) {
		$this->url = $url;
		
		$this->query = new Query( $url );
		$this->store = new Store( md5( $url ) );

		include 'Names.php';	# needed for list of language codes
		# skip all translations, by default
		$this->skip_translations( array_keys( $wgLanguageNames ) );
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
		
		do {
			$return = $this->process_queue();
		} while ( $return );
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

			# skip namespaces set with skip_namespaces()
			if(
				$this->ns_skip && preg_match( $this->ns_skip, $page['title'] )
			){ continue; }
			
			# skip translations set with skip_translations()
			if(
				$this->lang_skip 
				&& preg_match( $this->lang_skip, $page['title'] )
			){ continue; }

			/* Force prop missing to have a value if it is set.
			 * Without this sqlite can't tell the difference between a
			 * result set that that omits missing and that has missing = ''
			*/
			if( array_key_exists( 'missing', $page ) ){
				$page['missing'] = 1;
			}

			$this->store->store_page( $page );
		
			if( ! $page['missing'] ){	# don't queue missing pages
				$this->store->enqueue( $page['pageid'] );	# queue page
			}
		
			# if we've run generator=links record link
			if( $parameters['generator'] == 'links' ){
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
		Log::msg( "Processing #$pageid from queue." );
		
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
		
		return $pageid;
	}
	
	function throttle( $ms ){ $this->query->throttle( $ms ); }

	function skip_translations( $language_codes ){
		switch( func_num_args() ) {
			case  0: $args = false;             break;
			case  1: $args = func_get_arg( 0 ); break;
			default: $args = func_get_args();   break;
		}
		
		$this->lang_skip = '[/(' . join('|', $args ) . ')$]';
		
		Log::msg( 'Set $this->lang_skip to ' . $this->lang_skip );
	}
	
	function skip_namespaces( $namespaces ){
		if( ! $namespaces ){
			$this->ns_skip = false;
		} else {
			$this->ns_skip = '[^(' . join( '|', func_get_args() ) . '):]';
		}
		Log::msg( 'Set $this->ns_skip to ' . $this->ns_skip );
	}
	
	function max_depth( $max_depth ){
		$this->max_depth = $max_depth;
	}
}
