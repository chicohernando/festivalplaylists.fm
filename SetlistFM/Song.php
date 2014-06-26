<?php


class SetlistFM_Song {
	
    private $name;
    
    private $with; //artist
    
    private $cover; //artist
    
    private $info;
    
    private $position;

    private $spotifyUri;
    
    function __construct($name, $with, $cover, $info, $position = 0) {
        $this->name = $name;
        $this->with = $with;
        $this->cover = $cover;
        $this->info = $info;
        $this->position = $position;
        $this->spotifyUri = '';
    }

    public static function getNormalizedNameStatic($name) {
        $name = strtolower($name);
        $name = preg_replace('/&/i', 'and', $name);
        $name = preg_replace('/[^a-z0-9 ]/i', '', $name);
        $name = preg_replace('/\s\s+/i', ' ', $name);
        return $name;
    }

    public function getNormalizedName() {
        return SetlistFM_Song::getNormalizedNameStatic($this->getName());
    }

    public function setSpotifyUri($spotifyUri) {
        $this->spotifyUri = $spotifyUri;
    }

    public function getSpotifyUri() {
        return $this->spotifyUri;
    }

    
    public function getName() {
        return $this->name;
    }

    public function getWith() {
        return $this->with;
    }

    public function getCover() {
        return $this->cover;
    }

    public function getInfo() {
        return $this->info;
    }
    
    public function getPosition() {
        return $this->position;
    }

    public static function sortByCount($song1, $song2) {
        if ($song1->count == $song2->count) {
            return 0;
        } else if ($song1->count > $song2->count) {
            return -1;
        } else {
            return 1;
        }
    }
    
    public static function fromSimpleXMLElement(SimpleXMLElement $xml, $position = 0){
		return new SetlistFM_Song(
			SetlistFM_Util::toString($xml->attributes()->name),
			isset($xml->with) ? SetlistFM_Artist::fromSimpleXMLElement($xml->with) : null,
            isset($xml->cover) ? SetlistFM_Artist::fromSimpleXMLElement($xml->cover) : null,
            isset($xml->info) ? SetlistFM_Util::toString($xml->info) : null,
            $position
		);
	}
}