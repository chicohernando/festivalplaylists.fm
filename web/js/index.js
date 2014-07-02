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
};

jQuery('[data-id=save-to-spotify]').click(function(e) {
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
	
	var client_id = 'bde96cbd16ec44548ab77ce86e187654';
	var redirect_uri = 'http://localhost:8000/callback.html';
	var url = 'https://accounts.spotify.com/authorize?client_id=' + client_id +
			  '&response_type=token' +
			  '&scope=playlist-read-private%20playlist-modify%20playlist-modify-private' +
			  '&redirect_uri=' + encodeURIComponent(redirect_uri);
	localStorage.setItem('spotify_track_uris', JSON.stringify(spotify_track_uris));
	localStorage.setItem('spotify_playlist_name', 'Hello World ' + (new Date()));
	var w = window.open(url, 'chicohernando', 'WIDTH=400,HEIGHT=500');
});

jQuery('#search_form').submit(function(e) {
	e.preventDefault();
	var artist_name = jQuery('input[name=artist_name]').val();
	if (artist_name !== '') {
		jQuery('.form-help span').removeClass("error").show().text("Searching for songs by " + artist_name + "...");
		console.log('Searching for songs by ' + artist_name);
		jQuery.ajax({
			url: jQuery(this).attr('action'),
			type: jQuery(this).attr('method'),
			data: jQuery(this).serialize(),
			dataType: 'text',
			success: function(response) {
				//console.log(response);
				// jQuery('.festival-container').animate({height: "auto"});
				jQuery(".festival-container").animateAuto("height", 500); 
				jQuery('[data-id=save-to-spotify]').css('display', 'block');
				jQuery('#results').prepend(response);
				jQuery('.form-help span').removeClass("error").hide();
				var artist_count = jQuery('[data-type=spotify-artist]').length;
				if (artist_count == 2) {
					jQuery('[data-id=save-to-spotify]').clone().prependTo('.playlist-container');
				}
			},
			error: function(response) {
				console.log('Error');
				jQuery('.form-help span').addClass("error").text("Sorry, something went wrong. Please try again.");
				console.log(response);
			}
		});
	} else {
		jQuery('.form-help span').addClass("error").show().text("Please enter an artist name.");
		console.log('No artist name given');
	}
});