<?php if (!defined('APPLICATION')) exit(); ?>
<?php
   $UcContext = ucfirst($this->Data['Plugin.Flagging.Data']['Context']);
   $ElementID = $this->Data['Plugin.Flagging.Data']['ElementID'];
   $URL = $this->Data['Plugin.Flagging.Data']['URL'];
   $Title = sprintf("Flag this %s",ucfirst($this->Data['Plugin.Flagging.Data']['Context']));
?>
<h2><?php echo T($Title); ?></h2>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <li>
      <div class="Warning">
         <?php echo T('FlagForReview', "You are about to flag this for moderator review. If you're sure you want to do this,
         please enter a brief reason below, then press 'Flag this!'."); ?>
      </div>
      <?php echo T('FlagLinkContent', 'Link to content:') .' '. Anchor(T('FlagLinkFormat', "{$UcContext} #{$ElementID}"), $URL); ?> &ndash;
         <?php echo $this->Data['Plugin.Flagging.Data']['ElementAuthor']; ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Reason', 'Plugin.Flagging.Reason');
         echo $this->Form->TextBox('Plugin.Flagging.Reason', array('MultiLine' => TRUE));
      ?>
   </li>
   <?php
      $this->FireEvent('FlagContentAfter');
   ?>
</ul>
<?php echo $this->Form->Close('Flag this!');