<?php if (!defined('APPLICATION')) exit();

$Sf = $this->ConfigurationModule;
$Form = $Sf->Form();

if ($Sf->RenderAll) {
    echo '<h1>', $Sf->Controller()->data('Title'), '</h1>';
    if ($Sf->Controller()->data('Description')) {
        echo '<div class="Info">', $Sf->Controller()->data('Description'), '</div>';
    }
}

$Options = array();
if ($Sf->HasFiles())
    $Options['enctype'] = 'multipart/form-data';

echo $Form->open($Options);
echo $Form->errors();
?>
<ul>
    <?php

    foreach ($Sf->Schema() as $Row) {
        echo "<li>\n  ";

        $LabelCode = $Sf->LabelCode($Row);
        $Description = val('Description', $Row, '');
        if ($Description)
            $Description = '<div class="Info">'.$Description.'</div>';

        switch (strtolower($Row['Control'])) {
            case 'categorydropdown':
                echo $Form->label($LabelCode, $Row['Name']);
                echo $Description;
                echo $Form->CategoryDropDown($Row['Name'], $Row['Options']);
                break;
            case 'labelcheckbox':
                echo $Form->label($LabelCode);
                echo $Form->CheckBox($Row['Name'], '', $Row['Options']);
                break;
            case 'checkbox':
                echo $Description;
                echo $Form->CheckBox($Row['Name'], t($LabelCode), $Row['Options']);
                break;
            case 'dropdown':
                echo $Form->label($LabelCode, $Row['Name']);
                echo $Description;
                echo $Form->DropDown($Row['Name'], $Row['Items'], $Row['Options']);
                break;
            case 'imageupload':
                echo $Form->label($LabelCode, $Row['Name']);
                echo $Form->ImageUpload($Row['Name'], $Row['Options']);
                break;
            case 'radiolist':
                echo $Form->label($LabelCode, $Row['Name']);
                echo $Description;
                echo $Form->RadioList($Row['Name'], $Row['Items'], $Row['Options']);
                break;
            case 'checkboxlist':
                echo $Form->label($LabelCode, $Row['Name']);
                echo $Description;
                echo $Form->CheckBoxList($Row['Name'], $Row['Items'], null, $Row['Options']);
                break;
            case 'textbox':
                echo $Form->label($LabelCode, $Row['Name']);
                echo $Description;
                echo $Form->textBox($Row['Name'], $Row['Options']);
                break;
            default:
                echo "Error a control type of {$Row['Control']} is not supported.";
                break;
        }

        echo "\n</li>\n";
    }
    ?>
</ul>
<?php echo $Form->close('Save'); ?>
