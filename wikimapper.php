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
$crawler->start( 'Developer hub' );