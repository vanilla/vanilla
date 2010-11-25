<div class="UpdateBig UpdatePrompt">
   <h2><?php echo T('Your forum is out of date.'); ?></h2>
   <?php echo sprintf("The latest version is <b>%s</b> and you are running <b>%s</b>.", $this->Data('LatestVersion'), $this->Data('CurrentVersion')); ?>
   <p>
      <div><?php echo T('We suggest you update automatically. Would you like to update now?'); ?></div>
      <div>
         <?php
            echo $this->Form->Open();
            echo $this->Form->Errors();
            
            echo Anchor("No thanks", Url('/dashboard/settings'), "Button");
            
            echo $this->Form->Close('Update Now', '', array(
               'class'  => 'Button'
            ));
         ?>
      </div>
   </p>
</div>