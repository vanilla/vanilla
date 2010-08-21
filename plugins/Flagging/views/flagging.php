<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo T($this->Data['Title']); ?></h1>
<div class="Info">
   <?php echo T('The following content has been flagged by users for moderator review.'); ?>
</div>
<div class="FilterMenu">
   <?php
      $ToggleName = C('Plugins.Flagging.Enabled') ? T('Disable Content Flagging') : T('Enable Content Flagging');
      echo "<div>".Wrap(Anchor($ToggleName, 'plugin/flagging/toggle/'.Gdn::Session()->TransientKey(), 'SmallButton'))."</div>";
   ?>
</div>
<?php if (C('Plugins.Flagging.Enabled')) { ?>
<div class="FlaggedContent">
   <?php
      $NumFlaggedItems = count($this->FlaggedItems);
      if (!$NumFlaggedItems) {
         echo T("There are no items awaiting moderation at this time.");
      } else {
         echo "<h3>".$NumFlaggedItems." ".Plural($NumFlaggedItems,"item","items")." in queue</h3>\n";
         foreach ($this->FlaggedItems as $URL => $FlaggedList) {
   ?>
            <div class="FlaggedItem">
               <?php
                  $TitleCell = TRUE;
                  ksort($FlaggedList,SORT_STRING);
                  $NumComplaintsInThread = sizeof($FlaggedList);
                  foreach ($FlaggedList as $FlagIndex => $Flag) {
                     if ($TitleCell) {
                        $TitleCell = FALSE;
               ?>
                        <div class="FlaggedTitleCell">
                           <div class="FlaggedItemURL"><?php echo Anchor(Url($Flag['ForeignURL'],TRUE),$Flag['ForeignURL']); ?></div>
                           <div class="FlaggedItemInfo">
                              <?php
                                 if ($NumComplaintsInThread > 1)
                                    $OtherString = T(' and').' '.($NumComplaintsInThread-1).' '.T(Plural($NumComplaintsInThread-1, 'other', 'others')).' '.T('person');
                                 else
                                    $OtherString = '';
                              ?>
                              <span><?php echo T("Reported by: "); ?></span>
                              <span><?php echo "<strong>".Anchor($Flag['InsertName'],"profile/{$Flag['InsertUserID']}/{$Flag['InsertName']}")."</strong>{$OtherString} ".T('on').' '.$Flag['DateInserted']; ?></span>
                           </div>
                           <div class="FlaggedItemComment">"<?php echo $Flag['Comment']; ?>"</div>
                           <div class="FlaggedActions">
                              <?php 
                                 echo $this->Form->Button('Dismiss',array(
                                    'onclick'      => "window.location.href='".Url('plugin/flagging/dismiss/'.$Flag['EncodedURL'],TRUE)."'",
                                    'class' => 'SmallButton'
                                 ));
                                 echo $this->Form->Button('Take Action',array(
                                    'onclick'      => "window.location.href='".Url($Flag['ForeignURL'],TRUE)."'",
                                    'class' => 'SmallButton'
                                 ));
                              ?>
                           </div>
                        </div>
               <?php
                        if ($NumComplaintsInThread > 1)
                           echo '<div class="OtherComplaints">'."\n";
                     } else {
               ?>
                        <div class="FlaggedOtherCell">
                           <div class="FlaggedItemInfo"><?php echo T('On').' '.$Flag['DateInserted'].', <strong>'.Anchor($Flag['InsertName'],"profile/{$Flag['InsertUserID']}/{$Flag['InsertName']}").'</strong> '.T('said:'); ?></div>
                           <div class="FlaggedItemComment">"<?php echo $Flag['Comment']; ?>"</div>
                        </div>
               <?php
                     }
                  }
                  if ($NumComplaintsInThread > 1)
                     echo "</div>\n";
               ?>
            </div>
   <?php
         }
      }
   ?>
</div>
<?php } ?>