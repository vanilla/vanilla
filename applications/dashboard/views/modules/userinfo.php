<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
if (Gdn::Config('Garden.Profile.ShowAbout')) {
   require_once Gdn::Controller()->FetchViewLocation('helper_functions', 'Profile', 'Dashboard');
   
?>
<div class="About P">
   <h2 class="H"><?php echo T('About'); ?></h2>
   <dl class="About">
      <?php
      if ($this->User->Banned) {
         echo '<dd class="Value"><span class="Tag Tag-Banned">'.T('Banned').'</span></dd>';
      }
      ?>
      <dt class="Name"><?php echo T('Username'); ?></dt>
      <dd class="Name" itemprop="name"><?php echo $this->User->Name; ?></dd>
      
      <?php if ($Loc = GetValue('Location', $this->User)): ?>
      <dt class="Location"><?php echo T('Location'); ?></dt>
      <dd class="Location"><?php echo htmlspecialchars($Loc); ?></dd>
      <?php endif; ?>
      
      <?php               
      if ($this->User->Email && ($this->User->ShowEmail || $Session->CheckPermission('Garden.Moderation.Manage'))) {
         echo '<dt class="Email">'.T('Email').'</dt>
         <dd class="Email" itemprop="email">'.Gdn_Format::Email($this->User->Email).'</dd>';
      }
      ?>
      <dt class="Joined"><?php echo T('Joined'); ?></dt>
      <dd class="Joined"><?php echo Gdn_Format::Date($this->User->DateFirstVisit, 'html'); ?></dd>
      <dt class="Visits"><?php echo T('Visits'); ?></dt>
      <dd class="Visits"><?php echo number_format($this->User->CountVisits); ?></dd>
      <dt class="LastActive"><?php echo T('Last Active'); ?></dt>
      <dd class="LastActive"><?php echo Gdn_Format::Date($this->User->DateLastActive, 'html'); ?></dd>
      <dt class="Roles"><?php echo T('Roles'); ?></dt>
      <dd class="Roles"><?php 
         if (Gdn::Session()->CheckPermission('Garden.Moderation.Manage')) {
            echo UserVerified($this->User).', ';
         }
         
         if (empty($this->Roles))
            echo T('No Roles');
         else
            echo htmlspecialchars(implode(', ', ConsolidateArrayValuesByKey($this->Roles, 'Name'))); 
      
      ?></dd>
      <?php if ($Points = GetValueR('User.Points', $this, 0)) : // Only show positive point totals ?>
      <dt class="Points"><?php echo T('Points'); ?></dt>
      <dd class="Points"><?php echo number_format($Points); ?></dd>
      <?php 
      endif; 
      
      if ($Session->CheckPermission('Garden.Moderation.Manage')): ?>
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
         $Inviter = Gdn::UserModel()->GetID($this->User->InviteUserID);
         if ($Inviter) {
            echo '<dt class="Invited">'.T('Invited by').'</dt>
            <dd class="Invited">'.UserAnchor($Inviter).'</dd>';
         }
      }
      $this->FireEvent('OnBasicInfo');
      ?>
   </dl>
</div>
<?php
}