<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
  {asset name='Head'}
</head>
<body id="{$BodyID}" class="{$BodyClass}">
  <div id="Frame">
	 <div class="Banner">
		<ul>
		  {discussions_link}
		  <!-- {activity_link} -->
		  {profile_link}
		  {inbox_link}
		  {custom_menu}
		  {event name="BeforeSignInLink"}
		  {if !$User.SignedIn}
			 <li class="SignInItem">{link path="signin" class="SignIn"}</li>
		  {/if}
		</ul>
	 </div>
	 <div id="Body">
		<div id="Panel">
		  {asset name="Panel"}
		</div>
		<div id="Content">
		  {asset name="Content"}
		</div>
	 </div>
	 <div id="Foot">
		<div class="FootMenu">
		  <!--
		  <span>Mobile</span>
		  <span><a href="#">Desktop</a></span>
		  -->
		  {dashboard_link wrap="span"}
		  {signinout_link wrap="span"}
		</div>
		<div>
		  <a href="{vanillaurl}"><span>Powered by Vanilla</span></a>
		</div>
		{asset name="Foot"}
	 </div>
  </div>
</body>
</html>