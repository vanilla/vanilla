<?php if (!defined('APPLICATION')) exit();
/**
* Renders (in the panel) a list of users who share a fingerprint with the specified user.
*/
class SharedFingerprintModule extends Gdn_Module {

	protected $_Data = FALSE;
	
	public function GetData($FingerprintUserID, $Fingerprint) {
		if (!Gdn::Session()->CheckPermission('Garden.Users.Edit'))
			return;
		
		$this->_Data = Gdn::SQL()
			->Select()
			->From('User')
			->Where('Fingerprint', $Fingerprint)
			->Where('UserID <>', $FingerprintUserID)
			->Get();
	}

	public function AssetTarget() {
		return 'Panel';
	}

	public function ToString() {
      if (!$this->_Data)
			return;
      
		if ($this->_Data->NumRows() == 0)
			return;
		
		ob_start();
		?>
      <div id="SharedFingerprint" class="Box">
         <h4><?php echo T("Shared Accounts"); ?> <span class="Count"><?php echo $this->_Data->NumRows(); ?></span></h4>
			<ul class="PanelInfo">
         <?php
			foreach ($this->_Data->Result() as $SharedAccount) {
				echo '<li><strong>'.UserAnchor($SharedAccount).'</strong><br /></li>';
			}
         ?>
			</ul>
		</div>
		<?php
		$String = ob_get_contents();
		@ob_end_clean();
		return $String;
	}
}
