<?php if (!defined('APPLICATION')) exit();
echo Wrap($this->Data('Title'), 'h1');
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<div class="Info">
<?php echo T('Civil Tongue lets you make a list of words that are not allowed on the forum and replace them. This plugins also helps to make your forum suitable for younger audiences.'); ?>
</div>

<ul>
	<li>
		<?php
         echo $this->Form->Label('Forbidden words', 'Plugins.CivilTongue.Words');
			echo Wrap(T('Seperate each word with a semi-colon ";"'), 'p');
			echo $this->Form->TextBox('Plugins.CivilTongue.Words', array('MultiLine' => TRUE));
		?>
	</li>
	<li>
		<?php
         echo $this->Form->Label('Replacement word', 'Plugins.CivilTongue.Replacement');
			echo Wrap(T('Enter the word you wish to replace the banned word with.'), 'p');
			echo $this->Form->TextBox('Plugins.CivilTongue.Replacement');
		?>
	</li>
</ul>


<?php echo $this->Form->Close('Save');