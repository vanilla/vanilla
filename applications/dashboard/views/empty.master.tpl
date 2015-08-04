<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{$CurrentLocale.Lang}">
<head>
    {asset name='Head'}
</head>
<body id="{$BodyID}" class="{$BodyClass}">
<div id="Content">{asset name='Content'}</div>
{asset name='Foot'}
{event name='AfterBody'}
</body>
</html>
