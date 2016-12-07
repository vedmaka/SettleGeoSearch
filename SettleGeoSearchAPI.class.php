<?php

use MenaraSolutions\Geographer;

class SettleGeoSearchAPI extends ApiBase {
    
    public function execute() {

    	$result = array(
    		'items' => array()
	    );

    	$params = $this->extractRequestParams();
	    $term = $params['term'];

	    if( strlen($term) > 2 ) {
		    $result['items'] = $this->processTerm( $term );
	    }

	    $this->getResult()->addValue( null, $this->getModuleName(), $result );

    }

    private function processTerm( $term ) {

    	global $wgLang;

    	$result = array();

	    // No need to check empty terms
	    if( empty($term) ) {
	    	return $result;
	    }

	    $matches = SettleGeoTaxonomy::getInstance()->getMatch( $term, 10 );

	    if( count($matches) ) {
		    foreach ( $matches as $match ) {
			    $result[] = array(
			    	'label' => $match['name'],
				    'value' => $match['name'],
				    'code' => $match['code_geonames'],
				    'suffix' => (!empty($match['suffix'])) ? $match['suffix'] : false
			    );
	    	}
	    }

	    return $result;
    }
    
    public function getAllowedParams() {
        return array(
        	'term' => array(
        		ApiBase::PARAM_REQUIRED => false,
		        ApiBase::PARAM_TYPE => 'string'
	        )
        );
    }
    
}