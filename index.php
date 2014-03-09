<?php

if (!file_exists(__DIR__ . '/config.php')) {
  die('Hey, you need to set up config.php. Check out README.md.');
}
if (!is_writable(__DIR__ . '/cache')) {
  die('Please make the cache directory writable by the web daemon user.');
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/weather-ch.php';

$app = new Weather_CH(
  $yahoo_app_id,
  $cooper_hewitt_access_token
);

$query = '';
if (!empty($_GET['q'])) {
  $query = $_GET['q'];
  // Do we still have to do this? Thanks Obama!
  if (get_magic_quotes_gpc()) {
    $query = stripslashes($query);
  }
}

?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Weather + Object</title>
  </head>
  <body>
    <h1>Weather + Object</h1>
    <form action=".">
      <input name="q" value="<?php echo $app->attr_esc($query); ?>" placeholder="Search for a place">
      <button type="submit">Go</button>
    </form>
    <?php
    
    if (!empty($_GET['q'])) {
      $place = $app->get_place($_GET['q']);
      if (!empty($place)) {
        $response = 'No place was found!';
        $country_woeid = $app->find_country_woeid($place);
        $place_woeid = $app->find_place_woeid($place);
        $weather = $app->get_weather($place_woeid);
        $object = $app->get_object($country_woeid);
        echo "<p>place: $place_woeid / country: $country_woeid</p>";
        echo "<pre>";
        print_r($weather);
        print_r($object);
        echo "</pre>";
      } else {
        echo "<p class=\"error\">Error: could not find that place.</p>";
      }
    }
    
    ?>
  </body>
</html>
