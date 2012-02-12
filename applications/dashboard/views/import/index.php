<?php if (!defined('APPLICATION')) exit();
$this->AddSideMenu();
?>
<div class="Help Aside">
   <?php
   echo '<h2>', T('Need More Help?'), '</h2>';
   echo '<ul>';
   echo '<li>', Anchor(T('Importing to Vanilla'), 'http://vanillaforums.com/porter'), '</li>';
   echo '<li>', Anchor(T('The Vanilla 2 Exporter'), 'http://vanillaforums.org/addon/porter-core'), '</li>';
   echo '</ul>';
   ?>
</div>
<h1><?php echo T('Import'); ?></h1>
<?php
echo '<div class="Info">'
	.sprintf(T('Garden.Import.Description', 'Use this page to import data from another forum that was exported using Vanilla\'s exporter. For more information <a href="%s">see our importing documentation</a>.'), 'http://vanillaforums.com/blog/help-topics/importing-data/')
   .Wrap(T('You can place files in your /uploads folder.', 'If your file is too
		large to upload directly to this page you can place it in your /uploads
		folder. Make sure the filename begins with the word <b>export</b> and ends
		with one of <b>.txt, .gz</b>.'), 'p')
	.'</div>';

if ($this->Data('LoadSpeedWarning')) {
   echo '<div class="Warning">',
   T('Warning: Loading tables can be slow.', '<b>Warning</b>: Your server configuration does not support fast data loading.
If you are importing a very large file (ex. over 200,000 comments) you might want to consider changing your configuration. Click <a href="http://vanillaforums.com/porter">here</a> for more information.'),
   '</div>';
}
?>
<div class="Messages Errors">
	<ul>
		<li><?php echo T('Garden.Import.Overwrite.Description', 'Warning: All data in this forum will be overwritten.'); ?></li>
	</ul>
</div>
<?php
echo $this->Form->Open(array('enctype' => 'multipart/form-data'));
echo $this->Form->Errors();
?>
<ul>
	<li>
      <?php
      echo Wrap(T('Select the import source'), 'label');
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
				echo ' ', T('Current File:').' '.htmlspecialchars($OriginalFilename);
			}
		?>
	</li>
	<li>
		<?php
		//echo $this->Form->Radio('Overwrite', T('Garden.Import.Overwrite', 'Overwrite this forum.'), array('value' => 'overwrite', 'default' => 'overwrite'));
		echo Wrap(T('Garden.Import.InputInstructions', 'Enter the email and password of the admin user from the data being imported.'), 'div');
		
		echo $this->Form->Label('Email', 'Email'),
			$this->Form->TextBox('Email');
		
		echo $this->Form->Label('Password', 'Password'),
			$this->Form->Input('Password', 'password');

      echo $this->Form->CheckBox('UseCurrentPassword', 'Use My Current Password');
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
<h3><?php echo T('Advanced Options'); ?></h3>
<ul>
   <li>
      <?php
      echo $this->Form->CheckBox('GenerateSQL', 'Generate import SQL only');
      ?>
   </li>
</ul>
<div class="Buttons">
<?php echo $this->Form->Close('Start'); ?>
</div>