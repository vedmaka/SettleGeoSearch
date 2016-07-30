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
    
    public function getHtml( $mode = self::SGS_MODE_VALUE, $name = '', $class = '' ) {
        $templateEngine = new TemplateParser(  __DIR__ . '/templates' );
        return $templateEngine->processTemplate( 'default', array(
        	'input_name' => $name,
	        'input_class' => $class,
	        'input_mode' => $mode,
	        'input_placeholder' => wfMessage('settlegeosearch-input-placeholder')->plain()
        ));
    }
    
}