<?php if (!defined('APPLICATION')) exit();
// Make sure to force this form to post to the correct place in case the view is
// rendered within another view (ie. /dashboard/entry/index/):
?>
<h1><?php echo T("Sign in") ?></h1>
<div class="Box">
<?php
   echo $this->Form->Open(array('Action' => Url('/entry/handshake/'.$this->HandshakeScheme), 'id' => 'Form_User_Handshake'));
	echo $this->Form->Errors();
	?>
	<div class="Info"><?php
	// printf(
		//T('There is already an account with the same username (%1$s) or email (%2$s) as you. You can either create a new account, or you can enter the credentials for your existing forum account.'),
		echo Wrap(T("This is the first time you've visited the discussion forums."), 'strong');
		echo Wrap(T("You can either create a new account, or enter your credentials if you have an existing account."), 'div');
		// ArrayValue('Name', $this->Data),
		// ArrayValue('Email', $this->Data)
	// );
	?></div>
	<ul class="NewAccount">
		<li><h2><?php echo T('Give me a new account'); ?></h2></li>
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
		<li class="Buttons">
			<?php echo $this->Form->Button('Create New Account', array('Name' => 'User/NewAccount')); ?>
		</li>
	</ul>
	<ul class="LinkAccount">
		<li><h2><?php echo T('Link my existing account'); ?></h2></li>
		<li>
			<?php
				echo $this->Form->Label('Email', 'SignInEmail');
				echo $this->Form->TextBox('SignInEmail');
			?>
		</li>
		<li>
			<?php
				echo $this->Form->Label('Password', 'SignInPassword');
				echo $this->Form->Input('SignInPassword', 'password');
			?>
		</li>
		<li class="Buttons">
			<?php echo $this->Form->Button('Link Existing Account', array('Name' => 'User/LinkAccount')); ?>
		</li>
	</ul>
	<?php
		// echo $this->Form->Button("Get me outta here!", array('Name' => 'User/StopLinking')); 
		echo $this->Form->Close();
	?>
</div>
