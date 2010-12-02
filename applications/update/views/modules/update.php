<?php if (!defined('APPLICATION')) exit(); ?>
<div id="UpdateModule">
   <ul class="PanelUpdate">
      <?php
      
      $NumTasks = sizeof($this->Tasks);
      $TaskNumber = 0;
      foreach ($this->Tasks as $TaskName => $Task) {
         $TaskNumber++;
         $IsActive = $Task['Active'];
         $IsDone = ($Task['Completion'] == 100) ? TRUE : FALSE;
         
         $TaskName = $Task['Name'];
         $TaskLabel = $Task['Label'];
         $Completion =  $Task['Completion'];
         $TaskText = T($TaskLabel);

         if ($IsActive) {
            $CompletionText = (int)$Completion.'%';
            $Complete = Wrap($CompletionText, 'span', array('class' => 'Completion'));
            $TaskText .= $Complete;
         }

         $LinkProps = array();
         if ($IsActive)
            array_push($LinkProps, 'Active');
         if ($IsDone)
            array_push($LinkProps, 'Done');
         $Link = Wrap($TaskText,'span',array('class' => implode(' ',$LinkProps)));
                  
         $LiProps = array();
         array_push($LiProps,ucfirst($TaskLabel)."Task");
         $IsLast = ($TaskNumber == $NumTasks) ? TRUE : FALSE;
         if ($IsLast)
            array_push($LiProps, 'Last');
            
         echo Wrap($Link,'li',array('class' => implode(' ',$LiProps)));
      }
      ?>
   </ul>
</div>