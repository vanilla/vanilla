<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo T($this->Data['Title']); ?></h1>
<div class="Info">
   <?php echo T($this->Data['Description']); ?>
</div>
<div class="FilterMenu">
      <?php
      echo Anchor(
         T($this->Plugin->IsEnabled() ? 'Disable' : 'Enable'),
         $this->Plugin->AutoTogglePath(),
         'SmallButton'
      );
   ?>
</div>
<?php if (!$this->Plugin->IsEnabled()) return; ?>
<h3>Add a Feed</h3>
<div class="AddFeed">
   <?php 
      echo $this->Form->Open(array(
         'action'  => Url('plugin/feeddiscussions/addfeed')
      ));
      echo $this->Form->Errors();
      
      $Refreshments = array(
               "1m"  => T("Every Minute"),
               "5m"  => T("Every 5 Minutes"),
               "30m" => T("Twice Hourly"),
               "1h"  => T("Hourly"),
               "1d"  => T("Daily"),
               "3d"  => T("Every 3 Days"),
               "1w"  => T("Weekly"),
               "2w"  => T("Every 2 Weeks")
            );
      
   ?>
      <ul>
         <li>
            <div class="Info">Add a new Auto Discussion Feed</div>
         <?php
            echo $this->Form->Label('Feed URL', 'FeedURL');
            echo $this->Form->TextBox('FeedURL', array('class' => 'InputBox'));
         ?></li>
         <li><?php
            echo $this->Form->CheckBox('Historical', T('Import Older Posts'), array('value' => '1'));
         ?></li>
               
         <li><?php
            echo $this->Form->Label('Maximum Polling Frequency', 'Refresh');
            echo $this->Form->DropDown('Refresh', $Refreshments, array(
               'value'  => "1d"
            ));
         ?></li>
         
         <li><?php
            echo $this->Form->Label('Target Category', 'Category');
            echo $this->Form->CategoryDropDown('Category');
         ?></li>
      </ul>
   <?php
      echo $this->Form->Close("Add Feed");
   ?>
</div>

<h3><?php echo T('Active Feeds'); ?></h3>
<div class="ActiveFeeds">
<?php
   $NumFeeds = count($this->Data('Feeds'));
   if (!$NumFeeds) {
      echo T("You have no active auto feeds at this time.");
   } else {
      echo "<div>".$NumFeeds." ".Plural($NumFeeds,"Active Feed","Active Feeds")."</div>\n";
      foreach ($this->Data('Feeds') as $FeedURL => $FeedItem) {
         $LastUpdate = $FeedItem['LastImport'];
         $CategoryID = $FeedItem['Category'];
         $Frequency = GetValue($FeedItem['Refresh'], $Refreshments, T('Unknown'));
         $Category = $this->Data("Categories.{$CategoryID}.Name", 'Root');
?>
         <div class="FeedItem">
            <div class="DeleteFeed">
               <a href="<?php echo Url('/plugin/feeddiscussions/deletefeed/'.FeedDiscussionsPlugin::EncodeFeedKey($FeedURL)); ?>">Delete this Feed</a>
            </div>
            <div class="FeedContent">
               <div class="FeedItemURL"><?php echo Anchor($FeedURL,$FeedURL); ?></div>
               <div class="FeedItemInfo">
                  <span>Updated: <?php echo $LastUpdate; ?></span>
                  <span>Refresh: <?php echo $Frequency; ?></span>
                  <span>Category: <?php echo $Category; ?></span>
               </div>
            </div>
         </div>
<?php
      }
   }
?>
</div>
<script type="text/javascript"> 
   jQuery(document).ready(function($) {
      
      // Show drafts delete button on hover
      // Show options on each row (if present)
      $('div.ActiveFeeds div.FeedItem').livequery(function() {
         var row = this;
         var del = $(row).find('div.DeleteFeed');
         $(del).hide();
         $(row).hover(function() {
            $(del).show();
            $(row).addClass('Active');
         }, function() {
            if (!$(del).find('div.FeedItem').hasClass('ActiveFeed'))
               $(del).hide();
               
            $(row).removeClass('ActiveFeed');
         });
      });
   
   });
</script>