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
      <?php
      echo '<div>', T('Select the file to import.'), '</div>';
      echo '<div class="Info">',
         T('You can place files in your /uploads folder.',
         'If your file is too large to upload directly to this page you can place it in your /uploads folder.
            Make sure the filename begins with the word <b>export</b> and ends with one of <b>.txt, .gz</b>.'),
           '</div>';

      foreach ($this->Data('ImportPaths') as $Path => $Text) {
         echo '<div>',
            $this->Form->Radio('PathSelect', $Text, array('value' => $Path)),
            '</div>';
      }
      ?>
		<?php 
         $OriginalFilename = GetValue('OriginalFilename', $this->Data);

         echo '<div>';
         if (count($this->Data('ImportPaths')) > 0)
            echo $this->Form->Radio('PathSelect', $this->Form->Input('ImportFile', 'file'), array('value' => 'NEW'));
			else
            echo $this->Form->Input('ImportFile', 'file');
         echo '</div>';

         if($OriginalFilename) {
				echo ' ', T('Current File: '.htmlspecialchars($OriginalFilename));
			}
		?>
	</li>
	<li>
		<?php
		//echo $this->Form->Radio('Overwrite', T('Garden.Import.Overwrite', 'Overwrite this forum.'), array('value' => 'overwrite', 'default' => 'overwrite'));
		echo '<div class="Warning">',
		T('Garden.Import.Overwrite.Desciption', 'All of the user and discussion data in this forum will be overwritten. You must choose a new admin user from the import data below.'),
		'</div>';
		
		echo $this->Form->Label('Email', 'Email'),
			$this->Form->TextBox('Email');
		
		echo $this->Form->Label('Password', 'Password'),
			$this->Form->Input('Password', 'password');
		?>
	</li><?php /*
	<li>
		<?php
		echo $this->Form->Radio('Overwrite', T('Garden.Import.Merge', 'Merge with this forum.'), array('value' => 'merge'));
		echo '<div class="Info">',
		T('Garden.Import.Merge.Description', 'This will merge all of the user and discussion data from the import into this forum.
<b>Warning: If you merge the same data twice you will get duplicate discussions.</b>'),
		'</div>';
		
		?>
	</li> */?>
</ul>
<?php echo $this->Form->Close('Upload');