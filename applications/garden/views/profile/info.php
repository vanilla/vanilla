<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
if (!is_array($this->Roles))
   $this->Roles = array();

$this->FireEvent('BeforeInfo');
?>
<div class="Info">
   <h3><?php echo Gdn::Translate('Basic Information'); ?></h3>
   <dl>
      <?php               
      if ($this->User->ShowEmail == 1 || $Session->CheckPermission('Garden.Registration.Manage')) {
         echo '<dt>'.Gdn::Translate('Email').'</dt>
         <dd>'.Format::Email($this->User->Email).'</dd>';
      }
      ?>
      <dt><?php echo Gdn::Translate('Joined'); ?><dt>
      <dd><?php echo Format::Date($this->User->DateFirstVisit); ?></dd>
      <dt><?php echo Gdn::Translate('Visits'); ?><dt>
      <dd><?php echo $this->User->CountVisits; ?></dd>
      <dt><?php echo Gdn::Translate('Last Active'); ?><dt>
      <dd><?php echo Format::Date($this->User->DateLastActive); ?></dd>
      <dt><?php echo Gdn::Translate('Roles'); ?><dt>
      <dd><?php echo implode(', ', $this->Roles); ?></dd>
      <?php               
      if ($this->User->InviteUserID > 0) {
         $Inviter = new stdClass();
         $Inviter->UserID = $this->User->InviteUserID;
         $Inviter->Name = $this->User->InviteName;
         echo '<dt>'.Gdn::Translate('Invited by').'</dt>
         <dd>'.UserAnchor($Inviter).'</dd>';
      }
      $this->FireEvent('OnBasicInfo');
      ?>
   </dl>
</div>
<?php
$this->FireEvent('AfterInfo');
