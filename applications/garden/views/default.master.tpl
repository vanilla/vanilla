<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
	{asset name='Head'}
</head>
<body id="{$BodyIdentifier|escape}" class="{$CssClass|escape}">
   <div id="Frame">
      <div id="Head">
	      <div class="Menu">
	         <h1><a class="Title" href="{url dest='/'}"><span>{$Controller->Head->Title()}</span></a></h1>
				{asset name='Menu'}
				{asset name='Search' tag="div"}
			</div>
      </div>
      <div id="Body">
			{asset name='Content' tag='div'}
			{asset name='Panel' tag='div'}
      </div>
		{asset name='Foot' tag='div'}
   </div>
</body>
</html>