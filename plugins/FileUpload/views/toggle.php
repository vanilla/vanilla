<div class="FileUploadBlock Slice" rel="/plugin/fileupload/toggle">
   <?php
      echo $this->Form->Open();
   
      $FileUploadStatus = $this->Data['FileUploadStatus'];
      $NewUploadStatus = !$this->Data['FileUploadStatus'];
      
      function ParseBoolStatus($BoolStatus) {
         return ($BoolStatus) ? 'ON' : 'OFF';
      }
   
      $FileUploadStatus = ParseBoolStatus($FileUploadStatus);
      $NewUploadStatus = ParseBoolStatus($NewUploadStatus);
      
      echo $this->Form->Hidden('FileUploadStatus', array('value' => $NewUploadStatus));
   ?>
   <ul>
      <li><?php echo $this->Form->Label("FileUpload is currently {$FileUploadStatus}"); ?></li>
   </ul>
   <?php 
      echo $this->Form->Close("Turn {$NewUploadStatus}",'',array(
         'class' => 'SliceSubmit Button'
      )); 
   ?>
</div>