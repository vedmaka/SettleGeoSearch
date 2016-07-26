<?php

class SettleGeoSearchSpecial extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'SettleGeoSearch' );
	}

	public function execute( $subPage ) {

		$this->getOutput()->addModules('ext.settlegeosearch.special');
		$this->getOutput()->setPageTitle( wfMessage('settlegeosearch-special-title')->plain() );

		if( !$this->getRequest()->wasPosted() ) {
			$this->renderSearch();
		}else{
			$this->renderResults();
		}

	}

	private function renderSearch() {
		//TODO: implement ?
	}

	private function renderResults() {
		
	}

}