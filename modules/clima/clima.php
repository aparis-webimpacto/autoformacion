<?php 
/**
 * PrestaShop module created by Arturo
 *
 * @author    arturo https://artulance.com
 * @copyright 2020-2021 arturo
 * @license   This program is a free software but you can't resell it
 *
 * CONTACT WITH DEVELOPER
 * artudevweb@gmail.com
 */
class clima extends Module{


    
    public function __construct()
    {
        $this->name          = 'clima';
        $this->tab           = 'Blocks';
        $this->author        = 'artulance.com';
        $this->version       = '1.0.0';
        $this->bootstrap     = true;
       
        parent::__construct();
        $this->displayName = $this->l('Clima en el navbar');
        $this->description = $this->l('Este modulo da los datos de ubicación, clima, ciudad a través de la ip');

    }

    public function install()
    {
        if(!parent::install() || ! $this->registerHook('displayNav') || ! $this->registerHook('displayNavFullWidth') )
        {
       
            return false;
        }else{

            return true;
        }
    }

    public function unistall()
    {
        if(!parent::unistall() || ! $this->unregisterHook('displayNav') || ! $this->unregisterHook('displayNavFullWidth'))
        {
             
            return false;
        }else{
            return true;
        }
    }



    public function postProcess()
    {

      if (Tools::isSubmit('save'))
	    {
	    	$clima = array();
	        $clima['api'] = strval(Tools::getValue('api'));
            $clima_key = Tools::getValue('clima_key');
        //Cogemos el texto de la tabla ps_configuration con su campo correspondiente para poner en el value
            Configuration::updateValue('CLIMA_MODULO_API_KEY', $clima_key);
            // Devuelvo un mensaje de confirmación si se actualiza adecuadamente 
            return $this->displayConfirmation($this->l('Updated Successfully'));
	       
	    } 
    }

/**
	 * Hacemos el metodo de configuración
	 * Para cuando lo instalemos, podamos configurar el módulo
	 * Mostrará un formulario de api key ya que el usuario tendría que tener
	 * su propia key configurada 
	 */
    public function getContent()
	{
	    $output = null;
        /*
        Evaluamos si el nombre del dominio es localhost o su ip
        */
        if (in_array($_SERVER['SERVER_NAME'], array('localhost', '127.0.0.1'))) {
            $this->context->controller->errors[] = $this->l('Estas en localhost, la geolocalización solo funciona desde una web online');
        }


       return $this->postProcess() . $this->displayForm();
	}

    public function displayForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-wrench'
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('key de openweather'),
                        'name' => 'clima_key',
                        'identifier' => 'value',
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                     )
                 ),
        );
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->languages = $this->context->controller->getLanguages();
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = $this->context->controller->default_form_language;
        $helper->allow_employee_form_lang = $this->context->controller->allow_employee_form_lang;
        $helper->title = $this->displayName;

        /*Puedo configurar cual va a ser mi campo de submit */
        $helper->submit_action = 'save';
        $helper->fields_value['clima_key'] = Configuration::get('CLIMA_MODULO_API_KEY');
        

        return $helper->generateForm(array($fields_form));
    }

    /*Puedo configurar que si no se registra en los hooks en el install, pueda meterlo en el hook de displayhome manualmente poniendo esta funcion
    https://devdocs.prestashop.com/1.7/modules/concepts/hooks/list-of-hooks/
     */
    public function hookdisplayNav()
    {
        $api_key = Configuration::get('CLIMA_MODULO_API_KEY');
        $data=$this->geolocation($api_key);
        $this->context->smarty->assign( array(
            'city' => $data[1]->name,
            'wind' => $data[1]->wind->speed,
            'weather' => $data[1]->weather[0]->main,
            'temp' => $data[1]->main->temp,
            'IP' => $data[0]
        ));
        
        return $this->context->smarty->fetch($this->local_path.'views/templates/hook/nav.tpl');
    }/**
     * Método para mostrar en el displaynavfullwidth
     *consigo la api key y luego hago la llamada a geolocalizar 
     * con lo que obtenga podré luego desmigar el array, consiguiendo en primera posición la ip
     * y en segunda, una tanda de objetos la cual será el json pasado a object(stdClass)
	 *
	 * @param  $zip(no es necesario ya)$api_key, $latitude and $long
	 * @return object $response
	 */
    public function hookdisplayNavFullWidth()
    {
        $api_key = Configuration::get('CLIMA_MODULO_API_KEY');
        //$this->context->controller->addCSS($this->_path.'views/css/clima.css', 'all');
        $this->context->controller->registerStylesheet(
            'modules-clima', //This id has to be unique
            'modules/'.$this->name.'/views/css/clima.css',
            array('media' => 'all', 'priority' => 150)
        );
      //  $css=$this->loadAsset();
        $data=$this->geolocation($api_key);
        $this->context->smarty->assign( array(
            'city' =>  $data[1]->name,
            'wind' =>  $data[1]->wind->speed,
            'weather' =>  $data[1]->weather[0]->main,
            'temp' => $data[1]->main->temp,
            'IP' => $data[0]
        ));
        
        return $this->context->smarty->fetch($this->local_path.'views/templates/hook/nav.tpl');
    }
    public function hookDisplayHeader()
    {
        $this->context->controller->addCSS($this->_path.'views/css/clima.css', 'all');
      
    }
    
/**
     * Este método solo es un enlace para devolver luego un array
     *con todo lo necesario, primero consigue el pais y luego 
     * con los datos de latitud,logintud y la apikey llama a la api
     * y con eso retornará los datos para pasarlos al tpl
	 *
	 * @param  $zip(no es necesario ya)$api_key, $latitude and $long
	 * @return object $response
	 */
public function geolocation($api_key)
{
    $datos=$this->getCountry();
    $country=$datos[0];
    $ip=$datos[1];
    $latitude=$datos[2];
    $long=$datos[3];
    $datosdeapi=$this->callApi($country,$api_key,$latitude,$long);
    file_put_contents(__DIR__.'/datos.log', date('d-m-Y H:i:s').' - '.var_export($datosdeapi, TRUE).PHP_EOL ,FILE_APPEND);

    return array($ip, $datosdeapi);
}

/**
     * Este método usa el módulo de geoip
     * Consigue una ip en el try para guardarla en una variable
     * y luego devuelve en un array el codigo del pais que ya no es necesario
     * la latitud y la longitud 
     * hace un recurso de curl con ella que retorna la transferencia
     * como un string el cual acepta json y lo guarda todo en response y luego cierra el curl
	 *
	 * @param  
	 * @return object $array
	 */

public function getCountry()
{
    $codigodepais="";
    $ip="83.38.147.60";
    $latitude="";
    $long="";
    $record = false;
    if (in_array($_SERVER['SERVER_NAME'], array('localhost', '127.0.0.1'))) {
        /* comprobamos que la base de datos de Maxmind existe */
        if (@filemtime(_PS_GEOIP_DIR_ . _PS_GEOIP_CITY_FILE_)) {
            $reader = new GeoIp2\Database\Reader(_PS_GEOIP_DIR_ . _PS_GEOIP_CITY_FILE_);
            try {
                // $record = $reader->city(Tools::getRemoteAddr());
                $record = $reader->city("83.38.147.60");
                $ip=Tools::getRemoteAddr();
               
                file_put_contents(__DIR__.'/log.log', date('d-m-Y H:i:s').' - '.var_export($record, TRUE).PHP_EOL ,FILE_APPEND);

            } catch (\GeoIp2\Exception\AddressNotFoundException $e) {
                $record = null;
            }

            if (isset($record->country->isoCode)) {
                $codigodepais=$record->country->isoCode;
                $latitude=$record->location->latitude;
                $long=$record->location->longitude;
             
                return array($codigodepais,$ip,$latitude,$long);
            } 
        } 
    } return false;
}

	/**
     * Este método hace una llamada a la api del clima
     * Primero guarda la url en una variable y luego 
     * hace un recurso de curl con ella que retorna la transferencia
     * como un string el cual acepta json y lo guarda todo en response y luego cierra el curl
	 *
	 * @param  $zip(no es necesario ya)$api_key, $latitude and $long
	 * @return object $response
	 */
    public function callApi($zip = null, $api_key = null,$latitude,$long)
    {
    
        $url = 'http://api.openweathermap.org/data/2.5/weather?lat=' . $latitude . '&lon=' . $long . '&appid=' . $api_key . '&units=metric';
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER , array('Accept: application/json'));

		$response = curl_exec($curl);

		curl_close($curl);

		return json_decode($response);
    }

      /**
    * Loads asset resources
    */
    public function loadAsset()
    {
       $css = array();
       $css_path=$this->_path.'views/css/';
        // Load CSS
        $css = array(
            $this->css_path.'clima.css',
        );

        $this->context->controller->addCSS($css, 'all');
    }

}
?>