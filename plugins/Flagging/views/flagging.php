<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo T($this->Data['Title']); ?></h1>
<div class="Info">
   <?php echo T('FlaggedContent', 'The following content has been flagged by users for moderator review.'); ?>
</div>

<?php 
   // Settings
   echo $this->Form->Open();
   echo $this->Form->Errors();
   ?>
   <h3><?php echo T('Flagging Settings'); ?></h3>
   <ul>
      <li><?php echo $this->Form->CheckBox('Plugins.Flagging.UseDiscussions', T('Create Discussions')); ?></li>
      <li>
         <?php
            echo $this->Form->Label('Category to Use', 'Plugins.Flagging.CategoryID');
            echo $this->Form->CategoryDropDown('Plugins.Flagging.CategoryID', C('Plugins.Flagging.CategoryID'));
         ?>
      </li>
   </ul>
   <?php 
   echo $this->Form->Close('Save');
   
   // Flagged Items list   
   echo "<h3>".T('Flagged Items')."</h3>\n";
   echo '<div class="FlaggedContent">';
   $NumFlaggedItems = count($this->FlaggedItems);
   if (!$NumFlaggedItems) {
      echo T('FlagQueueEmpty', "There are no items awaiting moderation at this time.");
   } else {
      echo sprintf(
         T('Flagging queue counter', '%s in queue.'),
         Plural($NumFlaggedItems, '%s post', '%s posts')
      );
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
                                    $OtherString = T(' and').' '.($NumComplaintsInThread-1).' '.T(Plural($NumComplaintsInThread-1, 'other', 'others'));
                                 else
                                    $OtherString = '';
                              ?>
                              <span><?php echo T('FlaggedBy', "Reported by:"); ?> </span>
                              <span><?php printf(T('<strong>%s</strong>%s on %s'), Anchor($Flag['InsertName'],"profile/{$Flag['InsertUserID']}/{$Flag['InsertName']}"), $OtherString, $Flag['DateInserted']); ?></span>
                           </div>
                           <div class="FlaggedItemComment">"<?php echo Gdn_Format::Text($Flag['Comment']); ?>"</div>
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
                           <div class="FlaggedItemComment">"<?php echo Gdn_Format::Text($Flag['Comment']); ?>"</div>
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
