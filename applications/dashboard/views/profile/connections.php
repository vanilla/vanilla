<?php if (!defined('APPLICATION')) exit(); ?>

<style>
   .Conneciton-Header * {
      line-height: 48px;
   }
   
   .Connection-Name {
      font-size: 28px;
   }
   
   .IconWrap {
      margin-right: 10px;
   }
   
   .IconWrap img {
      height: 48px;
      width: 48px;
      vertical-align: bottom;
      border-radius: 3px;
   }
   
   .DataList-Connections .Connection-Header {
    overflow: hidden;
   }

   .Connection-Connect {
       position: absolute;
       right: 0;
       top: 0;
       padding: 20px 10px;
   }
</style>

<h2 class="H"><?php echo $this->Data('Title'); ?></h2>

<div class="Hero">
   <h3><?php echo T("What's This?"); ?></h3>
   <p>
      <?php
      echo Gdn_Format::Markdown(T('Connect your profile to social networks.', "Connect your profile to social networks to be notified of activity here and share your activity with your friends and followers."));
      ?>
   </p>
</div>

<ul class="DataList DataList-Connections">
   <?php
   foreach ($this->Data('Connections') as $Key => $Row) {
      WriteConnection($Row);
   }
   ?>
</ul>