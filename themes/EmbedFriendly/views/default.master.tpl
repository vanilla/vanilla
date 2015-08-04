<!DOCTYPE html>
<html>
<head>
    {asset name='Head'}
</head>

<body id="{$BodyID}" class="{$BodyClass}">

<div id="Frame">
    <div id="Head">
        <div class="Row">
            <strong class="SiteTitle"><a href="{link path="/"}">{logo}</a></strong>
            <ul class="SiteMenu">
                {dashboard_link}
                {discussions_link}
                {activity_link}
                {inbox_link}
                {custom_menu}
                {profile_link}
                {signinout_link}
            </ul>
        </div>
    </div>
    <div id="Body">
        <div class="Row">
            <div class="BreadcrumbsWrapper P">{breadcrumbs}</div>
            <div class="Column PanelColumn" id="Panel">
                {module name="MeModule" CssClass="FlyoutRight"}
                {asset name="Panel"}
                <div class="SiteSearch">{searchbox}</div>
            </div>
            <div class="Column ContentColumn" id="Content">{asset name="Content"}</div>
        </div>
    </div>
    <div id="Foot">
        <div class="Row">
            <a href="{vanillaurl}" class="PoweredByVanilla">Powered by Vanilla</a>
            {asset name="Foot"}
        </div>
    </div>
</div>
{event name="AfterBody"}
</body>
</html>
