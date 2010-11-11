<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();

?>
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
         echo $this->Form->Label('Banner Logo', 'Garden.Logo');
         $Logo = C('Garden.Logo');
         if ($Logo) {
            echo Wrap(
               Img($Logo),
               'div'
            );
            echo Wrap(Anchor(T('Remove Banner Logo'), '/dashboard/settings/removelogo/'.$Session->TransientKey(), 'SmallButton'), 'div', array('style' => 'padding: 10px 0;'));
            echo Wrap(
               T('Browse for a new banner logo if you would like to change it:'),
               'div',
               array('class' => 'Info')
            );
         } else {
            echo Wrap(
               T('The banner logo appears at the top of your forum.'),
               'div',
               array('class' => 'Info')
            );
         }
         
         echo $this->Form->Input('Logo', 'file');
      ?>
   </li>
</ul>
<?php echo $this->Form->Close('Save');
