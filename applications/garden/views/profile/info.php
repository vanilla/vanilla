<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
if (!is_array($this->Roles))
   $this->Roles = array();

$this->FireEvent('BeforeInfo');
if (Gdn::Config('Garden.Profile.ShowAbout')) {
?>
<div class="Info About">
   <h3><?php echo Gdn::Translate('Basic Information'); ?></h3>
   <dl>
      <?php               
      if ($this->User->ShowEmail == 1 || $Session->CheckPermission('Garden.Registration.Manage')) {
         echo '<dt>'.Gdn::Translate('Email').'</dt>
         <dd>'.Format::Email($this->User->Email).'</dd>';
      }
      ?>
      <dt class="Label Joined"><?php echo Gdn::Translate('Joined'); ?><dt>
      <dd class="Value Joined"><?php echo Format::Date($this->User->DateFirstVisit); ?></dd>
      <dt class="Label Visits"><?php echo Gdn::Translate('Visits'); ?><dt>
      <dd class="Value Visits"><?php echo $this->User->CountVisits; ?></dd>
      <dt class="Label LastActive"><?php echo Gdn::Translate('Last Active'); ?><dt>
      <dd class="Value LastActive"><?php echo Format::Date($this->User->DateLastActive); ?></dd>
      <dt class="Label Roles"><?php echo Gdn::Translate('Roles'); ?><dt>
      <dd class="Value Roles"><?php echo implode(', ', $this->Roles); ?></dd>
      <?php               
      if ($this->User->InviteUserID > 0) {
         $Inviter = new stdClass();
         $Inviter->UserID = $this->User->InviteUserID;
         $Inviter->Name = $this->User->InviteName;
         echo '<dt class="Label InvitedBy">'.Gdn::Translate('Invited by').'</dt>
         <dd class="Value InvitedBy">'.UserAnchor($Inviter).'</dd>';
      }
      $this->FireEvent('OnBasicInfo');
      ?>
   </dl>
</div>
<?php
}
$this->FireEvent('AfterInfo');
