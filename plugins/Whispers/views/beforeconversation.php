<?php if (!defined('APPLICATION')) exit(); ?>
<div class="P">
   <div class="Info">
      <?php
      echo FormatString(T('This conversation is a whisper.', 'This conversation is a whisper for <a href="{/discussion,url}/{Conversation.DiscussionID}/x">this discussion</a>.'), $Sender->Data);
      ?>
   </div>
</div>