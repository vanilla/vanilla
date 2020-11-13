<!DOCTYPE html>
<html lang="{$CurrentLocale.Lang}">
<head>
    <meta charset="utf-8">
    {asset name='Head'}
</head>
<body id="{$BodyID}" class="PopupPage {$BodyClass}">
<div id="Content">{asset name='Content'}</div>
{asset name='Foot'}
{event name='AfterBody'}
</body>
</html>
