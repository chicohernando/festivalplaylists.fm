<?php
	require_once __DIR__ . '/../vendor/autoload.php';
	require_once __DIR__ . '/../SetlistFM/setlistfm.api.php';
	require_once __DIR__ . '/../Spotify/' . 'spotify.class.php';
	require_once __DIR__ . '/../chicohernando/FestivalPlaylistHelper.class.php';
	use Symfony\Component\HttpFoundation\Request;
	use Symfony\Component\HttpFoundation\Response;
	use Symfony\Component\Yaml\Parser;

	$app = new Silex\Application();
	$app['debug'] = true;

	$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
	$app->register(new Silex\Provider\TwigServiceProvider(), array(
	    'twig.path' => __DIR__ . '/../views',
	));
	$app['twig']->addExtension(new Twig_Extension_StringLoader());

	$yaml = new Parser();
	$playlist_config = $yaml->parse(file_get_contents(__DIR__ . '/../config/playlists.yml'));
	$app['playlists'] = $playlist_config['playlists'];

	$app->error(function (\Exception $e, $code) {
	    switch ($code) {
	        case 404:
	            $message = 'The requested page could not be found.';
	            break;
	        default:
	            $message = 'We are sorry, but something went terribly wrong.';
	    }

	    return new Response($message);
	});

	/**
	 * Homepage
	 */
	$app->get('/', function() use($app) { 
		return $app['twig']->render('index.html.twig', array(
	        'playlists' => $app['playlists']
	    ));
	})->bind('home');

	/**
	 * Testing out putting a playable iframe playlist
	 */
	$app->get('/iframe', function() use ($app) {
		return $app['twig']->render('iframe.html.twig', array(
	        
	    ));
	})->bind('iframe');

	$app->get('/playlist/{slug}', function($slug) use ($app) {
		if (!isset($app['playlists'][$slug])) {
			return $app->abort(404, 'No playlist found.');
		}

		$playlist = $app['playlists'][$slug];
		$md5_uri = md5($playlist['spotify_uri']);

		if (file_exists($md5_uri)) {
			$playlist_json = file_get_contents($md5_uri);
		} else {
			$playlist_json = file_get_contents("http://tomashenden.com/projects/spotify-php-playlist.php?uri=" . $playlist['spotify_uri'] . "&output=json");
			file_put_contents($md5_uri, $playlist_json);
		}

		$playlist_json = json_decode($playlist_json);
		$tracks = array();

		foreach($playlist_json->tracks as $track) {
			$tracks[$track->artist][] = $track;
		}

		return $app['twig']->render('playlist.html.twig', array(
			'playlist_uri' => $playlist['spotify_uri'],
			'playlist_title' => $playlist['name'],
			'tracks' => $tracks,
			'song_count' => count($playlist_json->tracks),
			'content' => $playlist['content'],
			'playlists' => $app['playlists']
	    ));
	})->bind('playlist');
	
	/**
	 * Handle the search for an artist via Spotify
	 */
	$app->post('/artist-search', function(Request $request) use ($app) {
		$artist_name = $request->get('artist_name');
		$setlists = null;

		try {
			$spotify_results = FestivalPlaylistHelper::searchSpotifyForArtist($artist_name);
			$artist = $spotify_results->artists->items[0];

			$setlists = SetlistFM_Setlist::search(array('artistName' => $artist->name));
			print_r($setlists);exit;
			
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
		} catch (ResultsNotFoundException $rnfe) {
			$artist = null;
		} catch (ArtistNotFoundException $anfe) {
			$artist = null;
		} catch (Exception $e) {
			$artist = null;
		}

		if (!isset($artist) || count($artist->songs) == 0) {
			return new Response($app['twig']->render('artist-search-error.html.twig', array(
				'message' => 'No songs could be found that match your search'
			)), 404);
		} else {
			return $app['twig']->render('artist-search.html.twig', array(
				'artist' => $artist
			));
		}
	})->bind('artist-search');

	/**
	 * Handler for the sitemap of the site.
	 *
	 */
	$app->get('/sitemap', function(Request $request) use ($app) {
		$app->register(new TM\Provider\SitemapServiceProvider());

		$app['sitemap']->addEntry($app['url_generator']->generate('home', array(), true), 1, 'daily');
		// Need to refactor this once there are significantly more playlits
		foreach ($app['playlists'] as $playlist) {
			$app['sitemap']->addEntry($app['url_generator']->generate('playlist', array('slug' => $playlist['slug']), true), 2, 'monthly');
		}

		return new Response($app['sitemap']->generate(), 200, array('Content-Type' => 'application/xml'));
	})->bind('sitemap');

	$app->run();
?>