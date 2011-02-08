<?php if (!defined('APPLICATION')) exit();

$Sf = $this->ConfigurationModule;
$Form = $Sf->Form();

if ($Sf->RenderAll) {
   echo '<h1>', $Sf->Controller()->Data('Title'), '</h1>';
}

echo $Form->Open();
echo $Form->Errors();
?>
<ul>
   <?php
   
   foreach ($Sf->Schema() as $Row) {
      echo "<li>\n  ";

      $LabelCode = $Sf->LabelCode($Row);

      switch (strtolower($Row['Control'])) {
         case 'checkbox':
            echo $Form->CheckBox($Row['Name'], T($LabelCode));
            break;
         case 'dropdown':
            echo $Form->Label($LabelCode, $Row['Name']);
            echo $Form->DropDown($Row['Name'], $Row['Items'], $Row['Options']);
            break;
         case 'radiolist':
            echo $Form->RadioList($Row['Name'], $Row['Items'], $Row['Options']);
            break;
         case 'textbox':
            echo $Form->Label($LabelCode, $Row['Name']);
            echo $Form->TextBox($Row['Name'], $Row['Options']);
            break;
         default:
            echo "Error a control type of {$Row['Control']} is not supported.";
            break;
      }

      echo "\n</li>\n";
   }
   ?>
</ul>
<?php echo $Form->Close('Save'); ?>