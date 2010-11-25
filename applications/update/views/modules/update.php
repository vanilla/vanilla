<?php if (!defined('APPLICATION')) exit(); ?>
<div id="UpdateModule" class="Box Active">
   <h4><?php echo GetValue('UpdateModuleTitle', $this, T('Update Progress')); ?></h4>
   <div class="TagDescription">
      <?php
         $Tag = $this->GetTag();
         if (!is_null($Tag))
            echo sprintf(T('<b>%s</b> started this update on <b>%s</b> from <b>%s</b>.'),
               GetValue('Who',$Tag,'?'), 
               GetValue('When',$Tag,'?'), 
               GetValue('Where',$Tag,'?')
            );
      ?>
   </div>
   <ul class="PanelUpdate">
      <?php
      $Data = $this->GetTasks();
      foreach ($Data as $TaskName => $Task) {
         $Completion = $Task['Completion'];
         if ($Completion === TRUE) 
            $Completion = 100;
         if ($Completion === FALSE)
            $Completion = 0;
         
         switch ($Completion) {
            case 0: $Completness = "NotStarted";
            break;
            case 100: $Completness = "Complete";
            break;
            default: $Completness = "Incomplete";
            break;
         }
         
         echo '<div class="UpdateTask">';
         echo '   <div class="TaskName">'.T($Task['Label']).'</div>';
         echo '   <div class="TaskCompletion"><img src="/applications/update/design/images/pixel.png" class="'.$Completness.'" /></div>';
         echo '</div>';
         if (sizeof($Task['Children'])) {
            echo '<div class="UpdateTaskChildren">';
            foreach ($Task['Children'] as $ChildTaskName => $ChildTask) {
               $Completion = $ChildTask['Completion'];
               if ($Completion === TRUE) 
                  $Completion = 100;
               if ($Completion === FALSE)
                  $Completion = 0;
               
               switch ($Completion) {
                  case 0: $Completness = "NotStarted";
                  break;
                  case 100: $Completness = "Complete";
                  break;
                  default: $Completness = "Incomplete";
                  break;
               }
            
               echo '<div class="UpdateTask">';
               echo '   <div class="TaskName">'.T($ChildTask['Label']).'</div>';
               echo '   <div class="TaskCompletion"><img src="/applications/update/design/images/pixel.png" class="'.$Completness.'" /></div>';
               echo '</div>';
            }
            echo '</div>';
         }
         
      }
      ?>
   </ul>
</div>