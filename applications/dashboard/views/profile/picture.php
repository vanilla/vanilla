<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
         
// Check that we have the necessary tools to allow image uploading
$AllowImages = Gdn_UploadImage::CanUploadImages();

// Is the photo hosted remotely?
$RemotePhoto = IsUrl($this->User->Photo, 0, 7);

// Define the current profile picture
$Picture = '';
if ($this->User->Photo != '') {
   if (IsUrl($this->User->Photo))
      $Picture = Img($this->User->Photo, array('class' => 'ProfilePhotoLarge'));
   else
      $Picture = Img(Gdn_Upload::Url(ChangeBasename($this->User->Photo, 'p%s')), array('class' => 'ProfilePhotoLarge'));
}

// Define the current thumbnail icon
$Thumbnail = $this->User->Photo;
if (!$Thumbnail && function_exists('UserPhotoDefaultUrl'))
   $Thumbnail = UserPhotoDefaultUrl($this->User);

if ($Thumbnail && !IsUrl($Thumbnail))
   $Thumbnail = Gdn_Upload::Url(ChangeBasename($Thumbnail, 'n%s'));

$Thumbnail = Img($Thumbnail, array('alt' => T('Thumbnail')));
?>
<div class="SmallPopup">
<h2 class="H"><?php echo $this->Data('Title'); ?></h2>
<?php
echo $this->Form->Open(array('enctype' => 'multipart/form-data'));
echo $this->Form->Errors();
?>
<ul>
   <?php if ($Picture != '') { ?>
   <li class="CurrentPicture">
      <table>
         <thead>
            <tr>
               <td><?php echo T('Picture'); ?></td>
               <td><?php echo T('Thumbnail'); ?></td>
            </tr>
         </thead>
         <tbody>
            <tr>
               <td><?php
               echo $Picture;
               if ($this->User->Photo != '' && $AllowImages && !$RemotePhoto) {
                  echo Wrap(Anchor(T('Remove Picture'), CombinePaths(array(UserUrl($this->User, '', 'removepicture'), $Session->TransientKey())), 'Button Danger PopConfirm'), 'p');
               ?>
               </td>
               <td><?php
               echo $Thumbnail;
               echo Wrap(Anchor(T('Edit Thumbnail'), UserUrl($this->User, '', 'thumbnail'), 'Button'), 'p');
               }
               ?>
               </td>
            </tr>
         </tbody>
      </table>
   </li>
   <?php } ?>
   <li>
      <p><?php echo T('Select an image on your computer (2mb max)'); ?></p>
      <?php echo $this->Form->Input('Picture', 'file'); ?>
   </li>
</ul>
<div class="Warning"><?php echo T('By uploading a file you certify that you have the right to distribute this picture and that it does not violate the Terms of Service.'); ?></div>
<?php echo $this->Form->Close('Upload', '', array('class' => 'Button Primary')); ?>
</div>