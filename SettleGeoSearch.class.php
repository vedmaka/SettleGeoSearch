<?php

class SettleGeoSearch {

	const SGS_MODE_VALUE = 1;
	const SGS_MODE_TEXT = 2;
    
    public static function getModules() {
        return array('ext.settlegeosearch.main');
    }

    public static function getSearchPageUrl() {
    	return SpecialPage::getTitleFor('SettleGeoSearch')->getFullURL();
    }

	/**
	 * @param array  $locations
	 *
	 * @param string $class
	 *
	 * @return string
	 */
    public static function formatLocationBreadcrumbs( $locations, $class = '' ) {

    	$breadcrumbs = '<ol class="breadcrumb '.$class.'">';
			if( array_key_exists('country', $locations) ) {
				$breadcrumbs .= '<li class="active">'.$locations['country'][0].'</li>';
			}
		    if( array_key_exists('state', $locations) ) {
			    $breadcrumbs .= '<li class="active">'.$locations['state'][0].'</li>';
		    }
		    if( array_key_exists('city', $locations) ) {
			    $breadcrumbs .= '<li class="active">'.$locations['city'][0].'</li>';
		    }
    	$breadcrumbs .= '</ol>';

    	return $breadcrumbs;

    }
    
    public function getHtml( $mode = self::SGS_MODE_VALUE, $name = '', $class = '', $preselected_code = '', $preselected_text = '' ) {
        $templateEngine = new TemplateParser(  __DIR__ . '/templates', true );
        return $templateEngine->processTemplate( 'default', array(
        	'input_name' => $name,
	        'input_class' => $class,
	        'input_mode' => $mode,
	        'input_placeholder' => wfMessage('settlegeosearch-input-placeholder')->plain(),
	        'input_preselected_code' => $preselected_code,
	        'input_preselected_text' => $preselected_text
        ));
    }
    
}