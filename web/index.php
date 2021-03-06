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

		try {
			$spotify_results = file_get_contents('https://api.spotify.com/v1/search?q=' . urlencode($artist_name) . '&type=artist');
			$setlists = null;
			
			if (!empty($spotify_results)) {
			    $spotify_results = json_decode($spotify_results);
			    if (isset($spotify_results->artists->items[0])) {
					$artist = $spotify_results->artists->items[0];
					$setlists = SetlistFM_Setlist::search(array('artistName' => '"' . $artist->name . '"'));
				}
			}
			
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

// 					$searchResults = Spotify::searchTrack(implode(' ', array($artist->name, $song->name)));
					// https://api.spotify.com/v1/search?q=album:out+come+the+wolves%20track:time+bomb&type=track
					$searchUrl = 'https://api.spotify.com/v1/search?q=artist:' . urlencode($artist->name) . '%20track:' . urlencode($song->name) . '&type=track&limit=1';
					
					$searchResults = file_get_contents($searchUrl);
					$searchResults = json_decode($searchResults);
					if (!empty($searchResults) && isset($searchResults->tracks) && isset($searchResults->tracks->items[0])) {
					    $track = $searchResults->tracks->items[0];
						$count++;
						$s = new stdClass();
						$s->name = $song->name;
// 						$s->uri = $searchResults->tracks[0]->href;
						$s->uri = $track->id;
						$s->duration = gmdate('i:s', $track->duration_ms / 1000);//ltrim(ltrim(gmdate("i:s", $track->duration), '0'), ':');
						$artist->songs[] = $s;
					}
				}
			}
		} catch (Exception $e) {
			$artist = null;
		}

		if (!isset($artist) || !isset($artist->songs) || count($artist->songs) == 0) {
			return new Response($app['twig']->render('artist-search-error.html.twig', array(
				'message' => 'No songs could be found that match your search'
			)), 404);
		} else {
			return $app['twig']->render('artist-search.html.twig', array(
				'artist' => $artist
			));
		}
	})->bind('artist-search');

	$app->run();
?>