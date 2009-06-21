<?php if (!defined('APPLICATION')) exit();
// An individual discussion record for all panel modules to use when rendering a discussion list.
?>
<li>
   <ul>
      <li class="Topic">
         <?php
            echo Anchor($Discussion->Name, '/discussion/'.$Discussion->DiscussionID.'/'.Format::Url($Discussion->Name).($Discussion->CountCommentWatch > 0 ? '/#Item_'.$Discussion->CountCommentWatch : ''), 'DiscussionLink');
         ?>
      </li>
      <li class="Meta">
         <?php
            echo '<span>'.$Discussion->CountComments.'</span>';
            $CountUnreadComments = $Discussion->CountComments - $Discussion->CountCommentWatch;
            if ($CountUnreadComments > 0)
               echo '<strong>'.sprintf('%s new', $CountUnreadComments).'</strong>';
               
            echo '<span>'.Format::Date($Discussion->LastDate).' '.UserAnchor($Discussion->LastName).'</span>';
         ?>
      </li>
   </ul>
</li>
