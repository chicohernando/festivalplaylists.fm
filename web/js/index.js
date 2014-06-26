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