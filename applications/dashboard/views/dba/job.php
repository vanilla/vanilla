<?php if (!defined('APPLICATION')) exit; ?>
<style>
   .Complete {
      text-decoration: line-through;
   }
   
   .Error {
      color: red;
      text-decoration: line-through;
   }
</style>

<h1><?php echo $this->Data('Title'); ?></h1>


<div class="Info">
   <?php
   echo $this->Form->Open();
   echo $this->Form->Errors();
   ?>
   
   <ol class="DBA-Jobs">
   <?php 
   $i = 0;
   foreach ($this->Data('Jobs') as $Name => $Job): 
   ?>
      <li id="<?php echo "Job_$i"; ?>" class="DBA-Job" rel="<?php echo htmlspecialchars($Job); ?>">
         <?php
         if (!$this->Form->IsPostBack()) {
            $this->Form->SetValue("Job_$i", TRUE);
         }
         
         echo $this->Form->CheckBox("Job_$i", htmlspecialchars($Name));
         
         echo ' <span class="Count" style="display: none">0</span> ';
         ?>
      </li>
   <?php 
      $i++;
   endforeach; 
   ?>
   </ol>
   
   <?php
   if ($this->Form->IsPostBack()) {
      Gdn::Controller()->AddDefinition('Started', 1);
   }
   
   echo $this->Form->Button('Start');
   echo $this->Form->Close();
   ?>
</div>