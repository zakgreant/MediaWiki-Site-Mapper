#!/usr/bin/php
<?php

require 'inc/Cache.php';
require 'inc/Crawler.php';
require 'inc/Query.php';
require 'inc/Log.php';
require 'inc/Store.php';

Log::on();

$crawler = new Crawler( 'http://www.mediawiki.org/w/api.php' );
$crawler->throttle( 5000 );
$crawler->skip_namespaces(
	'API', 'API talk', 
	'Category', 'Category talk', 
	'Extension', 'Extension talk', 
	'Help', 'Help talk', 
	'Manual talk', 
	'Project', 'Project talk', 
	'Talk', 
	'User', 'User talk'
);
$crawler->start( 'Developer hub' );