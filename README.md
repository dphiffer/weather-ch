# weather-ch

A basic weather app that also includes objects from the Cooper-Hewitt collection

## Requirements

* PHP 5.2 or greater
* PHP cURL extension
* PHP libxml extension

## Installing

If you want to run this code on your own server:

1. [Register your app](https://developer.apps.yahoo.com/wsregapp/) on the Yahoo Developer Network
2. [Register your app again](https://collection.cooperhewitt.org/api/keys/register/) with the Cooper-Hewitt Collection API
3. Click "Create an access token for yourself using this API key" on your Cooper-Hewitt app page, fill in **Permissions** as **read**, click the checkbox, and the button "Create"
4. Copy `config.php.example` to `config.php` and drop your Yahoo API key and the access token you just generated in there
5. Make the 'cache' directory writable by the web daemon user

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

I added another method `get_weather($woeid)` to `Weather_CH` that downloads data from the Yahoo Weather RSS service and returns the current weather conditions (and units) as an object.

## Third step: get the object info

Working with the Cooper-Hewitt API was super easy! Being able to generate the OAuth token without doing the usual token exhange tango was a nice touch. I was able to pull data from the collection on my first try, which is rarely the case.

I added one more top-level method for working with the Cooper-Hewitt Collection API, `get_object($woeid)`. It retrieves a random object that's from the same country the weather is being shown for. The collection object data is returned as ... (you guessed it) an object.

I also added a caching layer to be a good Internet Citizen. All the basic parts are in place now!
 
## Fourth step: refactoring and styling

I took the basic data from the previous steps and did some light refactoring. A lot of this had to do with slimming down the amount of code in `index.php` and shifting it to the application class. I whipped up a simple, mobile-first CSS treatment to show the data, and some copy that helps orient a website visitor about what it is they're looking at.

The main thing this project shows is the breadth of the Cooper-Hewitt collection, across many countries beyond the United States. Perhaps it could serve to raise the international profile of the museum just a bit by providing a functional weather app for design enthusiasts. If I had a bit more time I would expose a control to switch between fahrenheit and celsius, defaulting to celsius if the country being searched for is outside the small list of non-metric observers.

