<?php
/**
* 2007-2022 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2022 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Tarea1 extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'tarea1';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Alba';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('tarea1');
        $this->description = $this->l('AQUI ESTA EL MODULO DE IMPORTAR UN CSV');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    
    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        return parent::install();
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if ((bool)Tools::isSubmit('submitModulocsvModule')===true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);


        return $this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitModulocsvModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'MODULOCSV_LIVE_MODE' => Configuration::get('MODULOCSV_LIVE_FILE', 'VACIO'),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {

        //se abre y se lee
        if (($gestor = fopen(dirname(__FILE__).'/products.csv', "r")) !== FALSE) {
            //esto es cada linea del csv se convierte en array
            while (($datos = fgetcsv($gestor, 1000, ",")) !== FALSE) {
               $product=new Product();
               file_put_contents(__DIR__.'/log.log', date('d-m-Y H:i:s').' - '.var_export($datos, TRUE).PHP_EOL, FILE_APPEND);
               $product->name=$datos[0];
               $product->reference=$datos[1];
               $product->ean13=$datos[2];
               $product->wholesale_price=$datos[3];
               $product->price=$datos[4];

                if($datos[5]==4){
                    $product->id_tax_rules_group=3;
                }elseif($datos[5]==10){
                    $product->id_tax_rules_group=2;
                }elseif($datos[5]==21){
                    $product->id_tax_rules_group=1;
                }
                


                 //Aqui dividimos las categorias que se dividen con ; ( devuelve un array de string)
                 $categorias = explode(";", $datos[7]);
                 //Creamos un array vacio de las categorias a añadir
                 $categories_to_add = array();
 
                 file_put_contents(__DIR__.'/categories.log', date('d-m-Y H:i:s').' - '.var_export($categorias, TRUE).PHP_EOL, FILE_APPEND);


                 //Se recorren las categorias
                 foreach ($categorias as $category) {
                     //en el array de categorias a añadir metemos lo que nos devuelva la funcion getidorcreatecategory
                     $categories_to_add[] = $this->getIdOrCreateCategory($category);
                     file_put_contents(__DIR__.'/categoriesadd.log', date('d-m-Y H:i:s').' - '.var_export($categories_to_add, TRUE).PHP_EOL, FILE_APPEND);
                 }
                 //AHora 
                 $product->addToCategories($categories_to_add);
                 $product->id_category_default = $categories_to_add[0];

                 //Esto devuelve strings

                 $product->id_manufacturer=$this->getIdOrCreateManufacturer($datos[9]);
                //Se añade el producto
                $product->add();

                StockAvailable::setQuantity($product->id,0,$datos[6]);

                $product->update();
            }
        }
    }


    protected function getIdOrCreateCategory($category_name)
    {
        $categories = Category::getAllCategoriesName(); //Busca categorias, devuelve array con id y nombre

        foreach ($categories as $category) {
            if ($category['name'] == $category_name) {
                return $category["id_category"];        //Si llega aqui, corta la ejecucion
            }
        }

        $category = new Category();
        $lenguajes = Language::getLanguages(false); //Todos los lenguajes de BD, false aplica a todos los lenguajes
        $name_language = array();
        

        foreach ($lenguajes as $lenguaje) {
            $name_language[$lenguaje["id_lang"]] = $category_name; //Une el lenguaje al nombre de la categoria, es decir los asocia
        }
        $category->name = $name_language;
        $category->active = true;
        //SE AÑADE EL LINK REWRITE
        $link = Tools::link_rewrite($category_name);
        $category->link_rewrite = array((int)Configuration::get('PS_LANG_DEFAULT') =>  $link);
        $category->id_parent = Configuration::get('PS_HOME_CATEGORY');//cON ESTO LE DIGO QUE TIENE QUE SER LA CATEGORIA POR DEFECTO LA CATEGORIA DE HOME, QUE ES DE QUIEN DESCIENDE

        //Se añade la categoria
        $category->add();

        file_put_contents(__DIR__.'/link.log', date('d-m-Y H:i:s').' - '.var_export($category->link_rewrite, TRUE).PHP_EOL, FILE_APPEND);

        return $category->id;
    }


    protected function getIdOrCreateManufacturer($manufacturer_name)
    {
        $manufactures = Manufacturer::getManufacturers(); //Busca categorias, devuelve array
        file_put_contents(__DIR__.'/manufactures.log', date('d-m-Y H:i:s').' - '.var_export($manufacturer_name, TRUE).PHP_EOL, FILE_APPEND);

        foreach ($manufactures as $manufacture) {
            if ($manufacture['name'] == $manufacturer_name) {
                return $manufacture["id_manufacturer"];        //Si llega aqui, corta la ejecucion
            }
        }

        $manufacture=new Manufacturer();
        if($manufacturer_name==null || empty($manufacturer_name)){
            $manufacture->name = "SIN MARCA";
        }else{
        $manufacture->name = $manufacturer_name;
        }
        $manufacture->active = true;
        //SE AÑADE EL LINK REWRITE
        // $link = Tools::link_rewrite($manufacturer_name);
        // $manufacture->link_rewrite = array((int)Configuration::get('PS_LANG_DEFAULT') =>  $link);
        $manufacture->isCatalogName=true;

        //Se añade la categoria
        $manufacture->add();


        return $manufacture->id;
    }


    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

}