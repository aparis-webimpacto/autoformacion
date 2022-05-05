<?php

class WeatherZipModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        global $cookie;
        $zip = strval(Tools::getValue('zipcode'));
        if ($zip) {
            $zip = filter_var(trim($zip), FILTER_SANITIZE_STRING);
            if (empty($zip) || !Validate::isZipCodeFormat($zip)) {
                $cookie->weather_module_zipcode_error = 'Invalid zip code';
            } else {
                $cookie->weather_module_zipcode = $zip;
            }
        }
        return Tools::redirect(isset($cookie->weather_module_return_url) ? $cookie->weather_module_return_url : '/');
    }
}