<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
if (!is_array($this->Roles))
   $this->Roles = array();

$this->FireEvent('BeforeInfo');
if (Gdn::Config('Garden.Profile.ShowAbout')) {
?>
<div class="Info About">
   <h3><?php echo T('Basic Information'); ?></h3>
   <dl>
      <?php               
      if ($this->User->ShowEmail == 1 || $Session->CheckPermission('Garden.Registration.Manage')) {
         echo '<dt>'.T('Email').'</dt>
         <dd>'.Gdn_Format::Email($this->User->Email).'</dd>';
      }
      ?>
      <dt class="Label Joined"><?php echo T('Joined'); ?><dt>
      <dd class="Value Joined"><?php echo Gdn_Format::Date($this->User->DateFirstVisit); ?></dd>
      <dt class="Label Visits"><?php echo T('Visits'); ?><dt>
      <dd class="Value Visits"><?php echo $this->User->CountVisits; ?></dd>
      <dt class="Label LastActive"><?php echo T('Last Active'); ?><dt>
      <dd class="Value LastActive"><?php echo Gdn_Format::Date($this->User->DateLastActive); ?></dd>
      <dt class="Label Roles"><?php echo T('Roles'); ?><dt>
      <dd class="Value Roles"><?php echo implode(', ', $this->Roles); ?></dd>
      <?php               
      if ($this->User->InviteUserID > 0) {
         $Inviter = new stdClass();
         $Inviter->UserID = $this->User->InviteUserID;
         $Inviter->Name = $this->User->InviteName;
         echo '<dt class="Label InvitedBy">'.T('Invited by').'</dt>
         <dd class="Value InvitedBy">'.UserAnchor($Inviter).'</dd>';
      }
      $this->FireEvent('OnBasicInfo');
      ?>
   </dl>
</div>
<?php
}
$this->FireEvent('AfterInfo');
