<?php

if (!file_exists(__DIR__ . '/config.php')) {
  die('Hey, you need to set up config.php. Check out README.md.');
}
if (!is_writable(__DIR__ . '/cache')) {
  die('Please make the cache directory writable by the web daemon user.');
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/weather-ch.php';

// All the heavy lifting happens in Weather_CH (defined in weather-ch.php)
$app = new Weather_CH(
  $yahoo_app_id,
  $cooper_hewitt_access_token
);
$query = $app->get_query();

if (!empty($query)) {
  $place = $app->get_place($query);
  $country_woeid = $app->find_country_woeid($place);
  $place_woeid = $app->find_place_woeid($place);
  $weather = $app->get_weather($place_woeid);
  $object = $app->get_object($country_woeid);
}

?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Weather + Design</title>
    <link rel="stylesheet" href="weather-ch.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
  </head>
  <body class="<?php echo (!empty($weather) && !empty($object)) ? 'result' : 'form'; ?>">
    <div class="container">
      <h1><span class="weather">Weather</span> + <span class="design">Design</span></h1>
      <p class="about">Get the local weather and a design object chosen at random from the <a href="https://collection.cooperhewitt.org/">Smithsonian Cooper-Hewitt, National Design Museum’s collection</a>.</p>
      <form action=".">
        <input name="q" value="<?php echo $app->escape($query); ?>" placeholder="Search for a place">
        <button type="submit">Go</button>
      </form>
      <?php
      
      if (!empty($query) && (empty($weather) || empty($object))) {
        echo "<p class=\"error\">Oops, something went wrong!</p>";
      } else if (!empty($query)) {
        $place_name = $app->escape($app->find_place_name($place));
        $country = $app->escape($app->find_country_name($place));
        $url = $app->escape($object->url);
        $image = $app->find_image($object, 'z');
        $src = $app->escape($image->url);
        $weather_text = $app->escape($weather->text);
        $temp = $app->escape($weather->temp);
        $units = $app->escape($weather->units->temperature);
        $title = $app->escape($object->title);
        $medium = $app->escape($object->medium);
        $description = $app->escape($object->description);
        echo <<<END
          <p class="weather">
            Current weather in $place_name: <strong>$weather_text ($temp&deg;$units)</strong>
          </p>
          <h2>A randomly chosen design object that’s also from <strong>$country</strong></h2>
          <a href="$url" class="object">
            <img src="$src" alt="$title">
            $title
          </a>
          <p class="details">
            $medium<br>$description
          </p>
END;
      }
    
      ?>
    </div>
  </body>
</html>
