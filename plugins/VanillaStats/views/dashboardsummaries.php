<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Summary PopularDiscussionSummary">
   <table>
      <thead>
         <tr>
            <th><?php echo T('Popular Discussions'); ?></th>
            <td><?php echo T('Comments'); ?></td>
            <td><?php echo T('Follows'); ?></td>
            <td><?php echo T('Views'); ?></td>
         </tr>
      </thead>
      <tbody>
         <?php foreach ($this->Data['DiscussionData'] as $Discussion) { ?>
         <tr>
            <th><?php echo Anchor(htmlspecialchars($Discussion->Name), DiscussionUrl($Discussion)); ?></th>
            <td><?php echo number_format($Discussion->CountComments); ?></td>
            <td><?php echo number_format($Discussion->CountBookmarks); ?></td>
            <td><?php echo number_format($Discussion->CountViews); ?></td>
         </tr>
         <?php } ?>
      </tbody>
   </table>
</div>
<div class="Summary ActiveUserSummary">
   <table>
      <thead>
         <tr>
            <th><?php echo T('Active Users'); ?></th>
            <!-- <td><?php echo T('Discussions'); ?></td> -->
            <td><?php echo T('Comments'); ?></td>
            <!-- <td><?php echo T('PageViews'); ?></td> -->
         </tr>
      </thead>
      <tbody>
         <?php foreach ($this->Data['UserData'] as $User) { ?>
         <tr>
            <th><?php echo Anchor($User->Name, 'profile/'.$User->UserID.'/'.Gdn_Format::Url($User->Name)); ?></th>
            <td><?php echo number_format($User->CountComments); ?></td>
            <!-- <td><?php // echo number_format($Discussion->CountViews); ?></td> -->
         </tr>
         <?php } ?>
      </tbody>
   </table>
</div>