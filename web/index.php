<?php
	require_once __DIR__ . '/../vendor/autoload.php';
	require_once __DIR__ . '/../SetlistFM/setlistfm.api.php';
	require_once __DIR__ . '/../Spotify/' . 'spotify.class.php';
	use Symfony\Component\HttpFoundation\Request;
	use Symfony\Component\HttpFoundation\Response;

	$app = new Silex\Application();
	$app['debug'] = true;

	$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
	$app->register(new Silex\Provider\TwigServiceProvider(), array(
	    'twig.path' => __DIR__ . '/../views',
	));

	/**
	 * Homepage
	 */
	$app->get('/', function() use($app) { 
	    return $app['twig']->render('index.html.twig', array(
	        
	    ));
	})->bind('home');

	/**
	 * Handle the search for an artist via Spotify
	 */
	$app->post('/artist-search', function(Request $request) use ($app) {
		$artist_name = $request->get('artist_name');
		$spotify_results = file_get_contents('https://api.spotify.com/v1/search?q=' . urlencode($artist_name) . '&type=artist');
		$spotify_results = json_decode($spotify_results);
		$artist = $spotify_results->artists->items[0];
		$setlists = SetlistFM_Setlist::search(array('artistName' => $artist->name));
		
		if (is_array($setlists)) {
			$songs = array();
			foreach ($setlists as $setlist) {
				//setlist is a SetlistFM_Setlist object
				foreach ($setlist->getSets() as $set) {
					//set is a SetlistFM_Setlist object
					foreach ($set->getSongs() as $song) {
						//song is a SetlistFM_Song object
						if (isset($songs[$song->getNormalizedName()])) {
							$songs[$song->getNormalizedName()]->count++;
						} else {
							if ($song->getName() == '' || $song->getNormalizedName() == '') {
								continue;
							}

							$songs[$song->getNormalizedName()] = new stdClass();
							$songs[$song->getNormalizedName()]->name = $song->getName();
							$songs[$song->getNormalizedName()]->count = 1;
							$songs[$song->getNormalizedName()]->spotifyUri = '';
						}
					}
				}
			}

			uasort($songs, array('SetlistFM_Song', 'sortByCount'));

			$count = 0;
			foreach ($songs as $song) {
				if ($count >= 5) {
					break; 
				}

				$searchResults = Spotify::searchTrack(implode(' ', array($artist->name, $song->name)));
				if (!empty($searchResults) && isset($searchResults->tracks[0])) {
					$count++;
					$s = new stdClass();
					$s->name = $song->name;
					$s->uri = $searchResults->tracks[0]->href;
					$s->duration = ltrim(ltrim(gmdate("i:s", $searchResults->tracks[0]->length), '0'), ':');
					$artist->songs[] = $s;
				}
			}
		}

		return $app['twig']->render('artist-search.html.twig', array(
			'artist' => $artist
		));
	})->bind('artist-search');

	$app->run();
?>