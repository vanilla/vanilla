<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo T('User Registration Settings'); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();

echo Gdn::Slice('/dashboard/role/defaultroleswarning');

?>
<ul>
   <li id="RegistrationMethods">
      <div class="Info"><?php echo T('Change the way that new users register with the site.'); ?></div>
      <table class="Label AltColumns">
         <thead>
            <tr>
               <th><?php echo T('Method'); ?></th>
               <th class="Alt"><?php echo T('Description'); ?></th>
            </tr>
         </thead>
         <tbody>
         <?php
            $Count = count($this->RegistrationMethods);
            $i = 0;
            $Alt = FALSE;
            foreach ($this->RegistrationMethods as $Method => $Description) {
               $Alt = $Alt ? FALSE : TRUE;
               $CssClass = $Alt ? 'Alt' : '';
               ++$i;
               if ($Count == $i)
                  $CssClass .= ' Last';
               
               $CssClass = trim($CssClass);
               ?>
               <tr<?php echo $CssClass != '' ? ' class="'.$CssClass.'"' : ''; ?>>
                  <th><?php
                     $MethodName = $Method;
                     if ($MethodName == 'Captcha')
                        $MethodName = 'Basic';
                        
                     echo $this->Form->Radio('Garden.Registration.Method', $MethodName, array('value' => $Method));
                  ?></th>
                  <td class="Alt"><?php echo T($Description); ?></td>
               </tr>
               <?php
            }
         ?>
         </tbody>
      </table>
   </li>
   <?php
   /*
   <li id="NewUserRoles">
      <div class="Info"><?php echo T('Check all roles that should be applied to new/approved users:'); ?></div>
      <?php echo $this->Form->CheckBoxList('Garden.Registration.DefaultRoles', $this->RoleData, $this->ExistingRoleData, array('TextField' => 'Name', 'ValueField' => 'RoleID')); ?>
   </li>
   */
   ?>
   <li id="CaptchaSettings">
      <div class="Info"><?php echo T('<strong>The basic registration form requires</strong> that new users copy text from a "Captcha" image to keep spammers out of the site. You need an account at <a href="http://recaptcha.net/">recaptcha.net</a>. Signing up is FREE and easy. Once you have signed up, come back here and enter the following settings:'); ?></div>
      <table class="Label AltColumns">
         <thead>
            <tr>
               <th><?php echo T('Key Type'); ?></th>
               <th class="Alt"><?php echo T('Key Value'); ?></th>
            </tr>
         </thead>
         <tbody>
            <tr class="Alt">
               <th><?php echo T('Public Key'); ?></th>
               <td class="Alt"><?php echo $this->Form->TextBox('Garden.Registration.CaptchaPublicKey'); ?></td>
            </tr>
            <tr>
               <th><?php echo T('Private Key'); ?></th>
               <td class="Alt"><?php echo $this->Form->TextBox('Garden.Registration.CaptchaPrivateKey'); ?></td>
            </tr>
         </tbody>
       </table>
   </li>
   <li id="InvitationExpiration">
      <?php
         echo $this->Form->Label('Invitations will expire', 'Garden.Registration.InviteExpiration');
         echo $this->Form->DropDown('Garden.Registration.InviteExpiration', $this->InviteExpirationOptions, array('value' => $this->InviteExpiration));
      ?>
   </li>
   <li id="InvitationSettings">
      <div class="Info">
      <?php
         echo sprintf(T('Invitations can be sent from users\' profile pages.',
            'When you use registration by invitation users will have a link called <a href="%s" class="Popup">My Invitations</a> on their profile pages.'),
            Url('/dashboard/profile/invitations')),
            '<br /><br />';
         
         echo T('Choose who can send out invitations to new members:');
      ?>
      </div>
      <table class="Label AltColumns">
         <thead>
            <tr>
               <th><?php echo T('Role'); ?></th>
               <th class="Alt"><?php echo T('Invitations per month'); ?></th>
            </tr>
         </thead>
         <tbody>
         <?php
            $i = 0;
            $Count = $this->RoleData->NumRows();
            $Alt = FALSE;
            foreach ($this->RoleData->Result() as $Role) {
               $Alt = $Alt ? FALSE : TRUE;
               $CssClass = $Alt ? 'Alt' : '';
               ++$i;
               if ($Count == $i)
                  $CssClass .= ' Last';
               
               $CssClass = trim($CssClass);
               $CurrentValue = ArrayValue($Role->RoleID, $this->ExistingRoleInvitations, FALSE);
               ?>
               <tr<?php echo $CssClass != '' ? ' class="'.$CssClass.'"' : ''; ?>>               
                  <th><?php echo $Role->Name; ?></th>
                  <td class="Alt">
                     <?php
                     echo $this->Form->DropDown('InvitationCount[]', $this->InvitationOptions, array('value' => $CurrentValue));
                     echo $this->Form->Hidden('InvitationRoleID[]', array('value' => $Role->RoleID));
                     ?>
                  </td>
               </tr>
               <?php
            }
         ?>
         </tbody>
      </table>
   </li>
</ul>
<?php echo $this->Form->Close('Save');