<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta name="viewport" content="width=device-width"/>
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

    h1 {font-size: 36px;}
    h2 {font-size: 28px;}
    h3 {font-size: 24px;}
    h4 {font-size: 20px;}

    body, table, p, td {
      font-size: 16px;
    }

    p {
      margin-bottom: 10px;
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
    }

    /* Grid */

    .body-wrap,
    .footer-wrap {
      width: 100%;
    }

    .body-wrap {
      padding: 10px 10px 0;
    }

    .footer-wrap {
      padding: 0px 10px 10px;
    }

    .container {
      display: block !important;
      max-width: 600px !important;
      margin: 0 auto !important;
      clear: both !important;
    }

    .content {
      padding: 20px 30px;
      max-width: 600px;
      margin: 0 auto;
      display: block;
    }

    .content table {
      width: 100%;
    }

    .button {
      text-decoration: none;
      padding: 10px 20px;
      text-align: center;
      font-weight: 700;
      cursor: pointer;
      display: inline-block;
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

    .footer-wrap,
    .body-wrap {
      background-color: [[$email.backgroundColor]];
    }

    .content,
    .container td {
      background-color: [[$email.containerBackgroundColor]];
    }

    .footer-wrap .content,
    .footer-wrap .container td {
      background-color: [[$email.button.backgroundColor]];
    }

    body, table, h1, h2, h3, h4, h5, h6, p, td {
      color: [[$email.textColor]];
    }

    .footer {
      color: [[$email.button.textColor]];
    }

    hr {
      background-color: [[$email.textColor]];
    }

    .button {
      color: [[$email.button.textColor]];
      background-color: [[$email.button.backgroundColor]];
    }
  </style>
</head>
<body bgcolor="[[$email.backgroundColor]]">
<table class="body-wrap">
  <tr>
    <td class="container">
      <div class="content">
        <table>
          <tr>
            <td>
              [[if $email.image]]
                <div class="image-wrap center">
                  [[if $email.image.link]]
                    <a href="[[$email.image.link]]">
                  [[/if]]
                  [[if $email.image.source != '']]
                    <img class="center" src="[[$email.image.source]]" alt="[[$email.image.alt]]">
                  [[elseif $email.image.alt != '']]
                    <h1 class="center">[[$email.image.alt]]</h1>
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
        <!-- content end below -->
      </div>
      <!-- container end below -->
    </td>
  </tr>
</table>
<table class="footer-wrap">
  [[if $email.footer]]
  <tr>
    <td class="container">
      <div class="content">
        <table>
          <tr>
            <td>
              <div class="footer center">[[$email.footer]]</div>
            </td>
          </tr>
        </table>
      </div>
    </td>
  </tr>
  [[/if]]
</table>
</body>
</html>
