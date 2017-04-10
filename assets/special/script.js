$(function(){

    //TODO: remove duplications

    var textInput = $(this).find('.selectize-search-appendix');
    var geoInput = $(this).find('.settle-geo-search-input');
    var wrapper = $(this).find('.settle-geo-search-wrapper');

    $('#searchform_smw').submit(function(event){

        if( wrapper.find('.settle-geo-search-input-error').length ) {
            wrapper.find('.settle-geo-search-input-error').remove();
        }

        if( !geoInput.val() ) {
            var error = $('<div/>');
            error.html('Oops, please specify a location!');
            error.addClass('settle-geo-search-input-error');
            wrapper.append(error);
            geoInput.get(0).selectize.focus();
            event.preventDefault();
            return false;
        }

    });

    geoInput.on('change', function(){
        if( $(this).val() ) {
            if( wrapper.find('.settle-geo-search-input-error').length ) {
                wrapper.find('.settle-geo-search-input-error').remove();
            }
        }
    });

});