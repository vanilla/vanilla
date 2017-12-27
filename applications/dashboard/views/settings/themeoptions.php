<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo $this->data('Title'); ?></h1>

<?php if ($this->data('ThemeInfo.Options.Description')) {
    echo '<div class="padded">',
    $this->data('ThemeInfo.Options.Description'),
    '</div>';
}

$hasCustomStyles = is_array($this->data('ThemeInfo.Options.Styles'));
$hasCustomText = is_array($this->data('ThemeInfo.Options.Text'));

$desc = ($hasCustomStyles) ? t('This theme has customizable styles.', 'You can choose from one of the different styles this theme offers. ') : '';
$desc .= ($hasCustomText) ? t('This theme has customizable text.', 'This theme has text that you can customize.') : '';

helpAsset(t('Theme Options'), $desc);

echo $this->Form->open();
echo $this->Form->errors();
?>

<?php if ($hasCustomStyles): ?>
<section>
    <?php echo subheading(t('Styles')); ?>
    <ul class="label-selector theme-styles">
        <?php
        foreach ($this->data('ThemeInfo.Options.Styles') as $Key => $Options) {
            $Basename = val('Basename', $Options, '%s');
            $Active = '';
            $KeyID = str_replace(' ', '_', $Key);

            if ($this->data('ThemeOptions.Styles.Key') == $Key || (!$this->data('ThemeOptions.Styles.Key') && $Basename == '%s')) {
                $Active = ' active';
            }

            $KeyID = str_replace(' ', '_', $Key);
            echo "<li id=\"{$KeyID}\" class=\"label-selector-item $Active\">"; ?>
            <div class="image-wrap">
                <?php
                // Look for a screenshot for for the style.
                $Screenshot = safeGlob(PATH_THEMES.DS.$this->data('ThemeFolder').DS.'design'.DS.changeBasename('screenshot.*', $Basename), ['gif', 'jpg', 'png']);
                if (is_array($Screenshot) && count($Screenshot) > 0) {
                    $Screenshot = basename($Screenshot[0]);
                } else {
                    $Screenshot = 'images'.DS.'theme-placeholder.svg';
                }
                echo img('/themes/'.$this->data('ThemeFolder').'/design/'.$Screenshot, ['class' => 'label-selector-image', 'alt' => t($Key), 'width' => '160']); ?>
                <div class="overlay">
                    <div class="buttons">
                        <?php echo anchor(t('Select'), '?style='.urlencode($Key), 'js-select-theme btn btn-overlay Hijack', ['Key' => $Key]) ?>
                    </div>
                    <div class="selected">
                        <?php echo dashboardSymbol('checkmark'); ?>
                    </div>
                </div>
            </div>
            <div class="title"><?php echo t($Key); ?></div>
            <?php
            if (isset($Options['Description'])) {
                echo '<div class="description">',
                $Options['Description'],
                '</div>';
            }
            echo '</li>';
        }
        ?>
    </ul>
</section>
<?php endif; ?>

<?php if ($hasCustomText): ?>
<section>
    <?php echo subheading(t('Text')); ?>
    <ul>
        <?php foreach ($this->data('ThemeInfo.Options.Text') as $Code => $Options) {

            echo '<li class="form-group">'; ?>
            <div class="label-wrap">
            <?php echo $this->Form->label('@'.$Code, 'Text_'.$Code);

            if (isset($Options['Description']))
                echo '<div class="info">', $Options['Description'], '</div>'; ?>
            </div>
            <div class="input-wrap">
            <?php switch (strtolower(val('Type', $Options, 'textarea'))) {
                case 'textbox':
                    echo $this->Form->textBox($this->Form->escapeFieldName('Text_'.$Code));
                    break;
                case 'textarea':
                default:
                    echo $this->Form->textBox($this->Form->escapeFieldName('Text_'.$Code), ['MultiLine' => TRUE]);
                    break;
            } ?>
            </div>
            <?php echo '</li>';
        }
        ?>
    </ul>
    <div class="form-footer js-modal-footer">
    <?php echo $this->Form->button('Save'); ?>
    </div>
</section>
<?php endif; ?>

<?php echo '<br />'.$this->Form->close();
