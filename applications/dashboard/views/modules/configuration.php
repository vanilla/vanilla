<?php if (!defined('APPLICATION')) exit();

/** @var ConfigurationModule $Sf */
$Sf = $this->ConfigurationModule;
/* @var Gdn_Form $Form */
$Form = $Sf->form();

if ($Sf->RenderAll) {
    echo '<h1>', $Sf->controller()->data('Title'), '</h1>';
    if ($Sf->controller()->data('Description')) {
        echo '<div class="padded">', $Sf->controller()->data('Description'), '</div>';
    }
}

$Options = [];
if ($Sf->hasFiles()) {
    $Options['enctype'] = 'multipart/form-data';
}

echo $Form->open($Options);
echo $Form->errors();
?>
<ul>
    <?php

    foreach ($Sf->schema() as $Row) {

        if ((strtolower($Row['Control'])) !== 'imageupload') {
            if (val('no-grid', $Row['Options'])) {
                echo "<li>\n  ";
            } else {
                echo "<li class=\"form-group\">\n  ";
            }
        }

        $LabelCode = $Sf->labelCode($Row);
        $Description = val('Description', $Row, '');
        if (strtolower($Row['Control']) !== 'checkbox' && $Description) {
            $Description = '<div class="info">'.$Description.'</div>';
        }

        switch (strtolower($Row['Control'])) {
            case 'categorydropdown':
                echo '<div class="label-wrap">';
                echo $Form->label($LabelCode, $Row['Name']);
                echo $Description;
                echo '</div>';
                echo '<div class="input-wrap">';
                echo $Form->categoryDropDown($Row['Name'], $Row['Options']);
                echo '</div>';
                break;
            case 'labelcheckbox':
                echo $Form->label($LabelCode);
                echo $Form->checkBox($Row['Name'], '', $Row['Options']);
                break;
            case 'checkbox':
                echo '<div class="label-wrap">';
                echo $Description;
                echo '</div>';
                echo '<div class="input-wrap">';
                echo $Form->checkBox($Row['Name'], t($LabelCode), $Row['Options']);
                echo '</div>';
                break;
            case 'toggle':
                echo $Form->toggle($Row['Name'], t($LabelCode), $Row['Options'], $Description);
                break;
            case 'dropdown':
                echo '<div class="label-wrap">';
                echo $Form->label($LabelCode, $Row['Name']);
                echo $Description;
                echo '</div>';
                echo '<div class="input-wrap">';
                echo $Form->dropDown($Row['Name'], $Row['Items'], $Row['Options']);
                echo '</div>';
                break;
            case 'imageupload':
                $removeUrl = 'asset/deleteconfigimage/'.urlencode($Row['Name']);
                echo $Form->imageUploadPreview($Row['Name'], $LabelCode, $Description, $removeUrl, $Row['Options']);
                break;
            case 'color':
                echo '<div class="label-wrap">';
                echo $Form->label($LabelCode, $Row['Name']);
                echo $Description;
                echo '</div>';
                echo '<div class="input-wrap">';
                echo $Form->color($Row['Name'], $Row['Options']);
                echo '</div>';
                break;
            case 'radiolist':
                echo '<div class="label-wrap">';
                echo $Form->label($LabelCode, $Row['Name'], ['class' => 'radiolist-label']);
                echo $Description;
                echo '</div>';
                echo '<div class="input-wrap">';
                echo $Form->radioList($Row['Name'], $Row['Items'], $Row['Options']);
                echo '</div>';
                break;
            case 'checkboxlist':
                echo $Form->label($LabelCode, $Row['Name']);
                echo $Description;
                echo $Form->checkBoxList($Row['Name'], $Row['Items'], null, $Row['Options']);
                break;
            case 'textbox':
                echo '<div class="label-wrap">';
                echo $Form->label($LabelCode, $Row['Name']);
                echo $Description;
                echo '</div>';
                echo '<div class="input-wrap">';
                echo $Form->textBox($Row['Name'], $Row['Options']);
                echo '</div>';
                break;
            default:
                echo "Error a control type of {$Row['Control']} is not supported.";
                break;
        }

        echo "\n</li>\n";
    }
    ?>
</ul>
<?php echo $Form->close('Save', '', $Sf->controller()->data('FormFooter', [])); ?>
