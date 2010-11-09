<?php if (!defined('APPLICATION')) exit();
// Get the information for displaying the connection information.
if (!($ConnectName = $this->Form->GetFormValue('FullName')))
	$ConnectName = $this->Form->GetFormValue('Name');

$ConnectPhoto = $this->Form->GetFormValue('Photo');
if (!$ConnectPhoto) {
   $ConnectPhoto = '/applications/dashboard/design/images/usericon.gif';
}
$ConnectSource = $this->Form->GetFormValue('ProviderName');
?>
<div class="Connect">
	<h1><?php echo StringIsNullOrEmpty($ConnectSource) ? T("Sign in") : sprintf(T('%s Connect'), $ConnectSource); ?></h1>
	<div class="Box">
	<?php
		echo $this->Form->Open();
		echo $this->Form->Errors();
		if ($ConnectName || $ConnectPhoto):
		?>
		<div class="ConnectInfo">
			<?php
			if ($ConnectPhoto)
				echo Img($ConnectPhoto, array('alt' => T('Profile Picture')));
	
			if ($ConnectName && $ConnectSource) {
				$NameFormat = T('You are connected as %s through %s.');
			} elseif ($ConnectName) {
				$NameFormat = T('You are connected as %s.');
			} elseif ($ConnectSource) {
				$NameFormat = T('You are connected through %2$s.');
			} else {
				$NameFormat = '';
			}
			
			
			$NameFormat = '%1$s';
			echo sprintf(
				$NameFormat,
				'<span class="Name">'.htmlspecialchars($ConnectName).'</span>',
				'<span class="Source">'.htmlspecialchars($ConnectSource).'</span>');
			
			echo Wrap(T('Add Info &amp; Create Account'), 'h3');
			?>
		</div>
		<?php endif; ?>
	
		<?php if ($this->Form->GetFormValue('UserID')): ?>
		<div class="SignedIn">
			<?php echo '<div class="Info">',T('You are now signed in.'),'</div>'; ?>
		</div>
		<?php
		else:
			$ExistingUsers = (array)$this->Data('ExistingUsers', array());
			$NoConnectName = $this->Data('NoConnectName');
			$PasswordMessage = 'Leave blank unless connecting to an exising account.';
		?>
			<ul>
				<li>
					<?php
					if (count($ExistingUsers) == 1 && $NoConnectName) {
						$PasswordMessage = 'Enter your existing account password.';
						$Row = reset($ExistingUsers);
						echo '<div class="FinePrint">',T('You already have an account here.'),'</div>',
							Wrap(sprintf(T('Your registered username: <strong>%s</strong>'), htmlspecialchars($Row['Name'])), 'div', array('class' => 'ExistingUsername'));
						$this->AddDefinition('NoConnectName', TRUE);
						echo $this->Form->Hidden('UserSelect', array('Value' => $Row['UserID']));
					} else {
						echo $this->Form->Label('Username', 'ConnectName');
						echo '<div class="FinePrint">',T('Choose a name to identify yourself on the site.'),'</div>';
	
						if (count($ExistingUsers) > 0) {
							foreach ($ExistingUsers as $Row) {
								echo Wrap($this->Form->Radio('UserSelect', $Row['Name'], array('value' => $Row['UserID'])), 'div');
							}
							echo Wrap($this->Form->Radio('UserSelect', T('Other'), array('value' => 'other')), 'div');
						}
					}
	
					if (!$NoConnectName)
						echo $this->Form->Textbox('ConnectName');
					?>
				</li>
				<li id="ConnectPassword">
					<?php
					echo $this->Form->Label('Password', 'ConnectPassword');
					echo '<div class="FinePrint">',T($PasswordMessage),'</div>';
					echo $this->Form->Input('ConnectPassword', 'password');
					?>
				</li>
			</ul>
	
		<?php
		echo Wrap($this->Form->Button('Connect'), 'div', array('class' => 'ButtonContainer'));
	
		endif;
		
		echo $this->Form->Close();
		?>
	</div>
</div>