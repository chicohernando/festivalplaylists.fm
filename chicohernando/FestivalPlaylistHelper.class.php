<?php
	require_once('ArtistNotFoundException.class.php');
	require_once('ResultsNotFoundException.class.php');

	class FestivalPlaylistHelper {
		/**
		 * Searches Spotify for an artist with the name of $artist.  Returns a json_decoded
		 * object with details of the search results.
		 *
		 * @param string $artist
		 * @return stdClass
		 * @throws ArtistNotFoundException
		 * @throws ResultsNotFoundException
		 */
		public static function searchSpotifyForArtist($artist = '') {
			$spotify_results = file_get_contents('https://api.spotify.com/v1/search?q=' . urlencode($artist) . '&type=artist');

			if (empty($spotify_results)) {
				throw new ResultsNotFoundException('No Spotify results were found for ' . $artist);
			}

			$spotify_results = json_decode($spotify_results);
			
			if (!isset($spotify_results->artists->items[0])) {
				throw new ArtistNotFoundException($artist . ' was not found in Spotify');
			}

			return $spotify_results;
		}
	}
?>