<?php if (!defined('APPLICATION')) exit();
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<h1><?php echo Gdn::Translate('Advanced'); ?></h1>
<ul>
   <li>
      <?php
         echo $this->Form->Label('Archive Discussions', 'Vanilla.Archive.Date');
			echo '<div class="Info">',
				Gdn::Translate('Vanilla.Archive.Description', 'You can choose to archive a the forum discussions from a certain date. Archived discussions are effectively closed, allowing no new posts.'),
				'</div>';
         echo $this->Form->Calendar('Vanilla.Archive.Date');
      ?>
   </li>
</ul>
<?php echo $this->Form->Close('Save');