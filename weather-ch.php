<?php

class Weather_CH {
  
  // Should be set by the constructor
  var $yahoo_key = null;
  
  // Only change this when upgrading to a new API version
  var $geoplanet_base = 'http://where.yahooapis.com/v1';
  
  function __construct($yahoo_key) {
    $this->yahoo_key = $yahoo_key;
  }
  
  // Takes a place search query and returns the first Yahoo GeoPlanet object
  // or null if none is found
  function get_place($query) {
    $query = rawurlencode($query);
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
