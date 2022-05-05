<?php


class OpenWeatherMap
{
    /**
     * @var string
     */
    protected $_key;

    protected $_validUnits = array('Fahrenheit' => 'imperial', 'Celsius' => 'metric');

    protected $_units = 'imperial';

    /**
     * Holds the last JSON result
     * @var string
     */
    protected $_result;

    function __construct($key, $units = 'Fahrenheit')
    {
        $this->_key = $key;
        if(isset($this->_validUnits[$units])) {
            $this->_units = $this->_validUnits[$units];
        }
    }

    protected function _getUrl($zip)
    {
        return 'http://api.openweathermap.org/data/2.5/weather?'
               . 'zip=' . $zip . ',es'
               . '&appid=' . $this->_key
               . '&units=' . $this->_units;
    }

    /**
     * Make the actual request for the json and decode it.
     * @param string $zip
     * @return mixed|null
     */
    protected function _requestWeather($zip)
    {
        $ch = curl_init();
        try {
            curl_setopt_array($ch, array(
                CURLOPT_URL => $this->_getUrl($zip),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => array('Accept: application/json'),
            ));
            $output = curl_exec($ch);
            curl_close($ch);
            return is_string($output) ? json_decode($output, true) : null;
        } catch (Exception $e) {
            curl_close($ch);
            print_r($e);
            return null;
        }
    }

    public function getWeather($zip)
    {
        if ($data = $this->_requestWeather($zip)) {
            if (isset($data['cod']) && $data['cod'] === 200) {
                return new OpenWeatherMapWeather($data);
            }
        }
    }

    /**
     * Checks to see if we can get the weather at all
     * @param $key
     * @param $zip
     * @return bool|string
     */
    public static function validate($key, $zip)
    {
        $owm = new self($key);
        if ($data = $owm->_requestWeather($zip)) {
            if(isset($data['cod']) && $data['cod'] === 200) {
                return true;
            }
            return isset($data['message']) ? $data['message'] : false;
        }
        return false;
    }
}