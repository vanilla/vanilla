<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <!--[if !mso]><!-->
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <!--<![endif]-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-size: 100%;
        }

        body {
            -webkit-font-smoothing: antialiased;
            -webkit-text-size-adjust: none;
            width: 100% !important;
            height: 100%;
            margin: 0 !important;
            padding: 0;
        }

        /* Typography */

        body, table, h1, h2, h3, h4, h5, h6, p, td {
            font-family: "Helvetica Neue", "Helvetica", Helvetica, Arial, sans-serif;
            font-weight: 300;
            padding: 0;
            margin: 0;
            text-align: left;
            line-height: 1.4;
        }

        h1, h2, h3, h4, h5, h6 {
            padding: 0;
            margin-top: 20px;
            margin-bottom: 5px;
            font-family: "Helvetica Neue", "Helvetica", Helvetica, Arial, sans-serif;
            font-weight: 200;
            line-height: 1.2;
            word-break: normal;

        }

        h1, .h1 {font-size: 36px;}
        h2, .h2 {font-size: 28px;}
        h3, .h3 {font-size: 24px;}
        h4, .h4 {font-size: 20px;}

        body, table, p, td {
            font-size: 16px;
        }

        p {
            margin: 0;
            Margin-bottom: 10px;
        }

        h1, .h1 {
            Margin-bottom: 18px;
        }

        small {
            font-size: 10px;
        }

        ul li {
            margin-left: 5px;
            list-style-position: inside;
        }

        img {
            max-width: 75%;
        }

        table {
            border-spacing: 0;
            font-family: sans-serif;
        }

        td {
            padding: 0;
        }

        img {
            border: 0;
        }

        hr {
            border-style: solid;
        }

        div[style*="margin: 16px 0"] {
            margin: 0 !important;
        }

        /* Grid */

        .wrapper {
            width: 100%;
            padding: 10px;
            table-layout: fixed;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        .webkit {
            max-width: 600px;
            margin: 0 auto;
        }

        .outer {
            Margin: 0 auto;
            width: 100%;
            max-width: 600px;
        }

        .inner {
            padding: 20px 30px;
        }

        .button {
            text-decoration: none;
            text-align: center;
            font-weight: 700;
            cursor: pointer;
            display: inline-block;
            border-width: 12px 18px;
            border-style: solid;
        }

        .lead {
            margin-bottom: 15px;
            font-size: 20px;
            line-height: 1.2;
        }

        .footer {
            font-size: 14px;
        }

        .message {
            margin-top: 10px;
            margin-bottom: 15px;
        }

        .image-wrap {
            margin-bottom: 10px;
        }

        .center {
            text-align: center;
        }

        /* Variable Colors */
        .wrapper {
            background-color: [[$email.backgroundColor]];
        }

        .main,
        .main td {
            background-color: [[$email.containerBackgroundColor]];
        }

        .footer,
        .footer td {
            background-color: [[$email.footer.backgroundColor]];
        }

        body, table, h1, h2, h3, h4, h5, h6, p, td {
            color: [[$email.textColor]];
        }

        .footer {
            color: [[$email.footer.textColor]];
        }

        hr {
            background-color: [[$email.textColor]];
            border-color: [[$email.textColor]];
        }

        .button {
            color: [[$email.button.textColor]];
            background-color: [[$email.button.backgroundColor]];
            border-color: [[$email.button.backgroundColor]];
        }

    </style>
    <!--[if (gte mso 9)|(IE)]>
    [[literal]]
    <style>
        table {
            border-collapse: collapse;
        }
    </style>
    [[/literal]]
    <![endif]-->
</head>
<body bgcolor="[[$email.backgroundColor]]">
<center class="wrapper">
    <div class="webkit">
        <!--[if (gte mso 9)|(IE)]>
        <table width="600" align="center" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td>
        <![endif]-->
        <table class="outer main" align="center">
            <tr>
                <td>
                    <table width="100%">
                        <tr>
                            <td class="inner contents">
                                [[if $email.image]]
                                <div class="image-wrap center">
                                    [[if $email.image.link]]
                                    <a href="[[$email.image.link]]">
                                        [[/if]]
                                        [[if $email.image.source != '']]
                                        <img class="center" src="[[$email.image.source]]" alt="[[$email.image.alt]]">
                                        [[elseif $email.image.alt != '']]
                                        <h1 class="center h1">[[$email.image.alt]]</h1>
                                        [[/if]]
                                        [[if $email.image.link]]
                                    </a>
                                    [[/if]]
                                </div>
                                <hr />
                                [[/if]]
                                [[if $email.title]]<h1 class="center">[[$email.title]]</h1>[[/if]]
                                [[if $email.lead]]<p class="lead center">[[$email.lead]]</p>[[/if]]
                                <p class="message">[[$email.message]]</p>
                                [[if $email.button]]
                                <div class="button-wrap center">
                                    <a href="[[$email.button.url]]" class="button">[[$email.button.text]]</a>
                                </div>
                                [[/if]]
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <!--[if (gte mso 9)|(IE)]>
                </td>
            </tr>
        </table>
        <![endif]-->
        [[if $email.footer]]
        <!--[if (gte mso 9)|(IE)]>
        <table width="600" align="center" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td>
        <![endif]-->
        <table class="outer footer" align="center">
            <tr>
                <td>
                    <table width="100%">
                        <tr>
                            <td class="inner contents">
                                <div class="content">
                                    <table>
                                        <tr>
                                            <td>
                                                <div class="footer center">[[$email.footer.text]]</div>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <!--[if (gte mso 9)|(IE)]>
                </td>
            </tr>
        </table>
        <![endif]-->
        [[/if]]
    </div>
</center>
</body>
</html>
