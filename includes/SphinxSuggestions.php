<?php

use Foolz\SphinxQL\SphinxQL;
use Foolz\SphinxQL\Drivers\Mysqli\Connection;

class SphinxSuggestions {

	public static function addValue( $value )
	{

		global $wgLang;


	}

	public static function getValue( $term = null, $limit = 10 )
	{

		global $wgLang;

		if( $term === null ) {
			return array();
		}
		$suggestions = array();

		$result = array();

		if( $result ) {
			foreach ($result as $r) {
				
			}
		}

		return $suggestions;
	}

}