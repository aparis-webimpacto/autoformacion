<?php


class OpenWeatherMapWeather
{
    public $headline;

    public $description;

    public $iconUrl;

    public $temp;

    public $tempMax;

    public $tempMin;

    public $humidity;

    public $locationName;

    public function __construct($data)
    {
        if(isset($data['weather'], $data['weather'][0])) {
            $weather = $data['weather'][0];
            $this->headline = isset($weather['main']) ? $weather['main'] : null;
            $this->description = isset($weather['description']) ? $weather['description'] : null;
            $this->iconUrl = isset($weather['icon']) ? 'http://openweathermap.org/img/w/'.$weather['icon'].'.png' : null;
        }
        if(isset($data['main'])) {
            $main = $data['main'];
            $this->humidity = isset($main['humidity']) ? $main['humidity'] : null;
            $this->temp = isset($main['temp']) ? $main['temp'] : null;
            $this->tempMax = isset($main['temp_max']) ? $main['temp_max'] : null;
            $this->tempMin = isset($main['temp_min']) ? $main['temp_min'] : null;
        }
        $this->locationName = isset($data['name']) ? $data['name'] : null;
    }
}