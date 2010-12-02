<div class="Backup BackupFiles">
   <h1><?php echo $this->BackupTitle; ?></h1>
   <div class="AboutBackup"><?php echo T("We're making a copy of all the files in your forum -- the software, all the uploads, everything -- and saving it somewhere safe. If things
   go wrong, you'll be able to restore your forum to the way it was before you began the update."); ?></div>
   <div class="UpdateProgress"><?php echo json_encode($this->BackupFilesTasks); ?></div>
</div>
