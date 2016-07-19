(function( mw ){

	/**
	 * @param wrapper
	 * @constructor
	 */
	var SettleGeoSearch = function( wrapper ) {

		this._$wrapper = $(wrapper);
		this._$input = this._$wrapper.find('.settle-geo-search-input');
		this._resultTemplate = null;
		this._mode = null;
		this.initialize();

	};

	/**
	 * Initialization function runs from constructor
	 */
	SettleGeoSearch.prototype.initialize = function () {

		this._resultTemplate = mw.template.get( 'ext.settlegeosearch.main', 'result.js.mustache' );
		this._mode = this._$input.data('input-mode');

		var self = this;

		this._$input.selectize({
			valueField: (this._mode == 1) ? 'code' : 'label',
			labelField: 'label',
			searchField: 'label',
			selectOnTab: true,
			create: false,
			persist: false,
			closeAfterSelect: true,
			plugins: ['restore_on_backspace'],
			loadThrottle: 300,
			highlightContainerSelector: '.settle-geo-result-item-main',
			render: {
				option: function(item, escape) {
					return self._resultTemplate.render( item );
				}
			},
			load: function(query, callback) {
				if (!query.length) return callback();
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
			}
		});

	};

	mw.settlegeosearch = SettleGeoSearch;

})( mediaWiki );