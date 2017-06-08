<?php

use Foolz\SphinxQL\SphinxQL;
use Foolz\SphinxQL\Drivers\Mysqli\Connection;

class SettleGeoSearchSpecial extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'SettleGeoSearch' );
	}

	public function execute( $subPage ) {

		$this->getOutput()->addModules('ext.settlegeosearch.special');
		$this->getOutput()->addModules('skins.settlein.animate.standalone');

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
			'count' => 0,
			'moretext' => ''
		);

		$template = 'default';

		$this->getOutput()->addModules( SettleGeoSearch::getModules() );
		$search = new SettleGeoSearch();

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

		if( $geoText ) {
			$data['moretext'] = wfMessage('settlegeosearch-special-result-additional-link-text')
				->params(
					SpecialPage::getTitleFor('SettleCategorySearch')->getFullURL(),
					SpecialPage::getTitleFor('SettleRequestArticle')->getFullURL(),
					SpecialPage::getTitleFor('SettleNewArticle')->getFullURL()
				)
				->plain();
		}

		if( $geoCode ) {
			$data['moretext'] = wfMessage('settlegeosearch-special-result-additional-link-geo')
				->params(
					$entity->setLanguage( $wgLang->getCode() )->getShortName(),
					SpecialPage::getTitleFor('SettleCategorySearch')->getFullURL().'/'.$geoCode,
					SpecialPage::getTitleFor('SettleRequestArticle')->getFullURL(),
					SpecialPage::getTitleFor('SettleNewArticle')->getFullURL()
				)
				->plain();
		}

		if( $entity instanceof MenaraSolutions\Geographer\Divisible ) {
			$term .= ' ' . $entity->setLanguage( $wgLang->getCode() )->inflict('in')->getShortName();
		}

		$data['term'] = $term;

		//TODO: get results
		$query = SphinxStore::getInstance()->getQuery();

		//TODO: improve protection
		$geoCode = str_replace( array("/", "\\", "'", '"'), "", $geoCode );
		$geoText = str_replace( array("/", "\\", "'", '"'), "", $geoText );

		// samples:
		// Newcastle    = 2155472
		// NSW          = 2155400
		// Australia    = 2077456


		$pl1 = "";
		$pl2 = "";
		if( $geoCode ) {
			//$pl1 = ", ANY(x={$geoCode} FOR x IN properties.geocodes) as p";

			//if we are looking for city, lets also look for pages which has state of the city specified:
			//TODO: only if record have no city specified (state-wide)
			/*if( $entity instanceof MenaraSolutions\Geographer\City ) {
				$stateCode = $entity->getParentCode();
				$pl1 = ", IN(properties.geocodes, {$geoCode}) OR ( IN(properties.geocodes, {$geoCode}, {$stateCode}) AND properties.city_code IS NULL ) as p";
			}else {
				$pl1 = ", IN(properties.geocodes, {$geoCode}) as p";
			}*/

			// Add self code into query
			/*$geocodesSql = array( $geoCode );

			if( $entity instanceof MenaraSolutions\Geographer\City ) {
				// State code into query
				$geocodesSql[] = $entity->getParentCode();
				// Country code into query
				$geocodesSql[] = $entity->parent()->geonamesCode;
			}
			$pl1 = ", IN( properties.geocodes, ".implode(',', $geocodesSql)." ) as p";*/

			// If we're looking for particular city display:
			// - all pages that have city in their code
			// - all pages that are state-wide for this city
			// - all pages that are country-wide for that state country
			if( $entity instanceof \MenaraSolutions\Geographer\City ) {

				$stateCode = $entity->getParentCode();

				$tempEarth = new MenaraSolutions\Geographer\Earth();
				$tempCountry = $tempEarth->findOne( array('code' => $entity->parent()->getParentCode()) );
				$countryCode = $tempCountry->geonamesCode;

				$pl1 = ", (";
				$pl1 .= "( IN( properties.geocodes, {$geoCode} ) )"; // AND IN( properties.geocategoryscope, 2) )";
				$pl1 .= " OR( IN(properties.geocodes, {$stateCode} ) AND IN( properties.geocategoryscope, 1) )";
				$pl1 .= " OR( IN(properties.geocodes, {$countryCode} ) AND IN( properties.geocategoryscope, 0) )";
				$pl1 .= " )";
			}

			// If we're looking for state-wide search lets display:
			// - all results that have this state in codes
			// - all results that country-wide for this state
			if( $entity instanceof \MenaraSolutions\Geographer\State ) {

				$tempEarth = new MenaraSolutions\Geographer\Earth();
				$tempCountry = $tempEarth->findOne( array('code' => $entity->getParentCode()) );
				$countryCode = $tempCountry->geonamesCode;

				$pl1 = ", (";
				$pl1 .= "( IN( properties.geocodes, {$geoCode} ) )"; // AND IN( properties.geocategoryscope, 1 ) )";
				$pl1 .= "OR ( IN( properties.geocodes, {$countryCode} ) AND IN( properties.geocategoryscope, 0 ) )";
				$pl1 .= " )";

			}

			// If we're looking country-wide lets display all results where country is available
			if( $entity instanceof \MenaraSolutions\Geographer\Country ) {

				$pl1 = ", (";
				$pl1 .= "( IN( properties.geocodes, {$geoCode} ) )"; // AND IN( properties.geocategoryscope, 0 ) )";
				$pl1 .= " )";

			}

			/*$pl1 = ", (";
			// Search is for City
			if( $entity instanceof \MenaraSolutions\Geographer\City ) {
				// Display only pages that explicitly have their binding set to the selected city
				$pl1 .= "( IN( properties.geocodes, {$geoCode} ) AND IN( properties.geocategoryscope, 2 ) )";
			}
			// Search is for State
			if( $entity instanceof \MenaraSolutions\Geographer\State ) {
				// Display only pages that explicitly have their binding set to the selected state
				$pl1 .= "( IN( properties.geocodes, {$geoCode} ) AND IN( properties.geocategoryscope, 1 ) )";
			}
			// Search is for Country
			if( $entity instanceof \MenaraSolutions\Geographer\Country ) {
				// Display only pages that explicitly have their binding set to the selected country
				$pl1 .= "( IN( properties.geocodes, {$geoCode} ) AND IN( properties.geocategoryscope, 0 ) )";
			}
			$pl1 .= " )";*/

			$pl1 .= " AS p";

			$pl2 = " WHERE p=1";
		}

		$pl3 = "";
		if( !empty($geoText) ) {
			if( $geoCode ) {
				$pl3 = " WHERE MATCH('\"{$geoText}\"/1')";
				//$pl3 = " WHERE MATCH('{$geoText}')";
				$pl2 = " AND p=1";
			}else{
				$pl3 = " WHERE MATCH('\"{$geoText}\"/1')";
				//$pl3 = " WHERE MATCH('{$geoText}')";
			}
		}

		// Save into suggestion index for autocomplete
		if( !empty($geoText) ) {
			//TODO: implement suggestions
		}

		$offset = $perPage * $page;
		$sql = "SELECT *{$pl1} FROM ".SphinxStore::getInstance()->getIndex()."{$pl3}{$pl2} LIMIT {$offset},{$perPage} OPTION ranker=matchany;";
		//$sql = "SELECT *{$pl1} FROM ".SphinxStore::getInstance()->getIndex()."{$pl3}{$pl2} LIMIT {$offset},{$perPage};";

		$result = $query->query( $sql )->execute();

		if( $result->count() ) {
			foreach ( $result as $r ) {

				$title = Title::newFromID($r['id']);
				$properties = json_decode($r['properties'], true);

                $strUpdated = '';
                if( isset($properties['modification_date']) && count($properties['modification_date']) && !empty($properties['modification_date'][0]) ) {
                    $strUpdated = $properties['modification_date'];
                }else{
                    $strUpdated = date('j F Y', wfTimestamp(TS_UNIX, $title->getTouched()));
                }

                $pageAuthor = User::newFromId( $title->getFirstRevision()->getUser() );
                $pageAuthorStr = $pageAuthor->getName();
                $pageAuthorImg = "/extensions/SettleGeoSearch/assets/img/avatar-large.png";
                if( OpauthProfile::exists( $pageAuthor->getId() ) ) {
                    $profile = new OpauthProfile( $pageAuthor->getId() );
                    if( $profile->image ) {
                        $pageAuthorImg = $profile->image;
                    }
                }

                $pageContribsCount = 1;
                $page = WikiPage::factory( $title );
                $tEditors = $page->getContributors();
                $pageContribsCount = $tEditors->count();


				$item = array(
					'real_title'  => $r['page_title'],
					'page_author' => $pageAuthorStr,
					'page_author_image' => $pageAuthorImg,
					'contributors' => $pageContribsCount,
					'userlink' => $pageAuthor->getUserPage()->getFullURL(),
					'url'         => $title->getFullURL(),
					'title'       => $r['alias_title'],
					'city'        => isset($properties['city']) ? $properties['city'][0] : false,
					'country'     => isset($properties['country']) ? $properties['country'][0] : false,
					'state'       => isset($properties['state']) ? $properties['state'][0] : false,
					'tags'        => isset($properties['tags']) ? $properties['tags'] : false,
					'updated'     => $strUpdated, //isset($properties['modification_date']) ? $properties['modification_date'][0] : '',
					'description' => isset($properties['short_description']) ? $properties['short_description'][0] : wfMessage('settlegeosearch-special-result-no-description-provided')->plain(),
					'processing_time' => isset($properties['processing_time']) ? wfMessage('sil-card-processing-time-value-'.$properties['processing_time'][0])->plain() : '?',
					'total_cost' => isset($properties['total_cost']) ? $properties['total_cost'][0] : '?',
					'total_cost_cur' => isset($properties['total_cost_currency']) ? $properties['total_cost_currency'][0] : '',
					'difficulty' => isset($properties['difficulty']) ? wfMessage('sil-card-difficulty-value-'.$properties['difficulty'][0])->plain() : '?',
					'location_text' => SettleGeoSearch::formatLocationBreadcrumbs( $properties, 'location-in-search' )
				);

				$data['items'][] = $item;

			}
		}
		$data['count'] = $result->count();

		$data['page'] = $page;
		$data['perPage'] = $perPage;
		$data['taglink'] = SpecialPage::getTitleFor('SearchByProperty')->getFullURL().'/Tags/';
		$data['geoText'] = $geoText;

		// Geo input & Form URL, apply preselect field to the geo-input
		if( $geoCode && $entity instanceof MenaraSolutions\Geographer\Divisible ) {
			$data['input'] = $search->getHtml( SettleGeoSearch::SGS_MODE_VALUE, 'geo_id', '', $geoCode, $entity->inflict('default')->getShortName() );
		}else{
			$data['input'] = $search->getHtml( SettleGeoSearch::SGS_MODE_VALUE, 'geo_id' );
		}
		$data['formurl'] = SettleGeoSearch::getSearchPageUrl();

		$templater = new TemplateParser( dirname(__FILE__) . '/../templates/special/', true );
		$html = $templater->processTemplate( $template, $data );
		$this->getOutput()->addHTML( $html );

	}

	/**
	 * @deprecated since Sphinx integration
	 */
	private function renderResultsEx() {

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
						'updated'     => $strUpdated, //$properties['Modification date'] ? $properties['Modification date'][0] : '',
						'description' => $properties['Short description'] ? $properties['Short description'][0] : wfMessage('settlegeosearch-special-result-no-description-provided')->plain(),
						'processing_time' => $properties['Processing time'] ? wfMessage('sil-card-processing-time-value-'.$properties['Processing time'][0])->plain() : '?',
						'total_cost' => $properties['Total cost'] ? $properties['Total cost'][0] : '?',
						'total_cost_cur' => $properties['Total cost currency'] ? $properties['Total cost currency'][0] : '',
						'difficulty' => $properties['Difficulty'] ? wfMessage('sil-card-difficulty-value-'.$properties['Difficulty'][0])->plain() : '?'
					);

					$data['items'][] = $item;

				}
			}

			$data['count'] = count($result);

		//}

		$data['page'] = $page;
		$data['perPage'] = $perPage;
		$data['taglink'] = SpecialPage::getTitleFor('SearchByProperty')->getFullURL().'/Tags/';

		$templater = new TemplateParser( dirname(__FILE__) . '/../templates/special/', true );
		$html = $templater->processTemplate( $template, $data );
		$this->getOutput()->addHTML( $html );

	}

}