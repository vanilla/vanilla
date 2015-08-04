<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo $this->data('Title'); ?></h1>

<?php if ($this->data('ThemeInfo.Options.Description')) {
    echo '<div class="Info">',
    $this->data('ThemeInfo.Options.Description'),
    '</div>';
}
?>

<?php
echo $this->Form->open();
echo $this->Form->errors();
?>

<?php if (is_array($this->data('ThemeInfo.Options.Styles'))): ?>
    <h3><?php echo t('Styles'); ?></h3>
    <table class="SelectionGrid ThemeStyles">
        <tbody>
        <?php
        $Alt = FALSE;
        $Cols = 3;
        $Col = 0;
        $Classes = array('FirstCol', 'MiddleCol', 'LastCol');

        foreach ($this->data('ThemeInfo.Options.Styles') as $Key => $Options) {
            $Basename = val('Basename', $Options, '%s');

            if ($Col == 0)
                echo '<tr>';

            $Active = '';
            if ($this->data('ThemeOptions.Styles.Key') == $Key || (!$this->data('ThemeOptions.Styles.Key') && $Basename == '%s'))
                $Active = ' Active';

            $KeyID = str_replace(' ', '_', $Key);
            echo "<td id=\"{$KeyID}_td\" class=\"{$Classes[$Col]}$Active\">";
            echo '<h4>', t($Key), '</h4>';

            // Look for a screenshot for for the style.
            $Screenshot = SafeGlob(PATH_THEMES.DS.$this->data('ThemeFolder').DS.'design'.DS.ChangeBasename('screenshot.*', $Basename), array('gif', 'jpg', 'png'));
            if (is_array($Screenshot) && count($Screenshot) > 0) {
                $Screenshot = basename($Screenshot[0]);
                echo img('/themes/'.$this->data('ThemeFolder').'/design/'.$Screenshot, array('alt' => t($Key), 'width' => '160'));
            }

            $Disabled = $Active ? ' Disabled' : '';
            echo '<div class="Buttons">',
            anchor(t('Select'), '?style='.urlencode($Key), 'SmallButton SelectThemeStyle'.$Disabled, array('Key' => $Key)),
            '</div>';

            if (isset($Options['Description'])) {
                echo '<div class="Info2">',
                $Options['Description'],
                '</div>';
            }

            echo '</td>';

            $Col = ($Col + 1) % 3;
            if ($Col == 0)
                echo '</tr>';
        }
        if ($Col > 0)
            echo '<td colspan="'.(3 - $Col).'">&#160;</td></tr>';

        ?>
        </tbody>
    </table>

<?php endif; ?>

<?php if (is_array($this->data('ThemeInfo.Options.Text'))): ?>
    <h3><?php echo t('Text'); ?></h3>
    <div class="Info">
        <?php echo t('This theme has customizable text.', 'This theme has text that you can customize.'); ?>
    </div>

    <ul>
        <?php foreach ($this->data('ThemeInfo.Options.Text') as $Code => $Options) {

            echo '<li>',
            $this->Form->label('@'.$Code, 'Text_'.$Code);

            if (isset($Options['Description']))
                echo '<div class="Info2">', $Options['Description'], '</div>';

            switch (strtolower(val('Type', $Options, 'textarea'))) {
                case 'textbox':
                    echo $this->Form->textBox($this->Form->EscapeString('Text_'.$Code));
                    break;
                case 'textarea':
                default:
                    echo $this->Form->textBox($this->Form->EscapeString('Text_'.$Code), array('MultiLine' => TRUE));
                    break;
            }


            echo
            '</li>';
        }
        ?>
    </ul>
    <?php
    echo $this->Form->button('Save');
endif;
?>

<?php
echo '<br />'.$this->Form->close();
