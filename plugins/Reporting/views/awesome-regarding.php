<div class="RegardingEvent">
   <span class="InformSprite Heart"/>&nbsp;</span> <?php 
      echo FormatString(T("{ReportingUser} likes this {EntityType} written by {ReportedUser}"), array(
         'ReportingUser'      => UserAnchor(GetValue('ReportingUser', $this->Data('ReportInfo')), 'ReportingUser'),
         'EntityType'         => GetValue('EntityType', $this->Data('ReportInfo')),
         'ReportedUser'       => UserAnchor(GetValue('ReportedUser', $this->Data('ReportInfo')), 'ReportedUser')
      ));
   ?>
   <div class="RegardingTime"><?php 
      $ReportedDate = GetValue('ReportedTime', $this->Data('ReportInfo'));
      echo Gdn_Format::FuzzyTime($ReportedDate);
   ?></div>
   <?php
      $ReportedReason = GetValue('ReportedReason', $this->Data('ReportInfo'), NULL);
      if (!is_null($ReportedReason)) {?>
         <div class="ReportedReason">"<?php echo $ReportedReason; ?>"</div>
         <?php
      }
   ?>
</div>
<div class="RegardingActions">
   <?php 
      $ForeignURL = GetValue('ForeignURL', $this->Data('RegardingData'), NULL);
      if (!is_null($ForeignURL)) {
         ?><div class="ActionButton"><a href="<?php echo $ForeignURL; ?>" title="<?php echo T("Visit awesome content location"); ?>"><?php echo T("Visit"); ?></a></div><?php
      }
   ?>
   <?php $this->Data('RegardingSender')->FireEvent("RegardingActions"); ?>
</div>