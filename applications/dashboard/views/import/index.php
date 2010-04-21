<?php if (!defined('APPLICATION')) exit();
$this->AddSideMenu();
?>
<h2><?php echo T('Import'); ?></h2>
<?php
echo $this->Form->Open(array('enctype' => 'multipart/form-data'));
echo $this->Form->Errors();
?>
<ul>
	<li>
		<p><?php echo T('Select the file to import.'); ?></p>
		<?php echo $this->Form->Input('ImportFile', 'file'); ?>
	</li>
</ul>
<?php echo $this->Form->Close('Upload');