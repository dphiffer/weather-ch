<?php

if (!file_exists('config.php')) {
  die('Hey, you need to set up config.php. Check out README.md.');
}
require_once 'config.php';
require_once 'weather-ch.php';

$app = new Weather_CH($yahoo_app_id);

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
      <input name="q" value="<?php echo $app->attr_esc($_GET['q']); ?>" placeholder="Search for a place">
      <button type="submit">Go</button>
    </form>
    <?php
    
    if (!empty($_GET['q'])) {
      $place = $app->get_place($_GET['q']);
      if (!empty($place)) {
        $country_attrs = "country attrs";
        $place_woeid = $place->woeid;
        $country_woeid = $place->$country_attrs->woeid;
        echo "<p>place: $place_woeid / country: $country_woeid</p>";
      } else {
        echo "<p class=\"error\">Error: could not find that place.</p>";
      }
    }
    
    ?>
  </body>
</html>
