<?php echo '<?xml version="1.0" encoding="utf-8"?>'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
   <title>Fatal Error</title>
   <meta name="robots" content="noindex" />
   <?php
   if ($CssPath !== FALSE)
      echo '<link rel="stylesheet" type="text/css" href="',Asset($CssPath),'" />';
   ?>
</head>
<body>
   <div id="Frame">
      <h1>Fatal Error in <?php echo $SenderObject,'.',$SenderMethod ?>();</h1>
      <div id="Content">
         <h2><?php echo $SenderMessage ?></h2>
         <?php
         if ($SenderCode != '')
            echo '<code>',htmlentities($SenderCode, ENT_COMPAT, 'UTF-8'),"</code>\n";

         if (is_array($ErrorLines) && $Line > -1) {
            echo '<h3>The error occurred on or near: <strong>',$File,'</strong></h3>
            <div class="PreContainer">';
               $LineCount = count($ErrorLines);
               $Padding = strlen($Line+5);
               $Odd = FALSE;
               $Class = '';
               for ($i = 0; $i < $LineCount; ++$i) {
                  if ($i > $Line-6 && $i < $Line+4) {
                     $Class = $Odd === TRUE ? 'Odd' : '';
                     if ($i == $Line - 1) {
                        if ($Class != '')
                           $Class .= ' ';
                           
                        $Class .= 'Highlight';
                     }
                     echo '<pre',($Class == '' ? '' : ' class="'.$Class.'"'),'>',str_pad($i+1, $Padding, " ", STR_PAD_LEFT),': ',htmlentities(str_replace("\n", '', $ErrorLines[$i]), ENT_COMPAT, 'UTF-8'),"</pre>\n";
                     $Odd = $Odd == TRUE ? FALSE : TRUE;
                  }
               }
            echo "</div>\n";
            
         }

         $Backtrace = $SenderTrace;
         if (is_array($Backtrace)) {
            echo '<h3><strong>Backtrace:</strong></h3>
            <div class="PreContainer">';
            $BacktraceCount = count($Backtrace);
            $Odd = FALSE;
            for ($i = 0; $i < $BacktraceCount; ++$i) {
               echo '<pre'.($Odd === FALSE ? '' : ' class="Odd"').'>';
               
               if (array_key_exists('file', $Backtrace[$i])) {
                  $File = '['.$Backtrace[$i]['file'].':'
                  .$Backtrace[$i]['line'].'] ';
               }
               echo $File , '<strong>'
                  ,array_key_exists('class', $Backtrace[$i]) ? $Backtrace[$i]['class'] : 'PHP'
                  ,array_key_exists('type', $Backtrace[$i]) ? $Backtrace[$i]['type'] : '::'
                  ,$Backtrace[$i]['function'],'();</strong>'
               ,"</pre>\n";
               $Odd = $Odd == TRUE ? FALSE : TRUE;
            }
            
            echo "</div>\n";
         }
         // Dump queries if present.
         $Database = Gdn::Database();
         if (!is_null($Database) && method_exists($Database, 'Queries')) {
            $Queries = $Database->Queries();
            $QueryTimes = $Database->QueryTimes();
            if (count($Queries) > 0) {
               echo '<h3><strong>Queries:</strong></h3>
               <div class="PreContainer">';
               $Odd = FALSE;
               foreach ($Queries as $Key => $QueryInfo) {
                  $Query = $QueryInfo['Sql'];
                  // this is a bit of a kludge. I found that the regex below would mess up when there were incremented named parameters. Ie. it would replace :Param before :Param0, which ended up with some values like "'4'0".
                  $tmp = (array)$QueryInfo['Parameters'];
                  arsort($tmp);
                  foreach ($tmp as $Name => $Parameter) {
                     $Pattern = '/(.+)('.$Name.')([\W\s]*)(.*)/';
                     $Replacement = "$1'".htmlentities($Parameter, ENT_COMPAT, 'UTF-8')."'$3$4";
                     $Query = preg_replace($Pattern, $Replacement, $Query);
                  }
                  echo '<pre'.($Odd === FALSE ? '' : ' class="Odd"').'>',$Query,'; <small>',@number_format($QueryTimes[$Key], 6),'s</small></pre>';
                  $Odd = $Odd == TRUE ? FALSE : TRUE;
               }
               echo "</div>\n";
            }
         }
         
         if (function_exists('CleanErrorArguments') && is_array($Arguments) && count($Arguments) > 0) {
            echo '<h3><strong>Variables in local scope:</strong></h3>
            <div class="PreContainer">';
            $Odd = FALSE;
            CleanErrorArguments($Arguments);
            foreach ($Arguments as $Key => $Value) {
               // Don't echo the configuration array as it contains sensitive information
               if (!in_array($Key, array('Config', 'Configuration'))) {
                  echo '<pre'.($Odd === FALSE ? '' : ' class="Odd"').'><strong>['.$Key.']</strong> ';
                  echo htmlentities(var_export($Value, TRUE), ENT_COMPAT, 'UTF-8');
                  echo "</pre>\r\n";
                  $Odd = $Odd == TRUE ? FALSE : TRUE;
               }
            }
            echo "</div>\n";
         }
         ?>
         <h2>Need Help?</h2>
         <p>If you are a user of this website, you can report this message to a website administrator.</p>
         <p>If you are an administrator of this website, you can get help at the <a href="http://vanillaforums.org/discussions/" target="_blank">Vanilla Community Forums</a>.</p>
      </div>
      <div id="MoreInformation">
         <h2>Additional information for support personnel:</h2>
         <ul>
            <li><strong>Application:</strong> <?php echo APPLICATION ?></li>
            <li><strong>Application Version:</strong> <?php echo APPLICATION_VERSION ?></li>
            <li><strong>PHP Version:</strong> <?php echo PHP_VERSION ?></li>
            <li><strong>Operating System:</strong> <?php echo PHP_OS ?></li>
            <?php
               if (array_key_exists('SERVER_SOFTWARE', $_SERVER))
                  echo '<li><strong>Server Software:</strong> ',$_SERVER['SERVER_SOFTWARE'],"</li>\n";
            
               if (array_key_exists('HTTP_REFERER', $_SERVER))
                  echo '<li><strong>Referer:</strong> ',$_SERVER['HTTP_REFERER'],"</li>\n";
      
               if (array_key_exists('HTTP_USER_AGENT', $_SERVER))
                  echo '<li><strong>User Agent:</strong> ',$_SERVER['HTTP_USER_AGENT'],"</li>\n";
      
               if (array_key_exists('REQUEST_URI', $_SERVER))
                  echo '<li><strong>Request Uri:</strong> ',$_SERVER['REQUEST_URI'],"</li>\n";
            ?>
            <li><strong>Controller:</strong> <?php echo $SenderObject ?></li>
            <li><strong>Method:</strong> <?php echo $SenderMethod ?></li>
         </div>
         </ul>
      </div>
   </div>
</body>
</html>