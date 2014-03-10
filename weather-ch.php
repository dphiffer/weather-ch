<?php

// Combines weather information with objects from the Cooper-Hewitt's collection
// See README.md for more details.

class Weather_CH {
  
  // Set by the constructor when creating an instance object
  var $yahoo_key = null;
  var $cooper_hewitt_access_token = null;
  
  // How long to cache the search query as a cookie
  var $query_cookie_ttl = 31536000; // 1 year
  
  // How long to keep API results cached for
  var $place_cache_ttl   = 86400; // 24 hours
  var $weather_cache_ttl = 60;    // 1 minute
  var $objects_cache_ttl = 86400; // 24 hours
  
  // These shoudn't need to change often
  var $geoplanet_base = 'http://where.yahooapis.com/v1';
  var $weather_base = 'http://weather.yahooapis.com/forecastrss';
  var $cooper_hewitt_base = 'https://api.collection.cooperhewitt.org/rest/';
  
  function __construct($yahoo_key, $cooper_hewitt_access_token) {
    $this->yahoo_key = $yahoo_key;
    $this->cooper_hewitt_access_token = $cooper_hewitt_access_token;
  }
  
  // Returns the current search query param 'q', or its equivalent from a
  // long-storage cookie
  function get_query() {
    $query = null;
    if (!empty($_GET['q'])) {
      $query = $_GET['q'];
      $expires = time() + $this->query_cookie_ttl;
      setcookie('q', $query, $expires);
    } else if (!empty($_COOKIE['q'])) {
      $query = $_COOKIE['q'];
    }
    // Do we still have to do this? Thanks Obama!
    if (get_magic_quotes_gpc()) {
      $query = stripslashes($query);
    }
    return $query;
  }
  
  // Takes a place search query and returns the first Yahoo GeoPlanet object
  // (or null if none is found)
  function get_place($query) {
    $places_json = $this->load_places($query);
    $places_list = $this->parse_places($places_json);
    $place = $this->choose_place($places_list);
    if (empty($place)) {
      return null;
    }
    return $place;
  }
  
  // Takes a place WOEID and returns the weather information from the Yahoo
  // Weather API as an object (or null if unsuccessful)
  function get_weather($place_woeid) {
    $weather_xml = $this->load_weather($place_woeid);
    $weather_obj = $this->parse_weather($weather_xml);
    if (empty($weather_obj)) {
      return null;
    }
    $weather = $this->find_condition($weather_obj);
    $units = $this->find_units($weather_obj);
    if (empty($weather) || empty($units)) {
      return null;
    }
    $weather['units'] = (object) $units;
    $weather['url'] = "{$weather_obj->channel->link}";
    return (object) $weather;
  }
  
  // Takes a country WOEID and searches for objects in the Cooper-Hewitt
  // Collection from that country, returns one object chosen at random (or null
  // if unsuccessful)
  function get_object($country_woeid) {
    $objects_json = $this->load_objects($country_woeid);
    $objects_list = $this->parse_objects($objects_json);
    $object = $this->choose_object($objects_list);
    if (empty($object)) {
      return null;
    }
    return $object;
  }
  
  // Takes a place search query and returns the Yahoo GeoPlanet API results
  // as a JSON string (or null if unsuccessful)
  function load_places($query) {
    $query = rawurlencode($query);
    if (empty($query)) {
      return null;
    }
    $hash = md5($query);
    $filename = "place_$hash.json";
    $result = $this->get_cached_file($filename, $this->place_cache_ttl);
    if (!empty($result)) {
      return $result;
    }
    $url = "{$this->geoplanet_base}/places.q('$query')" .
           "?appid={$this->yahoo_key}&format=json";
    $result = $this->curl($url);
    if (empty($result)) {
      return null;
    }
    $this->cache_result_data($filename, $result);
    return $result;
  }
  
  // Takes places JSON string and returns an array of place objects
  function parse_places($places_json) {
    $places = @json_decode($places_json);
    if (empty($places) ||
        empty($places->places) ||
        empty($places->places->place)) {
      return null;
    }
    return $places->places->place;
  }
  
  // Returns the first place object from the list
  function choose_place($places_list) {
    if (empty($places_list)) {
      return null;
    }
    return $places_list[0];
  }
  
  // Takes a place WOEID and returns the weather result from the Yahoo Weather
  // API as an XML string (or null if unsuccessful)
  function load_weather($woeid, $units = 'f') {
    $woeid = intval($woeid); // Sanity check
    if (empty($woeid) || ($units != 'f' && $units != 'c')) {
      return null;
    }
    $filename = "weather_{$woeid}_{$units}.xml";
    $result = $this->get_cached_file($filename, $this->weather_cache_ttl);
    if (!empty($result)) {
      return $result;
    }
    $url = "{$this->weather_base}?w=$woeid&u=$units";
    $result = $this->curl($url);
    if (empty($result)) {
      return null;
    }
    $this->cache_result_data($filename, $result);
    return $result;
  }
  
  // Takes an XML string and returns a SimpleXML object of the RSS (or null if
  // unsuccessful)
  function parse_weather($xml_str) {
    $rss = @simplexml_load_string($xml_str);
    if (empty($rss) ||
        empty($rss->channel)) {
      return null;
    }
    $yweather_ns = 'http://xml.weather.yahoo.com/ns/rss/1.0';
    $rss->registerXPathNamespace('yweather', $yweather_ns);
    return $rss;
  }
  
  // Takes a country WOEID and searches for objects in the Cooper-Hewitt
  // Collection from that country, returned as a JSON string (or null if
  // unsuccessfull)
  function load_objects($woeid) {
    $woeid = intval($woeid); // Sanity check
    if (empty($woeid)) {
      return null;
    }
    $filename = "objects_$woeid.json";
    $result = $this->get_cached_file($filename, $this->objects_cache_ttl);
    if (!empty($result)) {
      return $result;
    }
    $url = $this->cooper_hewitt_base .
           "?method=cooperhewitt.search.objects" .
           "&access_token={$this->cooper_hewitt_access_token}" .
           "&woe_id=$woeid" .
           "&has_images=1";
    $result = $this->curl($url);
    if (empty($result)) {
      return null;
    }
    $this->cache_result_data($filename, $result);
    return $result;
  }
  
  // Takes JSON text and returns an array from the parsed object (null if
  // unsuccessful)
  function parse_objects($objects_json) {
    $objects_obj = @json_decode($objects_json);
    if (empty($objects_obj) ||
        empty($objects_obj->objects)) {
      return null;
    }
    return $objects_obj->objects;
  }
  
  // Returns a random object from Cooper-Hewitt search results object
  function choose_object($objects) {
    $count = count($objects);
    $index = rand(0, $count - 1);
    return $objects[$index];
  }
  
  // Returns a human-readable version of the place
  function find_place_name($place) {
    $country_attrs = 'country attrs';
    if (empty($place) ||
        empty($place->$country_attrs)) {
      return null;
    }
    return "{$place->name}, {$place->$country_attrs->code}";
  }
  
  // Returns a human-readable version of the country
  function find_country_name($place) {
    if (empty($place)) {
      return null;
    }
    return $place->country;
  }
  
  // Returns a large representative image from the object
  function find_image($object, $size = 'b') {
    if (empty($object) ||
        empty($object->images)) {
      return null;
    }
    $image = $object->images[0];
    if (empty($image->$size)) {
      return null;
    }
    return $image->$size;
  }
  
  // Takes a place object from GeoPlanet API and returns a numeric WOEID from
  // that place's country (or null if one is not found)
  function find_country_woeid($place) {
    $country_attrs = "country attrs";
    if (empty($place) ||
        empty($place->$country_attrs) ||
        empty($place->$country_attrs->woeid)) {
      return null;
    }
    return $place->$country_attrs->woeid;
  }
  
  // Takes a place object from GeoPlanet API and returns that place's numeric
  // WOEID (or null if one is not found)
  function find_place_woeid($place) {
    if (empty($place) ||
        empty($place->woeid)) {
      return null;
    }
    return $place->woeid;
  }
  
  // Searches SimpleXML input for <yweather:current> and returns an associative
  // array of current weather conditions (or null if not found)
  function find_condition($weather_obj) {
    $condition_nodes = $weather_obj->xpath('//yweather:condition');
    if (empty($condition_nodes)) {
      return null;
    }
    $condition = $condition_nodes[0];
    return array(
      'text' => "{$condition['text']}",
      'code' => "{$condition['code']}",
      'temp' => "{$condition['temp']}",
      'date' => "{$condition['date']}"
    );
  }
  
  // Searches SimpleXML input for <yweather:units> and returns an associative
  // array of the units used (or null if not found)
  function find_units($weather_obj) {
    $units_nodes = $weather_obj->xpath('//yweather:units');
    if (empty($units_nodes)) {
      return null;
    }
    $units = $units_nodes[0];
    return array(
      'temperature' => "{$units['temperature']}",
      'distance' => "{$units['distance']}",
      'pressure' => "{$units['pressure']}",
      'speed' => "{$units['speed']}"
    );
  }
  
  // Returns the cached results from get_objects() if they exist
  function get_cached_file($filename, $ttl) {
    $path = __DIR__ . "/cache/$filename";
    if (!file_exists($path)) {
      return null;
    }
    $mtime = filemtime($path);
    if (time() - $mtime > $ttl) {
      // Expire the cached results; too old!
      return null;
    }
    return file_get_contents($path);
  }
  
  // Saves the results from get_objects() for later use
  function cache_result_data($filename, $data) {
    $path = __DIR__ . "/cache/$filename";
    file_put_contents($path, $data);
  }
  
  // Defend against XSS
  function escape($untrusted) {
    // Quick and dirty, there might some be unicode edge cases to handle
    return htmlentities($untrusted, ENT_QUOTES, 'utf-8');
  }
  
  // Takes a URL and returns resulting content (or null if HTTP status != 200)
  function curl($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $result = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($status != 200) {
      return null;
    } else {
      return $result;
    }
  }
  
}

?>
