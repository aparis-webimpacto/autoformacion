{*
* Tall template. @todo, create wide template, and better templates
*}

<div class="col-xs-12 col-sm-4">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">Get the Weather!</h3>
        </div>
        <div class="panel-body">
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
    </div>
</div>