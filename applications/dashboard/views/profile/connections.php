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
   
   .DataList-Connections .Item {
    overflow: hidden;
   }

   .Connection-Connect {
       position: absolute;
       right: 0;
       bottom: 0;
       padding: 10px;
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
   <?php foreach ($this->Data('Connections') as $Key => $Row): ?>
   <li class="Item">
      <div class="Connection-Header">
         <span class="IconWrap">
            <?php
               echo Img(GetValue('Icon', $Row, Asset('/applications/dashboard/design/images/connection-64.png')));
            ?>
         </span>
         <span class="Connection-Name">
            <?php
               echo GetValue('Name', $Row, T('Unknown'));
            ?>
         </span>
         <span class="Connection-Connect">
            <?php
            $Connected = GetValue('Connected', $Row);
            $CssClass = $Connected ? 'Active' : 'InActive';
            $ConnectUrl = GetValue('ConnectUrl', $Row);
            
            echo '<span class="ActivateSlider ActivateSlider-'.$CssClass.'">';
            if ($Connected) {
               echo Anchor(T('Connected'), $ConnectUrl, 'Button Primary');
            } else {
               echo Anchor(T('Connect'), $ConnectUrl, 'Button');
            }
            echo '</span>';
            ?>
         </span>
      </div>
   </li>
   <?php endforeach; ?>
</ul>