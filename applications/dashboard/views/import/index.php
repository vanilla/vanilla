<?php if (!defined('APPLICATION')) exit();
$this->AddSideMenu();
?>
<h2><?php echo T('Import'); ?></h2>
<?php
echo '<div class="Info">',
	T('Garden.Import.Description', 'Use this page to import data from another forum that was exported using Vanilla\'s exporter.'),
	  '</div>';

echo $this->Form->Open(array('enctype' => 'multipart/form-data'));
echo $this->Form->Errors();
?>
<ul>
	<li>
		<p><?php 
         $OriginalFilename = GetValue('OriginalFilename', $this->Data);
         
         if($OriginalFilename) :
				echo T('Import File: '.htmlentities($OriginalFilename)); 
			
			else :
			   echo T('Select the file to import.'); ?></p><?php
			   echo $this->Form->Input('ImportFile', 'file');
			   
			endif;
		?>
	</li>
	<li>
		<?php
		echo $this->Form->Radio('Overwrite', T('Garden.Import.Overwrite', 'Overwrite this forum.'), array('value' => 'overwrite', 'default' => 'overwrite'));
		echo '<div class="Info">',
		T('Garden.Import.Overwrite.Desciption', 'This option will delete all of the user and discussion data in this forum. You must choose a new admin user from the import data below.'),
		'</div>';
		
		echo $this->Form->Label('Email', 'Email'),
			$this->Form->TextBox('Email');
		
		echo $this->Form->Label('Password', 'Password'),
			$this->Form->Input('Password', 'password');
		?>
	</li>
	<li>
		<?php
		echo $this->Form->Radio('Overwrite', T('Garden.Import.Merge', 'Merge with this forum.'), array('value' => 'merge'));
		echo '<div class="Info">',
		T('Garden.Import.Merge.Description', 'This will merge all of the user and discussion data from the import into this forum.
<b>Warning: If you merge the same data twice you will get duplicate discussions.</b>'),
		'</div>';
		
		?>
	</li>
</ul>
<?php echo $this->Form->Close('Upload');