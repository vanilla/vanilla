<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width">
</head>
<body style="font-size: 16px;line-height: 22px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: 300;padding:0;margin:0;text-align: left;width:100% !important;min-width: 100%;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%">
<table class="body" style="background-color: {$email.backgroundColor};border-spacing: 0;border-collapse: collapse;padding:0;vertical-align: top;text-align: left;font-size: 14px;line-height: 1.3;font-weight: normal;color: #222222;font-family: Helvetica, Arial, sans-serif;margin: 0;height: 100%;width: 100%"><tr style="padding: 0;vertical-align: top;text-align: left">
<td class="center" align="center" valign="top" style="font-size: 16px;line-height: 22px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: 300;padding: 0;margin: 0;text-align: center;vertical-align: top;word-break: break-word;-webkit-hyphens: none;-moz-hyphens: none;hyphens: none;border-collapse: collapse !important">
      <center style="width: 100%;min-width: 580px">
	<table class="row" style="border-spacing: 0;border-collapse: collapse;padding: 0px;vertical-align: top;text-align: left;width: 100%;position: relative"><tr style="padding: 0;vertical-align: top;text-align: left">
<td class="center" align="center" style="font-size: 16px;line-height: 22px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: 300;padding: 0;margin: 0;text-align: center;vertical-align: top;word-break: break-word;-webkit-hyphens: none;-moz-hyphens: none;hyphens: none;border-collapse: collapse !important">
	      <center style="width: 100%;min-width: 580px">
		<table class="container" style="border-spacing: 0;border-collapse: collapse;padding: 0;vertical-align: top;text-align: inherit;width: 470px !important;margin: 20px auto !important;background-color: #fff"><tr style="padding: 0;vertical-align: top;text-align: left">
<td class="wrapper last" style="font-size: 16px;line-height: 22px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: 300;padding: 20px 0px 10px !important;margin: 0;text-align: left;vertical-align: top;word-break: break-word;-webkit-hyphens: none;-moz-hyphens: none;hyphens: none;border-collapse: collapse !important;position: relative;padding-right: 0px">
		      <table class="eight columns" style="border-spacing: 0;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;width: 380px;margin: 0 auto">
			{if $email.image}
			<tr style="padding: 0;vertical-align: top;text-align: left">
<td style="font-size: 16px;line-height: 22px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: 300;padding: 0px 0px 10px;margin: 0;text-align: left;vertical-align: top;word-break: break-word;-webkit-hyphens: none;-moz-hyphens: none;hyphens: none;border-collapse: collapse !important">
			    {if $email.image.link}
			    <a href="{$email.image.link}" style="color: {$email.link.color};text-decoration: none">
			      {/if}
			      <img src="{$email.image.source}" style="outline:none;text-decoration:none;-ms-interpolation-mode: bicubic;width: auto;max-width: 75%;float: none;clear: both;display: block;margin: 0 auto;border: none">
			      {if $email.image.link}
			    </a>
			    {/if}
			  </td>
			</tr>
<tr style="padding: 0;vertical-align: top;text-align: left">
<td style="font-size: 16px;line-height: 22px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: 300;padding: 0px 0px 10px;margin: 0;text-align: left;vertical-align: top;word-break: break-word;-webkit-hyphens: none;-moz-hyphens: none;hyphens: none;border-collapse: collapse !important">
			    <hr style="color: #d9d9d9;background-color: #d9d9d9;height: 1px;border: none">
</td>
			</tr>
			{/if}
			<tr style="padding: 0;vertical-align: top;text-align: left">
<td style="font-size: 16px;line-height: 22px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: 300;padding: 0px 0px 10px;margin: 0;text-align: left;vertical-align: top;word-break: break-word;-webkit-hyphens: none;-moz-hyphens: none;hyphens: none;border-collapse: collapse !important">
			    {if $email.title}<h1 class="center" style="font-size: 40px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: normal;padding:0;margin: 0;text-align: center;line-height: 1.3;word-break: normal">{$email.title}</h1>{/if}
			    {if $email.lead}<p class="lead center" style="font-size: 18px;line-height:21px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: 300;padding:0;margin: 0 0 0 10px;text-align: center;margin-bottom: 10px">{$email.lead}</p>{/if}
			  </td>
			</tr>
</table>
		      {if $email.button}
		      <table class="four columns" style="border-spacing: 0;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;width: 180px;margin: 0 auto"><tr style="padding: 0;vertical-align: top;text-align: left">
<td class="center button-wrapper" style="font-size: 16px;line-height: 22px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: 300;padding: 0px 0px 20px !important;margin: 0;text-align: center;vertical-align: top;word-break: break-word;-webkit-hyphens: none;-moz-hyphens: none;hyphens: none;border-collapse: collapse !important">
			    <center style="width: 100%;min-width: 180px">
			      <table class="button button-custom" style="border-spacing: 0;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;width: 100%;overflow: hidden"><tr style="padding: 0;vertical-align: top;text-align: left">
<td style="font-size: 16px;line-height: initial !important;color: #ffffff;font-family: Helvetica, Arial, sans-serif;font-weight: 300;padding: 0;margin: 0;text-align: center;vertical-align: top;word-break: break-word;-webkit-hyphens: none;-moz-hyphens: none;hyphens: none;border-collapse: collapse !important;display: block;width: auto !important;background: #2ba6cb;border: 0;background-color: {$email.button.backgroundColor}">
				    <a href="{$email.button.url}" style="color: {$email.button.color};text-decoration: none;font-weight: bold;font-family: Helvetica, Arial, sans-serif;font-size: 16px;display: block;height: 100%;width: 100%;padding: 15px 0">{$email.button.text}</a>
				  </td>
				</tr></table>
</center>
			  </td>
			</tr></table>
		      {/if}
		      <table class="eight columns" style="border-spacing: 0;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;width: 380px;margin: 0 auto"><tr style="padding: 0;vertical-align: top;text-align: left">
<td style="font-size: 16px;line-height: 22px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: 300;padding: 0px 0px 10px;margin: 0;text-align: left;vertical-align: top;word-break: break-word;-webkit-hyphens: none;-moz-hyphens: none;hyphens: none;border-collapse: collapse !important">
			    <p class="message" style="font-size: 16px;line-height: 22px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: 300;padding:0;margin: 0 0 0 10px;text-align: left;margin-bottom: 10px">{$email.message}
			      {if $email.link}<a href="{$email.link.url}" style="color: {$email.link.color};text-decoration: none">{$email.link.text}</a>{/if}
			    </p>
			  </td>
			</tr></table>
		      {if $email.footer}
		      <table class="footer eight columns" style="border-spacing: 0;border-collapse: collapse;padding: 0;vertical-align: top;text-align: left;width: 380px;margin: 0 auto"><tr style="padding: 0;vertical-align: top;text-align: left">
<td class="center" style="font-size: 16px;line-height: 22px;color: #222222;font-family: Helvetica, Arial, sans-serif;font-weight: 300;padding: 0px 0px 10px;margin: 0;text-align: center;vertical-align: top;word-break: break-word;-webkit-hyphens: none;-moz-hyphens: none;hyphens: none;border-collapse: collapse !important;padding-bottom: 0">
			    <center style="width: 100%;min-width: 380px">
			      <p class="footer center" style="font-size: 12px;line-height: 22px;color: #333;font-family: Helvetica, Arial, sans-serif;font-weight: 400;padding:0;margin: 0 0 0 10px;text-align: center;margin-bottom: 10px">{$email.footer}</p>
			    </center>
			  </td>
			</tr></table>
		      {/if}
		    </td>
		  </tr></table>
</center>
	      <!-- container end below -->
	    </td>
	  </tr></table>
</center>
    </td>
  </tr></table>
</body>
</html>
