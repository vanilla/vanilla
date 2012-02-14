<!DOCTYPE html>
<html>
<head>
	{asset name='Head'}
</head>
<body id="{$BodyID}" class="PopupPage {$BodyClass}">
<div id="Content">{asset name='Content'}</div>
{asset name='Foot'}
{event name='AfterBody'}
</body>
</html>