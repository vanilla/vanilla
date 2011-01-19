<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
?>
<div class="Help Aside">
   <?php
   echo '<h2>', T('Need More Help?'), '</h2>';
   echo '<ul>';
   echo '<li>', Anchor(T('Configuring Vanilla\'s Homepage'), 'http://vanillaforums.org/docs/homepage'), '</li>';
   echo '<li>', sprintf(T('Change the look of All Categories', 'You can change the look of the <b>All Categories</b> page <a href="%s">here</a>.'), Url('/vanilla/settings/managecategories')), '</li>';
   echo '<li>', Anchor(T('Changing the Discussions Menu Link'), 'http://vanillaforums.org/docs/homepage#discussionslink'), '</li>';
   echo '</ul>';
   ?>
</div>
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
