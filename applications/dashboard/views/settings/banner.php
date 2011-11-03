<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
?>
<div class="Help Aside">
   <?php
   echo '<h2>', T('Need More Help?'), '</h2>';
   echo '<ul>';
   echo Wrap(Anchor(T("Video tutorial on managing appearance"), 'settings/tutorials/appearance'), 'li');
   echo '</ul>';
   ?>
</div>
<h1><?php echo T('Banner'); ?></h1>
<?php
echo $this->Form->Open(array('enctype' => 'multipart/form-data'));
echo $this->Form->Errors();
?>
<ul>
   <li>
      <?php
         echo $this->Form->Label('Banner Title', 'Garden.Title');
         echo Wrap(
               T('The banner title appears on the top-left of every page. If a banner logo is uploaded, it will replace the banner title on user-facing forum pages.'),
               'div',
               array('class' => 'Info')
            );
         echo $this->Form->TextBox('Garden.Title');
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Banner Logo', 'Logo');
         $Logo = $this->Data('Logo');
         if ($Logo) {
            echo Wrap(
               Img(Gdn_Upload::Url($Logo)),
               'div'
            );
            echo Wrap(Anchor(T('Remove Banner Logo'), '/dashboard/settings/removelogo/'.$Session->TransientKey(), 'SmallButton'), 'div', array('style' => 'padding: 10px 0;'));
            echo Wrap(
               T('LogoBrowse', 'Browse for a new banner logo if you would like to change it:'),
               'div',
               array('class' => 'Info')
            );
         } else {
            echo Wrap(
               T('LogoDescription', 'The banner logo appears at the top of your forum.'),
               'div',
               array('class' => 'Info')
            );
         }
         
         echo $this->Form->Input('Logo', 'file');
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Favicon', 'Favicon');
         $Favicon = $this->Data('Favicon');
         if ($Favicon) {
            echo Wrap(
               Img(Gdn_Upload::Url($Favicon)),
               'div'
            );
            echo Wrap(Anchor(T('Remove Favicon'), '/dashboard/settings/removefavicon/'.$Session->TransientKey(), 'SmallButton'), 'div', array('style' => 'padding: 10px 0;'));
            echo Wrap(
               T('FaviconBrowse', 'Browse for a new favicon if you would like to change it:'),
               'div',
               array('class' => 'Info')
            );
         } else {
            echo Wrap(
               T('FaviconDescription', "The shortcut icon that shows up in your browser's bookmark menu (16x16 px)."),
               'div',
               array('class' => 'Info')
            );
         }
         echo $this->Form->Input('Favicon', 'file');
      ?>
   </li>
</ul>
<?php echo $this->Form->Close('Save');
