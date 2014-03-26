<div class="Trace">
   <style>
      .Trace {
         width: 100%;
      }
      
      .Trace table {
         width: 100%;
         border-collapse: collapse;
         table-layout: fixed;
      }
      
      .Trace td {
         border-top: solid 1px #efefef;
         border-bottom: solid 1px #efefef;
         padding: 4px;
         vertical-align: top;
      }
      
      .Trace pre {
         margin: 0;
         overflow: auto;
      }
      
      .Trace .TagColumn {
         width: 50px;
      }
      
      .Trace .Tag-Info {
         background: #00A6FF;
         color: #fff;
      }
      
      .Trace .Tag-Warning {
         background: #FF9000;
         color: #fff;
      }
      
      .Trace .Tag-Notice {
         background: #FF9000;
         color: #fff;
      }
      
      .Trace .Tag-Error {
         background: #f00;
         color: #fff;
      }
      
      .Trace pre {
         color: #000;
      }
   </style>
<h2>Debug Trace</h2>
<table>
   <?php 
   foreach ($this->Data('Traces') as $Trace): 
      list($Message, $Type) = $Trace;
   
      $Var = 'Debug';
      if (!in_array($Type, array(TRACE_ERROR, TRACE_INFO, TRACE_NOTICE, TRACE_WARNING))) {
         $Var = $Type;
         $Type = TRACE_INFO;
      }
   ?>
   <tr>
      <td class="TagColumn">
         <span class="Tag Tag-<?php echo Gdn_Format::AlphaNumeric($Type); ?>"><?php echo htmlspecialchars($Type); ?></span>
      </td>
      <td>
         <?php
         if (is_string($Message)) {
            if ($Var != 'Debug')
               echo '<b>'.htmlspecialchars($Var).'</b>: ';
            
            echo nl2br(htmlspecialchars($Message));
         } elseif (is_a($Message, 'Exception')) {
            echo '<pre>';
            echo htmlspecialchars($Message->getMessage());
            echo "\n\n";
            echo htmlspecialchars($Message->getTraceAsString());
            echo '</pre>';
         } else
            echo "<pre><b>$Var:</b> ".htmlspecialchars(var_export($Message, TRUE)).'</pre>';
         ?>
      </td>
   </tr>
   <?php endforeach; ?>
</table>
</div>