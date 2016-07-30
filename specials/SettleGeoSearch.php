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

		global $wgLang;

		$data = array(
			'items' => array(),
			'count' => 0
		);
		$template = 'default';

		$this->getOutput()->addModules( SettleGeoSearch::getModules() );
		$search = new SettleGeoSearch();
		$data['input'] = $search->getHtml( SettleGeoSearch::SGS_MODE_VALUE, 'geo_id' );
		$data['formurl'] = SettleGeoSearch::getSearchPageUrl();

		$geoCode = $this->getRequest()->getVal('geo_id');
		$geoText = $this->getRequest()->getVal('geo_text');
		$page = $this->getRequest()->getVal('page', 0);
		$perPage = 10;

		$term = '*';
		if( !empty($geoText) ) {
			$term = '"' . trim( htmlspecialchars( str_replace(array('"','~','*'), '',$geoText) ) ) . '"';
		}

		// Determine entity
		$entity = false;
		try {
			$entity = MenaraSolutions\Geographer\City::build( $geoCode );
		}catch (Exception $e) {
			try {
				$entity = MenaraSolutions\Geographer\State::build( $geoCode );
			}catch (Exception $e) {
				try {
					$earth = new MenaraSolutions\Geographer\Earth();
					$entity = $earth->findOne( array('geonamesCode' => $geoCode) );
				}catch (Exception $e) {
					$term = '';
				}
			}
		}


		if( $entity instanceof MenaraSolutions\Geographer\Divisible ) {
			$term .= ' ' . $entity->setLanguage( $wgLang->getCode() )->inflict('in')->getShortName();
		}

		$data['term'] = $term;

		//if( !empty($geoCode) || !empty($geoText) ) {

			// Process query
			$sqi = new \SQI\SemanticQueryInterface( array(
				'fetch_all_properties' => true
			) );

			$sqi->category( 'Card' );
			if( $geoCode ) {
				$sqi->condition( 'Geocodes', $geoCode );
			}
			if( !empty($geoText) ) {
				$sqi->like( 'Title', ucfirst($geoText).'*' );
			}
			$result = $sqi->offset( $page * $perPage )->limit( $perPage )->toArray();

			if ( count( $result ) ) {
				foreach ( $result as $r ) {
					if ( ! array_key_exists( 'title', $r ) ) {
						continue;
					}
					if ( ! array_key_exists( 'properties', $r ) ) {
						continue;
					}

					/** @var Title $title */
					$title = $r['title'];
					/** @var string[] $properties */
					$properties = $r['properties'];

					$item = array(
						'real_title'  => $title->getBaseText(),
						'url'         => $title->getFullURL(),
						'title'       => $properties['Title'][0],
						'city'        => $properties['City'] ? $properties['City'][0] : false,
						'country'     => $properties['Country'] ? $properties['Country'][0] : false,
						'state'       => $properties['State'] ? $properties['State'][0] : false,
						'tags'        => $properties['Tags'] ? $properties['Tags'] : false,
						'updated'     => $properties['Modification date'] ? $properties['Modification date'][0] : '',
						'description' => $properties['Short description'] ? $properties['Short description'][0] : 'No description provided',
						'processing_time' => $properties['Processing time'] ? $properties['Processing time'][0] : '?',
						'total_cost' => $properties['Total cost'] ? $properties['Total cost'][0] : '?',
						'total_cost_cur' => $properties['Total cost currency'] ? $properties['Total cost currency'][0] : '',
						'difficulty' => $properties['Difficulty'] ? $properties['Difficulty'][0] : '?'
					);

					$data['items'][] = $item;

				}
			}

			$data['count'] = count($result);

		//}

		$data['page'] = $page;
		$data['perPage'] = $perPage;
		$data['taglink'] = SpecialPage::getTitleFor('SearchByProperty')->getFullURL().'/Tags/';

		$data['_i_results_for'] = wfMessage( 'settlegeosearch-special-results-for' )->plain();
		$data['_i_processing_time'] = wfMessage( 'settlegeosearch-special-result-processing-time' )->plain();
		$data['_i_total_cost'] = wfMessage( 'settlegeosearch-special-result-total-cost' )->plain();
		$data['_i_difficulty'] = wfMessage( 'settlegeosearch-special-result-difficulty' )->plain();

		$templater = new TemplateParser( dirname(__FILE__) . '/../templates/special/' );
		$html = $templater->processTemplate( $template, $data );
		$this->getOutput()->addHTML( $html );

	}

}