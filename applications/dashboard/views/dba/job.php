<?php if (!defined('APPLICATION')) exit; ?>
<style>
   .Complete {
      text-decoration: line-through;
   }
   
   .Error {
      color: red;
      text-decoration: line-through;
   }
   
   .Complete .TinyProgress {
      display: none !Important;
   }
   
   
</style>

<h1><?php echo $this->Data('Title'); ?></h1>

<div class="Info">
   <ol class="DBA-Jobs">
   <?php 
   $i = 0;
   foreach ($this->Data('Jobs') as $Name => $Job): 
   ?>
      <li id="<?php echo "Job_$i"; ?>" class="DBA-Job" rel="<?php echo htmlspecialchars($Job); ?>">
         <?php
         echo htmlspecialchars($Name).' ';
         echo '<span class="Count" style="display: none">0</span> ';
         echo '<span class="TinyProgress"></span>';
         ?>
      </li>
   <?php 
      $i++;
   endforeach; 
   ?>
   </ol>
</div>