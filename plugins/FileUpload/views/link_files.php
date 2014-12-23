<div class="Attachments">
   <div class="AttachFileContainer">
      <?php
         $CanDownload = $this->Data('CanDownload');
         foreach ($this->Data('CommentMediaList') as $Media) {
            $IsOwner = (Gdn::Session()->IsValid() && (Gdn::Session()->UserID == GetValue('InsertUserID',$Media,NULL)));
            $this->EventArguments['CanDownload'] =& $CanDownload;
            $this->EventArguments['Media'] =& $Media;
            $this->FireEvent('BeforeFile');

      ?>
            <div class="Attachment">
               <div class="FilePreview">
                  <?php
                  $Path = GetValue('Path', $Media);
                  $Img = '';
                  
                  if ($CanDownload) {
                     $DownloadUrl = Url(MediaModel::Url($Media));
                     $Img = '<a href="'.$DownloadUrl.'">';
                  }

                  $ThumbnailUrl = MediaModel::ThumbnailUrl($Media);
                  $Img .= MediaThumbnail($Media);
                  if ($CanDownload)
                     $Img .= '</a>';
                     
                  echo $Img;
               ?></div>
               <div class="FileHover">
                  <?php echo $Img; ?>
                  <div class="FileMeta">
                     <?php
                     echo '<div class="FileName">';
   
                     if (isset($DownloadUrl)) {
                        echo '<a href="'.$DownloadUrl.'">'.htmlspecialchars($Media->Name).'</a>';
                     } else {
                        echo htmlspecialchars($Media->Name);
                     }
   
   
                     echo '</div>';
   
                     echo '<div class="FileAttributes">';
                     if ($Media->ImageWidth && $Media->ImageHeight) {
                        echo ' <span class="FileSize">'.$Media->ImageWidth.'&#160;x&#160;'.$Media->ImageHeight.'</span> - ';
                     }
   
                     echo ' <span class="FileSize">', Gdn_Format::Bytes($Media->Size, 0), '</span>';
                     echo '</div>';
                     
                     $Actions = '';
                     if (StringBeginsWith($this->ControllerName, 'post', TRUE))
                        $Actions = ConcatSep(' | ', $Actions, '<a class="InsertImage" href="'.Url(MediaModel::Url($Path)).'">'.T('Insert Image').'</a>');
                     
                     if (GetValue('ForeignTable', $Media) == 'discussion')
                        $PermissionName = "Vanilla.Discussions.Edit";
                     else
                        $PermissionName = "Vanilla.Comments.Edit";
   
                     if ($IsOwner || Gdn::Session()->CheckPermission($PermissionName, TRUE, 'Category', $this->Data('Discussion.PermissionCategoryID')))
                        $Actions = ConcatSep(' | ', $Actions, '<a class="DeleteFile" href="'.Url("/plugin/fileupload/delete/{$Media->MediaID}").'"><span>'.T('Delete').'</span></a>');
   
                     if ($Actions)
                        echo '<div>', $Actions, '</div>';
                     ?>
                  </div>
               </div>
            </div>
      <?php
         }
      ?>
   </div>
</div>