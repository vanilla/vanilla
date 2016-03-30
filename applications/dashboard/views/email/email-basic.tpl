<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<!--[if !mso]><!--><meta http-equiv="X-UA-Compatible" content="IE=edge">
<!--<![endif]--><meta name="viewport" content="width=device-width, initial-scale=1.0">
<!--[if (gte mso 9)|(IE)]>
    {literal}
    <style type="text/css">
        table {
            border-collapse: collapse;
        }
    </style>
    {/literal}
    <![endif]-->
</head>
<body bgcolor="{$email.backgroundColor}" style='font-size: 16px;-webkit-font-smoothing: antialiased;-webkit-text-size-adjust: none;width: 100% !important;height: 100%;margin: 0 !important;padding: 0;font-family: "Helvetica Neue", "Helvetica", Helvetica, Arial, sans-serif;font-weight: 300;text-align: left;line-height: 1.4;color: {$email.textColor}'>
<center class="wrapper" style="margin: 0;padding: 10px;box-sizing: border-box;font-size: 100%;width: 100%;table-layout: fixed;-webkit-text-size-adjust: 100%;-ms-text-size-adjust: 100%;background-color: {$email.backgroundColor}">
    <div class="webkit" style="margin: 0 auto;padding: 0;box-sizing: border-box;font-size: 100%;max-width: 600px">
        <!--[if (gte mso 9)|(IE)]>
        <table width="600" align="center" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td>
        <![endif]-->
        <table class="outer main" align="center" style='font-size: 100%;margin: 0;padding: 0;box-sizing: border-box;color: {$email.textColor};border-spacing: 0;font-family: "Helvetica Neue", "Helvetica", Helvetica, Arial, sans-serif;font-weight: 300;text-align: left;line-height: 1.4;Margin: 0 auto;width: 100%;max-width: 600px;background-color: {$email.containerBackgroundColor}'><tr style="margin: 0;padding: 0;box-sizing: border-box;font-size: 100%">
<td style='font-size: 100%;margin: 0;padding: 0;box-sizing: border-box;font-family: "Helvetica Neue", "Helvetica", Helvetica, Arial, sans-serif;font-weight: 300;text-align: left;line-height: 1.4;color: {$email.textColor};background-color: {$email.containerBackgroundColor}'>
                    <table width="100%" style='font-size: 100%;margin: 0;padding: 0;box-sizing: border-box;color: {$email.textColor};border-spacing: 0;font-family: "Helvetica Neue", "Helvetica", Helvetica, Arial, sans-serif;font-weight: 300;text-align: left;line-height: 1.4'><tr style="margin: 0;padding: 0;box-sizing: border-box;font-size: 100%">
<td class="inner contents" style='font-size: 100%;margin: 0;padding: 20px 30px;box-sizing: border-box;font-family: "Helvetica Neue", "Helvetica", Helvetica, Arial, sans-serif;font-weight: 300;text-align: left;line-height: 1.4;color: {$email.textColor};background-color: {$email.containerBackgroundColor}'>
                                {if $email.image}
                                <div class="image-wrap center" style="margin: 0;padding: 0;box-sizing: border-box;font-size: 100%;text-align: center;margin-bottom: 10px">
                                    {if $email.image.link}
                                    <a href="{$email.image.link}" style="margin: 0;padding: 0;box-sizing: border-box;font-size: 100%">
                                        {/if}
                                        {if $email.image.source != ''}
                                        <img class="center" src="{$email.image.source}" alt="{$email.image.alt}" style="margin: 0;padding: 0;box-sizing: border-box;font-size: 100%;max-width: 75%;border: 0;text-align: center">
                                        {elseif $email.image.alt != ''}
                                        <h1 class="center h1" style='Margin-bottom: 18px;margin: 0;padding: 0;box-sizing: border-box;font-size: 36px;color: {$email.textColor};font-family: "Helvetica Neue", "Helvetica", Helvetica, Arial, sans-serif;font-weight: 200;text-align: center;line-height: 1.2;margin-top: 20px;margin-bottom: 5px;word-break: normal'>{$email.image.alt}</h1>
                                        {/if}
                                        {if $email.image.link}
                                    </a>
                                    {/if}
                                </div>
                                <hr style="margin: 0;padding: 0;box-sizing: border-box;font-size: 100%;border-style: solid;background-color: {$email.textColor};border-color: {$email.textColor}">
                                {/if}
                                {if $email.title}<h1 class="center" style='Margin-bottom: 18px;margin: 0;padding: 0;box-sizing: border-box;font-size: 36px;color: {$email.textColor};font-family: "Helvetica Neue", "Helvetica", Helvetica, Arial, sans-serif;font-weight: 200;text-align: center;line-height: 1.2;margin-top: 20px;margin-bottom: 5px;word-break: normal'>{$email.title}</h1>{/if}
                                {if $email.lead}<p class="lead center" style='margin: 0;Margin-bottom: 10px;font-size: 20px;padding: 0;box-sizing: border-box;color: {$email.textColor};font-family: "Helvetica Neue", "Helvetica", Helvetica, Arial, sans-serif;font-weight: 300;text-align: center;line-height: 1.2;margin-bottom: 15px'>{$email.lead}</p>{/if}
                                <p class="message" style='margin: 0;Margin-bottom: 10px;font-size: 100%;padding: 0;box-sizing: border-box;color: {$email.textColor};font-family: "Helvetica Neue", "Helvetica", Helvetica, Arial, sans-serif;font-weight: 300;text-align: left;line-height: 1.4;margin-top: 10px;margin-bottom: 15px'>{$email.message}</p>
                                {if $email.button}
                                <div class="button-wrap center" style="margin: 0;padding: 0;box-sizing: border-box;font-size: 100%;text-align: center">
                                    <a href="{$email.button.url}" class="button" style="margin: 0;padding: 0;box-sizing: border-box;font-size: 100%;color: {$email.button.textColor};background-color: {$email.button.backgroundColor};border-color: {$email.button.backgroundColor};text-decoration: none;text-align: center;font-weight: 700;cursor: pointer;display: inline-block;border-width: 12px 18px;border-style: solid">{$email.button.text}</a>
                                </div>
                                {/if}
                            </td>
                        </tr></table>
</td>
            </tr></table>
<!--[if (gte mso 9)|(IE)]>
                </td>
            </tr>
        </table>
        <![endif]-->
        {if $email.footer}
        <!--[if (gte mso 9)|(IE)]>
        <table width="600" align="center" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td>
        <![endif]-->
        <table class="outer footer" align="center" style='font-size: 14px;margin: 0;padding: 0;box-sizing: border-box;color: {$email.footer.textColor};border-spacing: 0;font-family: "Helvetica Neue", "Helvetica", Helvetica, Arial, sans-serif;font-weight: 300;text-align: left;line-height: 1.4;Margin: 0 auto;width: 100%;max-width: 600px;background-color: {$email.footer.backgroundColor}'><tr style="margin: 0;padding: 0;box-sizing: border-box;font-size: 100%">
<td style='font-size: 100%;margin: 0;padding: 0;box-sizing: border-box;font-family: "Helvetica Neue", "Helvetica", Helvetica, Arial, sans-serif;font-weight: 300;text-align: left;line-height: 1.4;color: {$email.textColor};background-color: {$email.footer.backgroundColor}'>
                    <table width="100%" style='font-size: 100%;margin: 0;padding: 0;box-sizing: border-box;color: {$email.textColor};border-spacing: 0;font-family: "Helvetica Neue", "Helvetica", Helvetica, Arial, sans-serif;font-weight: 300;text-align: left;line-height: 1.4'><tr style="margin: 0;padding: 0;box-sizing: border-box;font-size: 100%">
<td class="inner contents" style='font-size: 100%;margin: 0;padding: 20px 30px;box-sizing: border-box;font-family: "Helvetica Neue", "Helvetica", Helvetica, Arial, sans-serif;font-weight: 300;text-align: left;line-height: 1.4;color: {$email.textColor};background-color: {$email.footer.backgroundColor}'>
                                <div class="content" style="margin: 0;padding: 0;box-sizing: border-box;font-size: 100%">
                                    <table style='font-size: 100%;margin: 0;padding: 0;box-sizing: border-box;color: {$email.textColor};border-spacing: 0;font-family: "Helvetica Neue", "Helvetica", Helvetica, Arial, sans-serif;font-weight: 300;text-align: left;line-height: 1.4'><tr style="margin: 0;padding: 0;box-sizing: border-box;font-size: 100%">
<td style='font-size: 100%;margin: 0;padding: 0;box-sizing: border-box;font-family: "Helvetica Neue", "Helvetica", Helvetica, Arial, sans-serif;font-weight: 300;text-align: left;line-height: 1.4;color: {$email.textColor};background-color: {$email.footer.backgroundColor}'>
                                                <div class="footer center" style="margin: 0;padding: 0;box-sizing: border-box;font-size: 14px;color: {$email.footer.textColor};text-align: center;background-color: {$email.footer.backgroundColor}">{$email.footer.text}</div>
                                            </td>
                                        </tr></table>
</div>
                            </td>
                        </tr></table>
</td>
            </tr></table>
<!--[if (gte mso 9)|(IE)]>
                </td>
            </tr>
        </table>
        <![endif]-->
        {/if}
    </div>
</center>
</body>
</html>
