<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
?>
<h1><?php echo T('Touch Icon'); ?></h1>
<?php
echo $this->Form->Open(array('enctype' => 'multipart/form-data'));
echo $this->Form->Errors();
?>
<ul>
   <li>
      <?php
         echo $this->Form->Label('Touch Icon', 'TouchIcon');
         echo Wrap(
               T('TouchIconInfo', 'The touch icon appears when you bookmark a website on the homescreen of an Apple device.
                  These are usually 57x57 or 114x114 pixels. Apple adds rounded corners and lighting effect automatically.'),
               'div',
               array('class' => 'Info')
            );
            
         echo Wrap(
            Img('/apple-touch-icon.png'),
            'div'
         );
         echo Wrap(
            T('TouchIconEdit', 'Browse for a new touch icon to change it:'),
            'div',
            array('class' => 'Info')
         );
         
         echo $this->Form->Input('TouchIcon', 'file');
      ?>
   </li>
</ul>
<?php echo $this->Form->Close('Save');
