<?php

class SettleGeoSearch {
    
    public static function getModules() {
        return array('ext.settlegeosearch.main');
    }
    
    public function getHtml() {
        $templateEngine = new TemplateParser(  __DIR__ . '/templates' );
        return $templateEngine->processTemplate( 'default', array() );
    }
    
}