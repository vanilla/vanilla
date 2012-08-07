<?php if (!defined('APPLICATION')) return; ?>
<h3><?php echo T('Category Notifications'); ?></h3>
<div class="Info">
   <?php
   echo T('You can follow individual categories and be notified of all posts within them.');
   ?>
</div>
<table class="PreferenceGroup">
   <thead>
      <tr>
         
         <td style="border: none;">&nbsp;</td>
         <td class="TopHeading" colspan="2"><?php echo T('Discussions'); ?></td>
         <td class="TopHeading" colspan="2"><?php echo T('Comments'); ?></td>
      </tr>
      <tr>
         <td style="text-align: left;"><?php echo T('Category'); ?></td>
         <td class="PrefCheckBox BottomHeading"><?php echo T('Email'); ?></td>
         <td class="PrefCheckBox BottomHeading"><?php echo T('Popup'); ?></td>
         <td class="PrefCheckBox BottomHeading"><?php echo T('Email'); ?></td>
         <td class="PrefCheckBox BottomHeading"><?php echo T('Popup'); ?></td>
      </tr>
   </thead>
   <tbody>
      <?php 
      foreach (Gdn::Controller()->Data('CategoryNotifications') as $Category): 
         $CategoryID = $Category['CategoryID'];
      
         if ($Category['Heading']):
         ?>
         <tr>
            <th>
               <b><?php echo $Category['Name']; ?></b>
            </th>
            <th colspan="4">
               &#160;
            </th>
         </tr>
         <?php else: ?>
         <tr>
            <td class="<?php echo "Depth_{$Category['Depth']}"; ?>"><?php echo $Category['Name']; ?></td>
            <td class="PrefCheckBox"><?php echo Gdn::Controller()->Form->CheckBox("Email.NewDiscussion.{$CategoryID}", '', array('value' => 1)); ?></td>
            <td class="PrefCheckBox"><?php echo Gdn::Controller()->Form->CheckBox("Popup.NewDiscussion.{$CategoryID}", '', array('value' => 1)); ?></td>
            <td class="PrefCheckBox"><?php echo Gdn::Controller()->Form->CheckBox("Email.NewComment.{$CategoryID}", '', array('value' => 1)); ?></td>
            <td class="PrefCheckBox"><?php echo Gdn::Controller()->Form->CheckBox("Popup.NewComment.{$CategoryID}", '', array('value' => 1)); ?></td>
         </tr>
      <?php 
         endif;
      endforeach; 
      ?>
   </tbody>
</table>