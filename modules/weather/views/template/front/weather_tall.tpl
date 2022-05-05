{*
* Tall template. @todo, create wide template, and better templates
*}

<div class="col-xs-12 col-sm-4">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">{l s='Currently in' mod='weather'} {{$weather->locationName}} {l s='I See' mod='weather'} {{$weather->headline}}
                <a class="btn btn-primary btn-xs" role="button" data-toggle="collapse" href="#weather_module_zip_form" aria-expanded="false" aria-controls="collapseExample">
                    Change Location
                </a>
            </h3>
        </div>
        <div class="panel-body">
            <div class="weather-change-zip {if !$zip_error}collapse{/if}" id="weather_module_zip_form">
                {if $zip_error}
                    <div class="alert alert-danger" role="alert">
                        <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
                        <span class="sr-only">Error:</span>
                        {{$zip_error}}
                    </div>
                {/if}
                <form method="post" action="{$link->getModuleLink('weather', 'zip', [], true)|escape:'html'}">
                    <label for="weather_zipcode">Your Zipcode</label>
                    <input id="weather_zipcode" name="zipcode" type="tel" />
                    <input type="submit" value="Save" />
                </form>
            </div>
            <div class="pull-left">
                <img src="{{$weather->iconUrl}}" alt="{{$weather->description}}" />
            </div>
            <div class="pull-left">
                <dl class="dl-horizontal">
                    <dt>{l s='Expect' mod='weather'}</dt>
                    <dd>{{$weather->description}}</dd>

                    <dt>{l s='Current Temperature' mod='weather'}</dt>
                    <dd>{{$weather->temp}}&deg;</dd>

                    <dt>{l s='Temperature Today' mod='weather'}</dt>
                    <dd>{{$weather->tempMin}}&deg; &dash; {{$weather->tempMax}}&deg;</dd>

                    <dt>{l s='Humidity' mod='weather'}</dt>
                    <dd>{{$weather->humidity}}</dd>
                </dl>
            </div>
        </div>

    </div>
</div>