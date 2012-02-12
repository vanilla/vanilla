<?php if (!defined('APPLICATION')) return; ?>
<div id="Sql" class="DebugInfo">
<h2>Debug Information</h2>
<?php
// Add the canonical Url.
if (method_exists($Sender, 'CanonicalUrl')) {
   $CanonicalUrl = htmlspecialchars($Sender->CanonicalUrl(), ENT_COMPAT, 'UTF-8');

   echo '<div class="CanonicalUrl"><b>'.T('Canonical Url')."</b>: <a href=\"$CanonicalUrl\" accesskey=\"r\">$CanonicalUrl</a></div>";
}
?>

<?php
// Add some cache info.
if (Gdn::Cache()->ActiveEnabled()) {
   echo '<h3>Cache Information</h3>';
   echo '<pre>';
   echo '<b>Cache Revision</b>: '.Gdn::Cache()->GetRevision()."\n";
   echo '<b>Permissions Revision</b>: '.Gdn::UserModel()->GetPermissionsIncrement();
   echo '</pre>';
}
?>

<?php
// Add the queries.
$Database = Gdn::Database();
$SQL = $Database->SQL();

if(!is_null($Database)) {
   $String = '';
   $Queries = $Database->Queries();
   $String .= '<h3>'.count($Queries).' queries in '.$Database->ExecutionTime().'s</h3>';
   foreach ($Queries as $Key => $QueryInfo) {
      $Query = $QueryInfo['Sql'];
      // this is a bit of a kludge. I found that the regex below would mess up when there were incremented named parameters. Ie. it would replace :Param before :Param0, which ended up with some values like "'4'0".
      if(isset($QueryInfo['Parameters']) && is_array($QueryInfo['Parameters'])) {
         $tmp = $QueryInfo['Parameters'];

         $Query = $SQL->ApplyParameters($Query, $tmp);
      }
      $String .= $QueryInfo['Method']
         .'<small>'.@number_format($QueryInfo['Time'], 6).'s</small>'
         .(isset($QueryInfo['Cache']) ? '<div><b>Cache:</b> '.var_export($QueryInfo['Cache'], TRUE).'</div>' : '')
         .'<pre>'.htmlspecialchars($Query).';</pre>';
   }
   echo $String;
}

global $Start;
echo '<h3>Page completed in '.round(Now() - $_SERVER['REQUEST_TIME'], 4).'s</h3>';
?>

<h3>Controller Data</h3>
<pre>
<?php 
   echo DebuggerPlugin::FormatData(Gdn::Controller()->Data); 
?>
</pre>


</div>