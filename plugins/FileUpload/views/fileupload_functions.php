<?php

function MediaThumbnail($Media, $Data = FALSE) {
      $Media = (array)$Media;
   
      if (GetValue('ThumbPath', $Media)) {
         $Src = Gdn_Upload::Url(ltrim(GetValue('ThumbPath', $Media), '/'));
      } else {
         $Width = GetValue('ImageWidth', $Media);
         $Height = GetValue('ImageHeight', $Media);

         if (!$Width || !$Height) {
            $Height = MediaModel::ThumbnailHeight();
            if (!$Height)
               $Height = 100;
               SetValue('ThumbHeight', $Media, $Height);
            
            return DefaultMediaThumbnail($Media);
         }

         $RequiresThumbnail = FALSE;
         if (MediaModel::ThumbnailHeight() && $Height > MediaModel::ThumbnailHeight())
            $RequiresThumbnail = TRUE;
         elseif (MediaModel::ThumbnailWidth() && $Width > MediaModel::ThumbnailWidth())
            $RequiresThumbnail = TRUE;

         $Path = ltrim(GetValue('Path', $Media), '/');
         if ($RequiresThumbnail) {
            $Src = Url('/utility/thumbnail/'.GetValue('MediaID', $Media, 'x').'/'.$Path, TRUE);
         } else {
            $Src = Gdn_Upload::Url($Path);
         }
      }
      if ($Data)
         $Result = array('src' => $Src, 'width' => GetValue('ThumbWidth', $Media), 'height' => GetValue('ThumbHeight', $Media));
      else
         $Result = Img($Src, array('class' => 'ImageThumbnail', 'width' => GetValue('ThumbWidth', $Media), 'height' => GetValue('ThumbHeight', $Media)));
      
      return $Result;
   
}

function DefaultMediaThumbnail($Media) {
  $Result = '<span class="Thumb-Default">'.
   '<span class="Thumb-Extension">'.pathinfo($Media['Name'], PATHINFO_EXTENSION).'</span>'.
   Img('/plugins/FileUpload/images/file.png', array('class' => 'ImageThumbnail', 'height' => GetValue('ThumbHeight', $Media))).
   '</span>';
   
   return $Result;
}
