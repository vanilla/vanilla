<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();

?>
<h1><?php echo T('Homepage'); ?></h1>
<div class="Info">
   <?php printf(T('Use the content at this url as your homepage.', 'Your "homepage" is what people see when they visit <strong>%s</strong>. We use "All Discussions" as your homepage by default, but you can change it to whatever you like. Here are some popular options:'), Url('/', TRUE)) ?>
</div>
<div class="HomeOptions">
   <?php
   // Only show the vanilla pages if Vanilla is enabled
   $ApplicationManager = new Gdn_ApplicationManager();
   $EnabledApplications = $ApplicationManager->EnabledVisibleApplications();
   if (array_key_exists('Vanilla', $EnabledApplications)) {
      echo Anchor(T('All Discussions'), 'discussions', array('class' => 'discussions'));
      echo Anchor(T('All Categories'), 'categories/all', array('class' => 'categoriesall'));
      echo Anchor(T('Categories &amp; Discussions'), 'categories/discussions', array('class' => 'categoriesdiscussions'));
   }
   echo Anchor(T('Activity'), 'activity', array('class' => 'activity'));
   ?>
</div>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <li>
      <?php
         echo $this->Form->Label('Enter the url to the page you would like to use as your homepage:', 'Target');
         echo Wrap(Url('/', TRUE), 'strong');
         echo $this->Form->TextBox('Target');
      ?>
   </li>
</ul>
<?php echo $this->Form->Close('Save');
