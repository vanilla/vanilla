<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
?>
<div class="Help Aside">
   <?php
   echo Wrap(T('Need More Help?'), 'h2');
   echo '<ul>';
   echo Wrap(Anchor(T("Video tutorial on user registration"), 'settings/tutorials/user-registration'), 'li');
   echo '</ul>';
   ?>
</div>
<h1><?php echo T('Manage Applicants'); ?></h1>
<?php
   echo $this->Form->Open(array('action' => Url('/dashboard/user/applicants')));
   echo $this->Form->Errors();
   $NumApplicants = $this->UserData->NumRows();
   if ($NumApplicants == 0) { ?>
      <div class="Info"><?php echo T('There are currently no applicants.'); ?></div>
   <?php } else { ?>
<?php
   $AppText = Plural($NumApplicants, 'There is currently %s applicant', 'There are currently %s applicants');
?>
<div class="Info"><?php echo sprintf($AppText, $NumApplicants); ?></div>
<table class="CheckColumn">
   <thead>
      <tr>
         <td><?php echo T('Action'); ?></td>
         <th class="Alt"><?php echo T('Applicant'); ?></th>
         <th><?php echo T('Options'); ?></th>
      </tr>
   </thead>
   <tbody>
   <?php
   foreach ($this->UserData->Format('Text')->Result() as $User) {
   ?>
      <tr>
         <td><?php echo $this->Form->CheckBox('Applicants[]', '', array('value' => $User->UserID)); ?></td>
         <td class="Alt">
            <?php
            printf(T('<strong>%1$s</strong> (%2$s) %3$s'), $User->Name, Gdn_Format::Email($User->Email), Gdn_Format::Date($User->DateInserted));
            
            $this->EventArguments['User'] = $User;
            $this->FireEvent("ApplicantInfo");
            echo '<blockquote>'.$User->DiscoveryText.'</blockquote>';
            
            $this->EventArguments['User'] = $User;
            $this->FireEvent("AppendApplicantInfo");
         ?></td>
         <td><?php
         echo Anchor(T('Approve'), '/user/approve/'.$User->UserID.'/'.$Session->TransientKey())
            .' '.Anchor(T('Decline'), '/user/decline/'.$User->UserID.'/'.$Session->TransientKey());
         ?></td>
      </tr>
   <?php } ?>
   </tbody>
</table>
<div class="Info">
<?php
   echo $this->Form->Button('Approve', array('Name' => 'Submit', 'class' => 'SmallButton'));
   echo $this->Form->Button('Decline', array('Name' => 'Submit', 'class' => 'SmallButton'));
?></div><?php
}
echo $this->Form->Close();