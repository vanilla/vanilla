<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta name="viewport" content="width=device-width"/>
  <style>
    <?php include('ink.css'); ?>
    table.container {
      width: 470px !important;
      margin: 20px auto !important;
      background-color: #fff;
    }
    td.wrapper {
      padding: 20px 0px 10px !important;
    }
    body,
    table.body,
    p,
    td {
      font-size: 16px;
      line-height: 22px;
      font-weight: 300;
    }
    table.columns td.button-wrapper {
        padding: 0px 0px 20px !important;
    }
    table.footer.columns td {
      padding-bottom: 0;
    }
    table.footer td p {
      font-size: 12px;
      font-weight: 400;
      color: #333;
    }
    img {
      max-width: 75%;
      float: none;
      margin: 0 auto;
    }
    a {
      color: [[$email.link.color]];
    }
    a h1 {
        color: [[$email.link.color]];
    }
    table.button.button-custom td a {
      color: [[$email.button.color]];
      padding: 15px 0;
    }
    table.columns table.button.button-custom td {
      background-color: [[$email.button.backgroundColor]];
      padding: 0;
      border: 0;
    }
  </style>
</head>
<body>
<table class="body" style="background-color: [[$email.backgroundColor]];">
  <tr>
    <td class="center" align="center" valign="top">
      <center>
        <table class="row">
          <tr>
            <td class="center" align="center">
              <center>
                <table class="container">
                  <tr>
                    <td class="wrapper last">
                      <table class="eight columns">
                        [[if $email.image]]
                        <tr class="image-row">
                          <td>
                            [[if $email.image.link]]
                            <a href="[[$email.image.link]]">
                            [[/if]]
                              [[if $email.image.source != '']]
                                <img src="[[$email.image.source]]" alt="[[$email.image.alt]]">
                              [[elseif $email.image.alt != '']]
                                <h1 class="center">[[$email.image.alt]]</h1>
                              [[/if]]
                            [[if $email.image.link]]
                            </a>
                            [[/if]]
                          </td>
                        </tr>
                        <tr>
                          <td>
                            <hr />
                          </td>
                        </tr>
                        [[/if]]
                        <tr>
                          <td>
                            [[if $email.title]]<h1 class="center">[[$email.title]]</h1>[[/if]]
                            [[if $email.lead]]<p class="lead center">[[$email.lead]]</p>[[/if]]
                          </td>
                        </tr>
                      </table>
                      [[if $email.button]]
                      <table class="four columns">
                        <tr>
                          <td class="center button-wrapper">
                            <center>
                              <table class="button button-custom">
                                <tr>
                                  <td>
                                    <a href="[[$email.button.url]]">[[$email.button.text]]</a>
                                  </td>
                                </tr>
                              </table>
                            </center>
                          </td>
                        </tr>
                      </table>
                      [[/if]]
                      <table class="eight columns">
                        <tr>
                          <td>
                            <p class="message">[[$email.message]]
                              [[if $email.link]]<a href="[[$email.link.url]]" style="color: [[$email.link.color]]">[[$email.link.text]]</a>[[/if]]
                            </p>
                          </td>
                        </tr>
                      </table>
                      [[if $email.footer]]
                      <table class="footer eight columns">
                        <tr>
                          <td class="center">
                            <center>
                              <p class="footer center">[[$email.footer]]</p>
                            </center>
                          </td>
                        </tr>
                      </table>
                      [[/if]]
                    </td>
                  </tr>
                </table>
              </center>
              <!-- container end below -->
            </td>
          </tr>
        </table>
      </center>
    </td>
  </tr>
</table>
</body>
</html>
