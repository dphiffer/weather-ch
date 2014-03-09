<?php

// Combines weather information with objects from the Cooper-Hewitt's collection
// See README.md for more details.

class Weather_CH {
  
  // Should be set by the constructor
  var $yahoo_key = null;
  
  // These shoudn't need to change often
  var $geoplanet_base = 'http://where.yahooapis.com/v1';
  var $weather_base = 'http://weather.yahooapis.com/forecastrss';
  
  function __construct($yahoo_key) {
    $this->yahoo_key = $yahoo_key;
  }
  
  // Takes a place search query and returns the first Yahoo GeoPlanet object
  // or null if none is found
  function get_place($query) {
    $query = rawurlencode($query);
    if (empty($query)) {
      return null;
    }
    $url = "{$this->geoplanet_base}/places.q('$query')" .
           "?appid={$this->yahoo_key}&format=json";
    $result = $this->curl($url);
    if (empty($result)) {
      return null;
    }
    $result = @json_decode($result);
    if (empty($result->places->place)) {
      return null;
    }
    return $result->places->place[0];
  }
  
  // Takes a WOEID and returns the weather result from the Yahoo Weather API as
  // a string (or null if unsuccessful)
  function get_weather($woeid, $units = 'f') {
    $woeid = intval($woeid); // Sanity check
    if (empty($woeid) || ($units != 'f' && $units != 'c')) {
      return null;
    }
    $url = "{$this->weather_base}?w=$woeid&u=$units";
    $result = $this->curl($url);
    if (empty($result)) {
      return null;
    }
    return $result;
  }
  
  // Takes an XML string and returns a SimpleXML object of the RSS (or null if
  // unsuccessful)
  function parse_weather($xml_str) {
    $rss = @simplexml_load_string($xml_str);
    if (empty($rss->channel)) {
      return null;
    }
    $yweather_ns = 'http://xml.weather.yahoo.com/ns/rss/1.0';
    $rss->registerXPathNamespace('yweather', $yweather_ns);
    return $rss;
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
  
  // Defend against XSS
  function attr_esc($untrusted) {
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
