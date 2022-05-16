{block name='header_banner'}
    <div class="header-banner">
        {hook h='displayBanner'}
    </div>
{/block}

{block name='header_nav'}
    <div class="header-nav u-bor-bot">
        <div class="header__container container">
            <div class="u-a-i-c d--flex-between visible--desktop">
                <!--<div class="small">
                    {hook h='displayNav1'}
                </div>-->
                <div class="header-nav__right">
                    {hook h='displayNav2'}
                </div>
            </div>
        </div>
    </div>
{/block}

{block name='header_top'}
    <div class="header-top d--flex-between u-a-i-c">
        <button class="visible--mobile btn" id="menu-icon" data-toggle="modal" data-target="#mobile_top_menu_wrapper">
            <i class="material-icons d-inline">&#xE5D2;</i>
        </button>
        <a href="{$urls.base_url}" class="header__logo header-top__col">
            <img class="logo img-fluid" src="{$shop.logo}" alt="{$shop.name}">
        </a>
        <div class="header__search">
            {hook h='displaySearch'}
        </div>
        <div class="header__right header-top__col">
            {hook h='displayTop'}
        </div>
    </div>
    <div class="container">
        {hook h='displayNavFullWidth'}
    </div>
{/block}
