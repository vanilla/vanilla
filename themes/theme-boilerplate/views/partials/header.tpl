{assign
    "linkFormat"
    "<div class='Navigation-linkContainer'>
        <a href='%url' class='Navigation-link %class'>
            %text
        </a>
    </div>"
}

<header id="MainHeader" class="Header">
    <div class="Container">
        <div class="row">
            <div class="Hamburger">
                {include file="partials/hamburger.html"}
            </div>
            <a href="{home_link format="%url"}" class="Header-logo">
                {logo}
            </a>
            <a href="{home_link format="%url"}" class="Header-logo mobile">
                {mobile_logo}
            </a>
            <nav class="Header-desktopNav">
                {categories_link format=$linkFormat}
                {discussions_link format=$linkFormat}
                {custom_menu format=$linkFormat}
            </nav>
            <div class="Header-flexSpacer"></div>
            <div class="Header-right">
                <span data-react="subcommunity-chooser"></span>
                <div class="MeBox-header">
                    {module name="MeModule" CssClass="FlyoutRight"}
                </div>
                {if $User.SignedIn}
                    <button class="mobileMeBox-button">
                        <span class="Photo PhotoWrap">
                            <img src="{$User.Photo|escape:'html'}" class="ProfilePhotoSmall" alt="{t c='Avatar'}">
                        </span>
                    </button>
                {/if}
            </div>
        </div>
    </div>
    <nav class="Navigation needsInitialization js-nav">
        <div class="Container">
            {if $User.SignedIn}
                <div class="Navigation-row NewDiscussion">
                    <div class="NewDiscussion mobile">
                        {module name="NewDiscussionModule"}
                    </div>
                </div>
            {else}
                <div class="Navigation-row">
                    <div class="SignIn mobile">
                        {module name="MeModule"}
                    </div>
                </div>
            {/if}
            {categories_link format=$linkFormat}
            {discussions_link format=$linkFormat}
            {activity_link format=$linkFormat}
            {custom_menu format=$linkFormat}
        </div>
    </nav>
    <nav class="mobileMebox js-mobileMebox needsInitialization">
        <div class="Container">
            {module name="MeModule"}
            <button class="mobileMebox-buttonClose Close">
                <span>×</span>
            </button>
        </div>
    </nav>
</header>
