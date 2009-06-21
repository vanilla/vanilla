<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
?>
<div class="Box">
   <h4><?php echo Gdn::Translate('About'); ?></h4>
   <dl>
      <dt><?php echo Gdn::Translate('Name'); ?><dt>
      <dd><?php echo $this->User->Name; ?></dd>
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
         echo '<dt>'.Gdn::Translate('Invited by').'</dt>
         <dd>'.UserAnchor($this->User->InviteName).'</dd>';
      }
      $this->FireEvent('OnBasicInfo');
      ?>
   </dl>
</div>