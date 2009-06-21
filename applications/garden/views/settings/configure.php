<?php if (!defined('APPLICATION')) exit();
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<h1><?php echo Gdn::Translate('General Settings'); ?></h1>
<ul>
   <li>
      <?php
         echo $this->Form->Label('Language', 'Garden.Locale');
         echo $this->Form->DropDown('Garden.Locale', $this->LocaleData, array('TextField' => 'Code', 'ValueField' => 'Code'));
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Application Title', 'Garden.Title');
         echo $this->Form->TextBox('Garden.Title');
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->CheckBox('Garden.RewriteUrls', "Use Garden's .htaccess file to rewrite urls");
      ?>
   </li>
</ul>
<?php echo $this->Form->Close('Save');
