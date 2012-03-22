<?php
@ob_end_clean();
echo '<?xml version="1.0" encoding="utf-8"?>'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
   <title>Something has gone wrong.</title>
   <meta name="robots" content="noindex" />
   <?php
   if ($CssPath !== FALSE)
      echo '<link rel="stylesheet" type="text/css" href="',Asset($CssPath),'" />';
   ?>
</head>
<body>
   <div id="Content">
      <div class="SplashInfo">
         <h1>Something has gone wrong.</h1>
         <p>
            We've run into a problem and are unable to handle this request right now.
            <br />Please check back in a little while.
         </p>
      </div>
   </div>
</body>
</html>