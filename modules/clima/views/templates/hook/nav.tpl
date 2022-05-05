{*
* 2021 ARTULANCE.COM
*
* NOTICE OF LICENSE
*
* This file is licenced under the Software License Agreement.
* With the purchase or the installation of the software in your application
* you accept the licence agreement
*
* @author    ARTULANCE.COM <artudevweb@gmail.com>
* @copyright 2021 ARTULANCE.COM
* @license   Free license 
*
*}
<style>
{literal}
#clima_bloque{
    border:2px dotted black;
}
{/literal}
</style>


{block name='clima'}
<section id="clima_bloque" class=" col-xs-12 col-sm-12">
	<h4>El tiempo en nuestra zona</h4>
    <ul class="toggle-footer">
		<li class="item"><i class="material-icons">location_city</i> Ciudad : {$city}</li>
		<li class="item"><i class="material-icons">beach_access</i> Viento: {$wind} mps</li>
		<li class="item"><i class="material-icons">settings_system_daydream</i> Tiempo: {$weather}</li>
		<li class="item"><i class="material-icons">ac_unit</i> Temperatura: {$temp} Cº</li>
		<li class="item"><i class="material-icons">devices</i> IP: {$IP}</li>
    </ul>
</section>
{/block}
