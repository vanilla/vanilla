<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
if (Gdn::Config('Garden.Profile.ShowAbout')) {
?>
<div class="Box About">
   <h4><?php echo T('About'); ?></h4>
   <dl>
      <dt class="Name"><?php echo T('Username'); ?></dt>
      <dd class="Name"><?php echo $this->User->Name; ?></dd>
      <?php               
      if ($this->User->ShowEmail == 1 || $Session->CheckPermission('Garden.Registration.Manage')) {
         echo '<dt class="Email">'.T('Email').'</dt>
         <dd class="Email">'.Gdn_Format::Email($this->User->Email).'</dd>';
      }
      ?>
      <dt class="Joined"><?php echo T('Joined'); ?></dt>
      <dd class="Joined"><?php echo Gdn_Format::Date($this->User->DateFirstVisit); ?></dd>
      <dt class="Visits"><?php echo T('Visits'); ?></dt>
      <dd class="Visits"><?php echo number_format($this->User->CountVisits); ?></dd>
      <dt class="LastActive"><?php echo T('Last Active'); ?></dt>
      <dd class="LastActive"><?php echo Gdn_Format::Date($this->User->DateLastActive); ?></dd>
      <dt class="Roles"><?php echo T('Roles'); ?></dt>
      <dd class="Roles"><?php echo implode(', ', $this->Roles); ?></dd>
      <?php if ($Session->CheckPermission('Garden.Moderation.Manage')): ?>
      <dt class="IP"><?php echo T('Register IP'); ?></dt>
      <dd class="IP"><?php 
         $IP = IPAnchor($this->User->InsertIPAddress);
         echo $IP ? $IP : T('n/a');
      ?></dd>
      <dt class="IP"><?php echo T('Last IP'); ?></dt>
      <dd class="IP"><?php
         $IP = IPAnchor($this->User->LastIPAddress);
         echo $IP ? $IP : T('n/a');
      ?></dd>
      <?php
      endif;

      if ($this->User->InviteUserID > 0) {
         $Inviter = new stdClass();
         $Inviter->UserID = $this->User->InviteUserID;
         $Inviter->Name = $this->User->InviteName;
         echo '<dt class="Invited">'.T('Invited by').'</dt>
         <dd class="Invited">'.UserAnchor($Inviter).'</dd>';
      }
      $this->FireEvent('OnBasicInfo');
      ?>
   </dl>
</div>
<?php
}