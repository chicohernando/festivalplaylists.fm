// function to make jQuery support what would otherwise be .animate({height: "auto"});
jQuery.fn.animateAuto = function(prop, speed, callback){
    var elem, height, width;
    return this.each(function(i, el){
        el = jQuery(el), elem = el.clone().css({"height":"auto","width":"auto"}).appendTo("body");
        height = elem.css("height"),
        width = elem.css("width"),
        elem.remove();
        
        if(prop === "height")
            el.animate({"height":height}, speed, callback);
        else if(prop === "width")
            el.animate({"width":width}, speed, callback);  
        else if(prop === "both")
            el.animate({"width":width,"height":height}, speed, callback);
    });  
}

jQuery('#save_to_spotify').click(function(e) {
	e.preventDefault();
	/**
	 * Find all artists, reverse the order so we get them in the order that
	 * the user inserted them, then find the tracks in the order that is being
     * displayed to the user on the site, then return the uris.
	 */
	var spotify_track_uris = jQuery(jQuery('#results').find('[data-type=spotify-artist]').get().reverse()).map(function() {
		return jQuery(this).find('[data-type=spotify-track]').map(function() {
   			return jQuery(this).data('uri');
		}).get();
	}).get();
	
	console.log(spotify_track_uris);
});

jQuery('#search_form').submit(function(e) {
	e.preventDefault();

	var artist_name = jQuery('input[name=artist_name]').val()
	if (artist_name !== '') {
		console.log('Searching for songs by ' + artist_name);
		jQuery.ajax({
			url: jQuery(this).attr('action'),
			type: jQuery(this).attr('method'),
			data: jQuery(this).serialize(),
			dataType: 'text',
			success: function(response) {
				console.log('Success');
				//console.log(response);
				// jQuery('.festival-container').animate({height: "auto"});
				jQuery(".festival-container").animateAuto("height", 500); 
				jQuery('#save_to_spotify').fadeIn();
				jQuery('#results').prepend(response);
			},
			error: function(response) {
				console.log('Error');
				console.log(response);
			}
		});
	} else {
		console.log('No artist name given');
	}
});