<?php if (!defined('APPLICATION')) exit(); ?>
<?php
   $ReportingData = GetValue('Plugin.Reporting.Data', $this->Data);
   
   $Context = GetValue('Context', $ReportingData);
   $UpperContext = ucfirst($Context);
   $ElementID = GetValue('ElementID', $ReportingData);
   $ElementAuthorID = GetValue('ElementAuthorID', $ReportingData);
   $ElementAuthor = GetValue('ElementAuthor', $ReportingData);
   $ElementTitle = GetValue('ElementTitle', $ReportingData);
   $ElementExcerpt = GetValue('ElementExcerpt', $ReportingData);
   $URL = GetValue('URL', $ReportingData);
   $Title = sprintf(T("Report this %s"), $Context);
?>
<h2><?php echo T($Title); ?></h2>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<div class="AwesomePost">
   <ul>
      <li>
         <div class="Excerpt">
            <div><?php echo sprintf(T("%s said:"), UserAnchor($ElementAuthor)); ?></div>
            <div>"<?php echo $ElementExcerpt; ?>"</div>
         </div>
         <div class="Warning">
            <?php echo sprintf(T("You consider this <b>%s</b> to be awesome, and you want us to take a look. If you're sure you want to do this, please enter a brief reason/explanation below."), $Context); ?>
         </div>
      </li>
      <li>
         <?php
            echo $this->Form->Label('Reason', 'Plugin.Reporting.Reason');
            echo Wrap($this->Form->TextBox('Plugin.Reporting.Reason', array('MultiLine' => TRUE)), 'div', array('class' => 'TextBoxWrapper'));
         ?>
      </li>
      <?php
         $this->FireEvent('ReportContentAfter');
      ?>
   </ul>
   <?php echo $this->Form->Close('This is awesome!', '', array('class' => 'button self-clearing')); ?>
</div>