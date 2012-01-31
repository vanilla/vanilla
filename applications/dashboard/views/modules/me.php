<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
if ($Session->IsValid() && C('Garden.Modules.ShowMeModule')) {
	$Name = $Session->User->Name;
?>
<div class="Box MeBox">
	<?php
	echo UserPhoto($Session->User);
	echo '<div class="WhoIs">';
		echo UserAnchor($Session->User, 'Username');
 		echo Wrap($Session->User->Email, 'div', array('class' => 'ByLine'));
	echo '</div>';
	?>
</div>
<?php
}