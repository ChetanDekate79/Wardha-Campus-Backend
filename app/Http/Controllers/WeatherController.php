<?php

namespace App\Http\Controllers;
use GuzzleHttp\Client;

use Illuminate\Http\Request;

class WeatherController extends Controller
{
    public function getCurrentWeather(Request $request)
    {
        $apiKey = 'f792813bccb848a6ad672008232511';
        $city = $request->input('city', 'wardha'); // Default to 'wardha' if no city is provided

        $client = new Client();
        $response = $client->get("http://api.weatherapi.com/v1/current.json?key={$apiKey}&q={$city}&aqi=no");

        $weatherData = json_decode($response->getBody(), true);

        return response()->json($weatherData);
    }
}
