<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Help Aside">
   <?php
   echo '<h2>', T('Need More Help?'), '</h2>';
   echo '<ul>';
   echo '<li>', Anchor(T('jsConnect Documentation'), 'http://vanillaforums.org/docs/jsconnect'), '</li>';
   echo '<li>', Anchor(T('jsConnect Client Libraries'), 'http://vanillaforums.org/docs/jsconnect#libraries'), '</li>';
   echo '</ul>';
   ?>
</div>
<h1><?php echo $this->Data('Title'); ?></h1>
<?php
echo $this->Form->Open(), $this->Form->Errors();
?>
<ul>
   <li>
     <?php
     echo $this->Form->Label('AuthenticationKey', 'AuthenticationKey'),
     '<div class="Info">'.T('The client ID uniqely identifies the site.', 'The client ID uniqely identifies the site. You can generate a new ID with the button at the bottom of this page.').'</div>',
      $this->Form->TextBox('AuthenticationKey');
     ?>
   </li>
   <li>
     <?php
     echo $this->Form->Label('AssociationSecret', 'AssociationSecret'),
     '<div class="Info">'.T('The secret secures the sign in process.', 'The secret secures the sign in process. Do <b>NOT</b> give the secret out to anyone.').'</div>',
      $this->Form->TextBox('AssociationSecret');
     ?>
   </li>
   <li>
     <?php
     echo $this->Form->Label('Site Name', 'Name'),
     '<div class="Info">'.T('Enter a short name for the site.', 'Enter a short name for the site. This is displayed on the signin buttons.').'</div>',
      $this->Form->TextBox('Name');
     ?>
   </li>
   <li>
     <?php
     echo $this->Form->Label('Authenticate Url', 'AuthenticateUrl'),
     '<div class="Info">'.T('The location of the jsonp formatted authentication data.').'</div>',
     $this->Form->TextBox('AuthenticateUrl', array('class' => 'InputBox BigInput'));
     ?>
   </li>
   <li>
     <?php
     echo $this->Form->Label('Sign In Url', 'SignInUrl'),
     '<div class="Info">'.T('The url that users use to sign in.').'</div>',
      $this->Form->TextBox('SignInUrl', array('class' => 'InputBox BigInput'));
     ?>
   </li>
   <li>
     <?php
     echo $this->Form->Label('Register Url', 'RegisterUrl'),
     '<div class="Info">'.T('The url that users use to register for a new account.').'</div>',
      $this->Form->TextBox('RegisterUrl', array('class' => 'InputBox BigInput'));
     ?>
   </li>
   <li>
     <?php
     echo $this->Form->Label('Sign Out Url', 'SignOutUrl'),
     '<div class="Info">'.T('The url that users use to sign out of your site.').'</div>',
      $this->Form->TextBox('SignOutUrl', array('class' => 'InputBox BigInput'));
     ?>
   </li>
   <li>
      <?php
         echo $this->Form->CheckBox('Trusted', 'This is trusted connection and can sync roles & permissions.');
      ?>
   </li>
   <li>
     <?php
      echo $this->Form->CheckBox('IsDefault', 'Make this connection your default signin method.');
     ?> 
   </li>
   <li>
      <h2>Advanced</h2>
   </li>
   <li>
      <?php
      $HashAlgos = hash_algos();
      $HashAlgos = ArrayCombine($HashAlgos, $HashAlgos);
      echo $this->Form->Label('Hash Algorithm', 'HashType'),
      '<div class="Info">'.T("Choose md5 if you're not sure what to choose.", "You can select a custom hash algorithm to sign your requests. The hash algorithm must also be used in your client library. Choose md5 if you're not sure what to choose.").'</div>',
      $this->Form->DropDown('HashType', $HashAlgos, array('Default' => 'md5'));
      ?>
   </li>
   <li>
     <?php
      echo $this->Form->CheckBox('TestMode', 'This connection is in test-mode.');
     ?> 
   </li>
</ul>

<?php
echo '<div class="Buttons">';
echo $this->Form->Button('Save');
echo $this->Form->Button('Generate Client ID and Secret', array('Name' => 'Generate'));
echo '</div>';

echo $this->Form->Close();