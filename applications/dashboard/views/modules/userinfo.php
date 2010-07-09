<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
if (Gdn::Config('Garden.Profile.ShowAbout')) {
?>
<div class="Box About">
   <h4><?php echo T('About'); ?></h4>
   <dl>
      <dt><?php echo T('Username'); ?></dt>
      <dd><?php echo $this->User->Name; ?></dd>
      <?php               
      if ($this->User->ShowEmail == 1 || $Session->CheckPermission('Garden.Registration.Manage')) {
         echo '<dt>'.T('Email').'</dt>
         <dd>'.Gdn_Format::Email($this->User->Email).'</dd>';
      }
      ?>
      <dt><?php echo T('Joined'); ?></dt>
      <dd><?php echo Gdn_Format::Date($this->User->DateFirstVisit); ?></dd>
      <dt><?php echo T('Visits'); ?></dt>
      <dd><?php echo $this->User->CountVisits; ?></dd>
      <dt><?php echo T('Last Active'); ?></dt>
      <dd><?php echo Gdn_Format::Date($this->User->DateLastActive); ?></dd>
      <dt><?php echo T('Roles'); ?></dt>
      <dd><?php echo implode(', ', $this->Roles); ?></dd>
      <?php               
      if ($this->User->InviteUserID > 0) {
         $Inviter = new stdClass();
         $Inviter->UserID = $this->User->InviteUserID;
         $Inviter->Name = $this->User->InviteName;
         echo '<dt>'.T('Invited by').'</dt>
         <dd>'.UserAnchor($Inviter).'</dd>';
      }
      $this->FireEvent('OnBasicInfo');
      ?>
   </dl>
</div>
<?php
}