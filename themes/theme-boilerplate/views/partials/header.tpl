{assign
    "linkFormat"
    "<div class='Navigation-linkContainer'>
        <a href='%url' class='Navigation-link %class'>
            %text
        </a>
    </div>"
}

<header class="Header">
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
            <div class="Header-right">
                <div class="MeBox--header">
                    {module name="MeModule" CssClass="FlyoutRight"}
                </div>
            </div>
        </div>
    </div>
    <nav id="navdrawer" class="Navigation">
        <div class="Container">
            <div class="Navigation-row">
                <div class="MeBox MeBox mobile">
                    {module name="MeModule"}
                </div>
            </div>
            <div class="Navigation-row NewDiscussion">
                <div class="NewDiscussion mobile">
                    {module name="NewDiscussionModule"}
                </div>
            </div>
            {categories_link format=$linkFormat}
            {discussions_link format=$linkFormat}
            {activity_link format=$linkFormat}
            {custom_menu format=$linkFormat}
        </div>
    </nav>
</header>
