<div class="Slice" rel="statistics/verify">
   <?php if ($this->Data('StatisticsVerified')) { ?>
      <div class="StatisticsVerification StatisticsOk"><?php echo T("Verified!"); ?></div>
   <?php } else { ?>
      <div class="StatisticsVerification StatisticsProblem">
         <?php echo T("Problem with credentials."); ?>
         <?php echo $this->Form->Hidden('ClearCredentials',array('value'=>1)); ?>
         <p><?php echo $this->Form->Button('Re-Register API Key', array('class' => 'SmallButton', 'name' => 'Reregister')); ?></p>
      </div>
   <?php } ?>
</div>