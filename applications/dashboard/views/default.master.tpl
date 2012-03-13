<!DOCTYPE html>
<html>
<head>
  {asset name='Head'}
</head>

<body id="{$BodyID}" class="{$BodyClass}">

<div id="Frame">
 <div id="Head">
   <div class="Banner Menu">
      <h1><a class="Title" href="{link path="/"}"><span>{logo}</span></a></h1>
      <ul id="Menu">
         {dashboard_link}
         {discussions_link}
         {activity_link}
         {inbox_link}
         {custom_menu}
         {profile_link}
         {signinout_link}
      </ul>
      <div id="Search">{searchbox}</div>
    </div>
  </div>
  <div id="Body">
    <!--<div class="P">{breadcrumbs}</div>-->
    <div id="Content">
      {asset name="Content"}
    </div>
    <div id="Panel">{asset name="Panel"}</div>
  </div>
  <div id="Foot">
    <div><a href="{vanillaurl}"><span>Powered by Vanilla</span></a></div>
    {asset name="Foot"}
 </div>
</div>
{event name="AfterBody"}
</body>
</html>