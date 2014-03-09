# weather-ch

A basic weather app that also includes objects from the Cooper-Hewitt collection

## Installing

If you want to run this code on your own machine:

1. [Register your app](https://developer.apps.yahoo.com/wsregapp/) on the Yahoo Developer Network
2. Copy `config.php.example` to `config.php` and drop your API keys in there

## Commentary follows

What follows is my thinking about the project as I build it. Think of it as kind of like the DVD commentary audio track that complements the Git commit history. I am going to combine two APIs to make a mobile-friendly weather app that might be useful, amusing, enriching, or maybe some combination thereof. The two APIs are:

1. Cooper-Hewitt's [Collection API](https://collection.cooperhewitt.org/api/)
2. Yahoo's [Weather RSS API](http://developer.yahoo.com/weather/)

These are not APIs I've used before, but they both tag their data with Yahoo's [WOEID](http://developer.yahoo.com/geo/geoplanet/guide/concepts.html) location system, so that's where I'll join the data together.

## First step: search for WOEIDs

Let's get some WOEID search up in here. I am going to ask the user to search for a place they want the weather/object from using Yahoo's [GeoPlanet Web Service](http://developer.yahoo.com/geo/geoplanet/guide/api_docs.html). I almost always use Google's Geocoder service for stuff like this, but I like that with Yahoo's how you can ask for the parent of a place, and traverse upward until you hit a Country (which is how Cooper-Hewitt's objects are geolocated). Plus being able to plug a WOEID into the Cooper-Hewitt collection API will make everything go a lot smoother.

The first method of interest here can be found in weather-ch.php, `Weather_CH::get_place($query)`. It takes a search query and returns an object from the GeoPlanet API. This object has a WOEID for the place itself (used for getting the weather) and also the country the place is in (used for getting an object). So far so good.

## Second step: get the weather info

The Weather RSS service is nice because you don't need to authenticate anything. I plugged in the place WOEID I got back from the GeoPlanet Web Service, did some SimpleXML parsing on the result, and I seem to be in the weather business. I'm just going to focus on the current conditions, although I could easily see extending this to grab forecast numbers as well.

The first two weather-related methods I added to `Weather_CH` was `get_weather($woeid)` to download data from the Yahoo Weather RSS service and `parse_weather($xml_str)` to parse it into a SimpleXML object. Two more methods, `find_condition($weather_xml)` and `find_units($weather_xml)`, pull out the relevant information we can use to display the weather.
