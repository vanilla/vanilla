<?php if (!defined('APPLICATION')) exit();
?>
<h1><?php echo T("Sign in") ?></h1>
<div class="Box">
<?php
   echo $this->Form->Open();
	echo $this->Form->Errors();

   // Get the information for displaying the connection information.
   if (!($ConnectName = $this->Form->GetFormValue('FullName')))
      $ConnectName = $this->Form->GetFormValue('Name');
   
   $ConnectPhoto = $this->Form->GetFormValue('Photo');

   $ConnectSource = $this->Form->GetFormValue('ProviderName');

   if ($ConnectName || $ConnectPhoto):
	?>
   <div class="ConnectInfo">
      <?php
      if ($ConnectPhoto) {
         echo Img($ConnectPhoto, array('alt' => T('Profile Picture')));
      }

      if ($ConnectName && $ConnectSource) {
         $NameFormat = T('You are connected as %s through %s.');
      } elseif ($ConnectName) {
         $NameFormat = T('You are connected as %s.');
      } elseif ($ConnectSource) {
         $NameFormat = T('You are connected through %s.');
      } else {
         $NameFormat = '';
      }

      echo sprintf(
         $NameFormat,
         '<span class="Name">'.htmlspecialchars($ConnectName).'</span>',
         '<span class="Source">'.htmlspecialchars($ConnectSource).'</span>');
      ?>
   </div>
   <?php endif; ?>

   <?php if ($this->Form->GetFormValue('UserID')): ?>
   <div class="SignedIn">
      <?php
      echo '<div class="Info">',
         T('You are now signed in.'),
         '</div>';
      ?>
   </div>
   <?php else: ?>
      <div class="Info">
         <?php
         echo Wrap(T("This is the first time you've visited the discussion forums."), 'strong');
         echo Wrap(T("You can either create a new account, or enter your credentials if you have an existing account."), 'div');
         ?>
      </div>
      <ul>
         <li>
            <?php
            echo $this->Form->Label('Username', 'ConnectName');

            $ExistingUsers = (array)$this->Data('ExistingUsers', array());
            $NoConnectName = $this->Data('NoConnectName');

            if (count($ExistingUsers) == 1 && $NoConnectName) {
               $Row = reset($ExistingUsers);
               echo '<div class="Info2">',
                  T('It looks like you\'ve registered on the site before with this email address.'),
                  '</div>',
                  '<div>',
                  sprintf(T('You will be identified as: <b>%s</b>'), htmlspecialchars($Row['Name'])),
                  '</div>';
               $this->AddDefinition('NoConnectName', TRUE);
               echo $this->Form->Hidden('UserSelect', array('Value' => $Row['UserID']));
            } else {
               echo '<div class="Info2">',
                  T('Choose a name to identify yourself on the site.'),
                  '</div>';

               if (count($ExistingUsers) > 0) {
                  foreach ($ExistingUsers as $Row) {
                     echo '<div>',
                        $this->Form->Radio('UserSelect', $Row['Name'], array('value' => $Row['UserID'])),
                        '</div>';
                  }
                  echo '<div>',
                     $this->Form->Radio('UserSelect', T('Other'), array('value' => 'other'));
               }
            }

            if (!$NoConnectName)
               echo $this->Form->Textbox('ConnectName');
            ?>
         </li>
         <li id="ConnectPassword">
            <?php
            echo $this->Form->Label('Password', 'ConnectPassword');
            echo '<div class="Info2">',
               T('Enter the existing account\'s password.', 'If you are connecting to an existing account you must enter it\'s password. Otherwise you can leave the password blank.'),
               '</div>';
				
				echo $this->Form->Input('ConnectPassword', 'password');
            ?>
         </li>
      </ul>
   <div>
      
   </div>

	<?php
   echo $this->Form->Button('Connect');

   endif;
   
   echo $this->Form->Close();
	?>
</div>
