(function( mw ){

	/**
	 * @param wrapper
	 * @constructor
	 */
	var SettleGeoSearch = function( wrapper ) {

		this._$wrapper = $(wrapper);
		this._$input = this._$wrapper.find('.settle-geo-search-input');
		this._selectize = null;
		this._resultTemplate = null;
		this._mode = null;
		this._preselected = null;
		this.initialize();

	};

	/**
	 * Initialization function runs from constructor
	 */
	SettleGeoSearch.prototype.initialize = function () {

		this._resultTemplate = mw.template.get( 'ext.settlegeosearch.main', 'result.js.mustache' );
		this._mode = this._$input.data('input-mode');

		if( this._$input.data('preselected-text').length && this._$input.data('preselected-code') ) {
			this._preselected = {
				text: this._$input.data('preselected-text'),
				code: this._$input.data('preselected-code')
			}
		}

		var self = this;

		this._$input.selectize({
			valueField: 'code', //(this._mode == 1) ? 'code' : 'label',
			hashField: 'code',
			labelField: 'label',
			searchField: 'label',
			selectOnTab: true,
			create: false,
			persist: false,
			closeAfterSelect: true,
			plugins: ['restore_on_backspace'],
			loadThrottle: 300,
			highlightContainerSelector: '.settle-geo-result-item-main',
			preload: false,
			render: {
				option: function(item, escape) {
					return self._resultTemplate.render( item );
				}
			},
			load: function(query, callback) {
				//if (!query.length) return callback();
				$.ajax({
					url: mw.config.get('wgServer') + mw.config.get('wgScriptPath') + '/api.php?action=settlegeosearch&format=json&term=' + encodeURIComponent(query),
					type: 'GET',
					error: function() {
						callback();
					},
					success: function(res) {
						callback(res.settlegeosearch.items);
					}
				});
			},
			onInitialize: function() {
				if( self._preselected ) {
					self._$input[0].selectize.addOption({
						'code': self._preselected.code,
						'value': self._preselected.code,
						'label': self._preselected.text
					});
					self._$input[0].selectize.setValue(self._preselected.code);
				}
			}
		});

		this._selectize = this._$input[0].selectize;

	};

	mw.settlegeosearch = SettleGeoSearch;

})( mediaWiki );