<?php if (!defined('APPLICATION')) exit();

echo '<h1>', $this->Data('Title'), '</h1>';

$Form = $this->Form; //new Gdn_Form();
echo $Form->Open();
echo $Form->Errors();
?>
<div class="Info">
   <?php echo sprintf(T('This plugin helps locale package development.', 'This plugin helps locale package development. The plugin keeps a working locale pack at <code>%s</code>.'),
      $this->Data('LocalePath'));
      echo ' ';
      echo sprintf(T('For more help on localization check out the page <a href="%s">here</a>.'), 'http://vanillaforums.org/page/localization');
   ?>
</div>
<h3><?php echo T('Settings'); ?></h3>
<ul>
   <li>
      <?php echo sprintf(T('Locale info file settings.', '<p>When you generate the zip file you can set the information for the locale below.</p> <p>You can download a zip of the locale pack by clicking <a href="%s">here</a>.</p>'), Url("/dashboard/settings/localedeveloper/download")); ?>
   </li>
   <li>
      <?php
      echo $this->Form->Label('Locale Key (Folder)', 'Key'),
         $this->Form->TextBox('Key');
      ?>
   </li>
   <li>
      <?php
      echo $this->Form->Label('Locale Name', 'Name'),
         $this->Form->TextBox('Name');
      ?>
   </li>
   <li>
      <?php
      echo $this->Form->Label('_Locale', 'Locale'),
         $this->Form->TextBox('Locale', array('Class' => 'SmallInput'));
      ?>
   </li>
   <li>
      <?php
      echo $this->Form->CheckBox('CaptureDefinitions', 'Capture definitions throughout the site. You must visit the pages in the site in order for the definitions to be captured. The captured definitions will be put in the <code>captured.php</code> and <code>captured_admin.php</code>.');
      ?>
   </li>
</ul>
<?php echo $Form->Button('Save'); ?>
<h3><?php echo T('Tools'); ?></h3>
<ul>
   <li>
      <?php
         echo T('Copy locale pack.', 'Copy the definitions from a locale pack to the Locale Developer. The definitions will be put in the <code>copied.php</code> file.');
         echo $Form->Label('Choose a locale pack', 'LocalePackForCopy');
         echo $Form->DropDown('LocalePackForCopy', $this->Data('LocalePacks'));
         echo $Form->Button('Copy');
      ?>
   </li>
   <li>
      <?php
         echo T('Capture locale pack changes.', 'Capture the changes between one of your locale packs and the Locale Developer. It will be put in the <code>changes.php</code> file.');
         echo $Form->Label('Choose a locale pack', 'LocalePackForChanges');
         echo $Form->DropDown('LocalePackForChanges', $this->Data('LocalePacks'));
         echo $Form->Button('Generate', array('Name' => 'Form/GenerateChanges'));
      ?>
   </li>
   <li>
      <?php
         echo '<div>', T('Remove locale developer files.', 'Remove the locale deveoper files and reset your changes.'), '</div>';
         echo $Form->Button('Remove');
      ?>
   </li>
</ul>
<?php echo $Form->Close(); ?>