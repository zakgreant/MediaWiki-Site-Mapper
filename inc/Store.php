<?php
/**
* SQLite database wrapper
*/
class Store {
	private $db;	# database handle
	
	function __construct( $path ) {
		$filename = "db/$path.sqlite3";
		Log::msg( "Creating tables." );
		`cat db/schema.sqlite3 | sqlite3 $filename`;
		$this->db = new SQLite3( $filename );
	}
	
	function quote_list( $array, $quote ){
		$array = array_map( 'SQLite3::escapeString', $array );
		return $quote . join("$quote,$quote", $array) . $quote;
	}
	
	function dequeue( $pageid ){
		Log::msg( "Removing #$pageid from the queue." );
		return $this->db->exec("DELETE FROM queue WHERE pageid='$pageid'");
	}
	
	function enqueue( $pageid ){
		Log::msg( "Inserting #$pageid into the queue." );
		return $this->db->exec("INSERT INTO queue VALUES ('$pageid')");
	}

	function log( $pageid ){
		Log::msg( "Inserting #$pageid into the log." );
		return $this->db->exec("INSERT INTO log (pageid) VALUES ('$pageid')");
	}
	
	function in_log( $pageid ){
		return $this->db->querySingle(
			"SELECT 1 FROM log WHERE pageid='$pageid'"
		);
	}
	
	function queue_shift(){
		$pageid = $this->db->querySingle( "SELECT pageid FROM queue LIMIT 1");
		$this->dequeue( $pageid );
		Log::msg( "Popping #$pageid from the queue." );
		return $pageid;
	}
	
	function store_link( $parent_pageid, $child_pageid ){
		return $this->db->exec(
			"INSERT INTO link (parent, child) 
				VALUES ('$parent_pageid', '$child_pageid')"
		);
	}
	
	function store_page( $page ){
		$columns = $this->quote_list( array_keys( $page ), '"' );
		$values = $this->quote_list( array_values( $page ), "'" );
		
		return $this->db->exec(
			"INSERT INTO page ($columns) VALUES ($values)"
		);
	}
}