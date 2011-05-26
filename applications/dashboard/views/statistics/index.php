<?php if (!defined('APPLICATION')) exit(); ?>
<style type="text/css">
   body .NotifyMessage {
      margin: 0 20px 20px;
      padding: 20px;
      background: #dbf3fc;
      color: #222222;
   }
   
   body .NotifyMessage strong {
      color: #252525;
   }
</style>
<div class="Help Aside">
   <?php
   echo '<h2>', T('Need More Help?'), '</h2>';
   echo '<ul>';
   echo '<li>', Anchor(T('Vanilla Statistics Plugin'), '/settings/plugins#vanillastats-plugin'), '</li>';
   echo '<li>', Anchor(T('Statistics Documentation'), 'http://vanillaforums.org/docs/vanillastatistics'), '</li>';
   echo '</ul>';
   ?>
</div>
<h1><?php echo $this->Data('Title'); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<div class="Info">
   <?php echo T("The Vanilla Statistics plugin turns your forum's dashboard into an analytics reporting tool", "Vanilla Statistics turns your forum's dashboard into an analytics reporting tool, allowing you to review activity on your forum over specific time periods. You can <a href=\"http://vanillaforums.org/docs/vanillastatistics\">read more about Vanilla Statistics</a> in our documentation."); ?>
</div>
<?php if ($this->Data('NotifyMessage') !== FALSE) { ?>
<div class="Info NotifyMessage">
   <?php
      echo "<strong>".T("Last time your forum communicated with the statistics server it received the following message:")."</strong>";
      echo "<p><i>".Gdn_Format::Html($this->Data('NotifyMessage'))."</i></p>"; 
   ?>
</div>
<?php } ?>
<?php
if ($this->Data('AnalyticsEnabled')) {
   echo $this->FetchView('configuration','statistics','dashboard');
} else {
   echo $this->FetchView('disabled','statistics','dashboard');
}

echo $this->Form->Close();