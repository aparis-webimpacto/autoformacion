<?php

if (!defined('_PS_VERSION_'))
    exit;

require 'lib/OpenWeatherMap.php';
require 'lib/OpenWeatherMapWeather.php';

class Weather extends Module
{
    const CONFIG_KEY = 'WEATHER_CONFIG';

    protected $LOCATIONS = array(
        'top' => 'Top',
        'leftColumn' => 'Left Column',
        'rightColumn' => 'Right Column',
        'home' => 'Home',
        'footer' => 'Footer',
    );

    function __construct()
    {
        $this->name = 'weather';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Alba';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Weather');
        $this->description = $this->l('Da la temperatura.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        if (!Configuration::get(self::CONFIG_KEY)) {
            $this->warning = $this->l('You need to provide a US zip code to use the Weather module!');
        }
    }

    /**
     * Get a config value
     * @param string $key
     * @return mixed|null
     */
    
    public static function getConfig($key = '')
    {
        $config = unserialize(Configuration::get(self::CONFIG_KEY));

        if ($key) {
            return isset($config[$key]) ? $config[$key] : null;
        }
        return $config;
    }

    /**
     * Returns the cached Weather Object
     *
     * @return null|OpenWeatherMapWeather
     */
    public static function getCachedResult($zip)
    {
        $row = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'weather_cache WHERE date_created >= DATE_ADD(CURRENT_TIMESTAMP, INTERVAL -15 MINUTE) AND zip = ? ORDER BY date_created DESC', $zip);
        //Remove stale;
        Db::getInstance()->delete('weather_cache', 'date_created < DATE_ADD(CURRENT_TIMESTAMP, INTERVAL -15 MINUTE)');
        if ($row && $row['weather']) {
            return unserialize($row['weather']);
        }
    }

    /**
     * Caches a result in the database
     * @param string $zip
     * @param OpenWeatherMapWeather $weather
     */
    public static function cacheResult($zip, $weather)
    {
        Db::getInstance()->insert('weather_cache', array(
            'zip' => pSQL($zip),
            'weather' => psql(serialize($weather)),
        ));
    }

    /**
     * Get a config value
     * @param string $key
     * @return mixed|null
     */
    public static function setConfig($key, $val)
    {
        $config = unserialize(Configuration::get(self::CONFIG_KEY));

        $config[$key] = $val;
        return Configuration::updateValue(self::CONFIG_KEY, serialize($config));
    }

    protected function installDb()
    {
        return (Db::getInstance()->execute('
		CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'weather_cache` (
			`id_weather_cache` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`zip` VARCHAR(10) NOT NULL,
			`weather` TEXT NOT NULL,
			`date_created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
		) ENGINE = ' . _MYSQL_ENGINE_ . ' CHARACTER SET utf8 COLLATE utf8_general_ci;'));
    }

    /**
     * @return string|null
     */
    protected function _getZipcode()
    {
        global $cookie;
        if (isset($cookie->weather_module_zipcode) && $cookie->weather_module_zipcode) {
            return $cookie->weather_module_zipcode;
        }
    }

    /**
     *
     * @param array $config
     * @return null|OpenWeatherMapWeather
     */
    protected function _getWeather($config)
    {
        $zip = $this->_getZipcode();

        if (!$zip) {
            return null;
        }

        $weather = null;
        if (isset($config['cache']) && $config['cache']) {
            $weather = self::getCachedResult($zip);
        }
        if (!$weather) {
            $owm = new OpenWeatherMap($config['key'], $config['units']);
            $weather = $owm->getWeather($zip);
            if ($weather && isset($config['cache']) && $config['cache']) {
                self::cacheResult($zip, $weather);
            }
        }
        return $weather;
    }

    protected function _render($location)
    {
        $config = self::getConfig();

        if (!isset($config['key'])) {
            return null;
        }

        if (isset($config['location']) && $location === $config['location']) {
            //Set previous URL for redirect after changing the cookie zip.
            global $cookie;
            $cookie->weather_module_return_url = $_SERVER['REQUEST_URI'];
            if ($weather = $this->_getWeather($config)) {
                $this->context->smarty->assign(
                    array(
                        'weather' => $weather,
                        'zip_error' => isset($cookie->weather_module_zipcode_error) ? $cookie->weather_module_zipcode_error : null,
                    )
                );
                return $this->display(__FILE__, 'views/template/front/weather_tall.tpl');
            } else {
                $this->context->smarty->assign(
                    array(
                        'zip_error' => isset($cookie->weather_module_zipcode_error) ? $cookie->weather_module_zipcode_error : null,
                    )
                );
                return $this->display(__FILE__, 'views/template/front/weather_form.tpl');
            }
        }
    }

    public function hookDisplayTop()
    {
        return $this->_render('top');
    }

    public function hookDisplayLeftColumn()
    {
        return $this->_render('leftColumn');
    }

    public function hookDisplayHome()
    {
        return $this->_render('home');
    }

    public function hookDisplayRightColumn()
    {
        return $this->_render('rightColumn');
    }

    public function hookDisplayFooter()
    {
        return $this->_render('footer');
    }

    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        return $this->installDb();
    }

    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }
        return (Configuration::deleteByName(self::CONFIG_KEY)
            && Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'weather_cache`'));
    }

    protected function clearCache()
    {
        Db::getInstance()->execute('TRUNCATE `' . _DB_PREFIX_ . 'weather_cache`');
    }

    public function getContent()
    {
        $output = array();

        $config = self::getConfig();

        if (Tools::isSubmit('submit_' . $this->name)) {

            $validateWeather = true;

            $key = strval(Tools::getValue('weather_key'));
            if (empty($key) || !Validate::isString($key)) {
                $output[] = $this->displayError($this->l('Invalid API key'));
                $validateWeather = false;
            } else if (!isset($config['key']) || $key !== $config['key']) {
                self::setConfig('key', $key);
                $output[] = $this->displayConfirmation($this->l('Weather API key updated'));
            }

            if ($validateWeather) {
                // 20500 = The White House
                $weatherCheck = OpenWeatherMap::validate($key, '20500');
                if ($weatherCheck !== true) {
                    $msg = $this->l('Invalid API key or zip code. Unable to retrieve forecast.');
                    $output[] = $this->displayError($weatherCheck === false ? $msg : $weatherCheck);
                }
            }

            $locations = $this->LOCATIONS;
            $location = strval(Tools::getValue('weather_location'));
            if (empty($location) || !isset($locations[$location])) {
                $output[] = $this->displayError($this->l('Invalid display location'));
            } else if (!isset($config['location']) || $location !== $config['location']) {
                if (isset($config['location'])) {
                    $this->unregisterHook($config['location']);
                }
                self::setConfig('location', $location);
                $this->registerHook($location);
                $output[] = $this->displayConfirmation($this->l('Weather display location updated'));
            }

            $unit = strval(Tools::getValue('weather_units'));
            if (empty($unit) || !Validate::isString($unit)) {
                $output[] = $this->displayError($this->l('Invalid temperature unit'));
            } else if (!isset($config['units']) || $unit !== $config['units']) {
                self::setConfig('units', $unit);
                $output[] = $this->displayConfirmation($this->l('Temperature unit updated'));
            }

            $cache = strval(Tools::getValue('weather_cache'));
            $cache = $cache === '1';

            if (!isset($config['cache']) || $cache !== $config['cache']) {
                self::setConfig('cache', $cache);
                $output[] = $this->displayConfirmation($this->l($cache ? 'Weather cache enabled' : 'Weather cache disabled'));
            }
            //In case units or other things change, reset the cache after changes are made.
            $this->clearCache();
        }
        return implode("\n", $output) . $this->displayForm();
    }

    public function displayForm()
    {
        // Get default language
        $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');
        $config = self::getConfig();

        $helper = new HelperForm();
        $helper->id = (int)Tools::getValue('id_weather_mod');
        $helper->identifier = $this->identifier;

        // Init Fields form array
        $fields = [];

        $fields[] = array(
            'type' => 'text',
            'label' => $this->l('OpenWeatherMap API Key'),
            'name' => 'weather_key',
            'desc' => $this->l('Visit http://openweathermap.org and signup and receive your free key.'),
            'required' => true,
        );
        // Load current value
        $helper->fields_value['weather_key'] = isset($config['key']) ? $config['key'] : '';

        $fields[] = array(
            'type' => 'switch',
            'label' => 'Weather Caching',
            'name' => 'weather_cache',
            'desc' => $this->l('Refresh weather data only every 15 minutes?'),
            'values' => array(
                array(
                    'id' => 'weather_cache_on',
                    'value' => '1',
                    'label' => $this->l('Enabled')
                ),
                array(
                    'id' => 'weather_cache_off',
                    'value' => '0',
                    'label' => $this->l('Disabled')
                )
            )
        );
        // Load current value
        $helper->fields_value['weather_cache'] = isset($config['cache']) ? ($config['cache'] ? '1' : '0') : '1';

        $fields[] = array(
            'type' => 'select',
            'label' => 'Temperature Units',
            'name' => 'weather_units',
            'options' => array(
                'query' => array(
                    array(
                        'id' => 'Fahrenheit',
                        'name' => $this->l('Fahrenheit'),
                    ),
                    array(
                        'id' => 'Celsius',
                        'name' => $this->l('Celsius'),
                    ),
                ),
                'id' => 'id',
                'name' => 'name',
            ),
        );
        // Load current value
        $helper->fields_value['weather_units'] = isset($config['units']) ? $config['units'] : 'Fahrenheit';

        $locations = array();
        foreach ($this->LOCATIONS as $lId => $lHuman) {
            $locations[] = array('id' => $lId, 'name' => $this->l($lHuman));
        }
        $fields[] = array(
            'type' => 'select',
            'label' => 'Display Location',
            'name' => 'weather_location',
            'options' => array(
                'query' => $locations,
                'id' => 'id',
                'name' => 'name',
            ),
        );
        // Load current value
        $helper->fields_value['weather_location'] = isset($config['location']) ? $config['location'] : 'Fahrenheit';

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

        // Language
        $helper->default_form_language = $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = false;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit_' . $this->name;
        $helper->toolbar_btn = array(
            'save' =>
                array(
                    'desc' => $this->l('Save'),
                    'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name .
                        '&token=' . Tools::getAdminTokenLite('AdminModules'),
                ),
            'back' => array(
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );

        return $helper->generateForm(array(
            array(
                'form' => array(
                    'legend' => array(
                        'title' => $this->l('Weather Settings'),
                    ),
                    'input' => $fields,
                    'submit' => array(
                        'title' => $this->l('Save'),
                        'class' => 'btn btn-default pull-right',
                    )
                ),
            ),
        ));
    }
}