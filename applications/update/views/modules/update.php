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

         $LinkProps = array();
         if ($IsActive)
            array_push($LinkProps, 'Active');
         if ($IsDone)
            array_push($LinkProps, 'Done');
         $Link = Wrap(T($TaskLabel),'span',array('class' => implode(' ',$LinkProps)));
         
         $LiProps = array();
         $IsLast = ($TaskNumber == $NumTasks) ? TRUE : FALSE;
         if ($IsLast)
            array_push($LiProps, 'Last');
            
         echo Wrap($Link,'li',array('class' => implode(' ',$LiProps)));
      }
      ?>
   </ul>
</div>