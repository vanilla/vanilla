<?php if (!defined('APPLICATION')) exit();
echo wrap($this->data('Title'), 'h1');

$desc =  t('Civil Tongue lets you make a list of words that are not allowed on the forum and replace them. This plugins also helps to make your forum suitable for younger audiences.');
echo wrap($desc, 'div', ['class' => 'alert alert-info padded']);

echo $this->Form->open();
echo $this->Form->errors();
?>
<ul>
    <li class="form-group">
        <div class="label-wrap">
            <?php echo $this->Form->label('Forbidden words', 'Plugins.CivilTongue.Words');
            echo wrap(t('Separate each word with a semi-colon ";"'), 'div', ['class' => 'info']); ?>
        </div>
        <div class="input-wrap">
            <?php echo $this->Form->textBox('Plugins.CivilTongue.Words', ['MultiLine' => TRUE]); ?>
        </div>
    </li>
    <li class="form-group">
        <div class="label-wrap">
            <?php echo $this->Form->label('Replacement word', 'Plugins.CivilTongue.Replacement');
            echo wrap(t('Enter the word you wish to replace the banned word with.'), 'div', ['class' => 'info']); ?>
        </div>
        <div class="input-wrap">
            <?php echo $this->Form->textBox('Plugins.CivilTongue.Replacement'); ?>
        </div>
    </li>
</ul>
<?php echo $this->Form->close('Save'); ?>
