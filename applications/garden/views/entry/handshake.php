<div id="Handshake" class="AjaxForm">
<?php if (!defined('APPLICATION')) exit();
// Make sure to force this form to post to the correct place in case the view is
// rendered within another view (ie. /garden/entry/index/):
echo $this->Form->Open(array('Action' => Url('/entry/handshake'), 'id' => 'Form_User_Handshake'));
?>
<h1><?php echo Gdn::Translate("Handshake") ?></h1>
<?php echo $this->Form->Errors(); ?>
<div class="">
	<p><?php echo Gdn::Translate('There is another user in the system that has the same Username and/or Email as you.
										  You\'ll have to select a new one to access the system.'); ?></p>
	<ul>
	<?php
	echo '<li><label>', Gdn::Translate('Username'), '</label><span>', $this->Data['Name'], '</span></li>';
	echo '<li><label>', Gdn::Translate('Email'), '</label><span>', $this->Data['Email'], '</span></li>';
	?>
	</ul>
</div>
<ul>
	<li><h2>
		<?php
			echo $this->Form->Radio('Handshake', 'Create a new user.', array('value' => 'NEW'));
		?></h2>
	</li>
	<li>
		<?php
			echo $this->Form->Label('Username', 'NewName');
			echo $this->Form->Textbox('NewName');
		?>
	</li>
	<li>
		<?php
			echo $this->Form->Label('Email', 'NewEmail');
			echo $this->Form->Textbox('NewEmail');
		?>
	</li>
	<li><h2>
		<?php
			echo $this->Form->Radio('Handshake', 'Associate with an existing user.', array('value' => 'ASSIGN'));
		?></h2>
	</li>
   <li>
      <?php
         echo $this->Form->Label('Username', 'SignInName');
         echo $this->Form->TextBox('SignInName');
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Password', 'SignInPassword');
         echo $this->Form->Input('SignInPassword', 'password');
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Button('Sign In →');
      ?>
   </li>
</ul>
<?php echo $this->Form->Close();?>
</div>