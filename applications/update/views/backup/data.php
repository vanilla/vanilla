<div class="Group Backup BackupData">
   <h1><?php echo $this->UpdaterTitle; ?></h1>
   <div class="About AboutBackup"><?php echo T("We're making a copy of your database. All your discussions, comments, users, and all your forum settings will be backed up for 
   later use in case you decide you want to roll back this update."); ?></div>
   <div class="UpdateProgress"><?php echo json_encode($this->UpdaterTasks); ?></div>
</div>