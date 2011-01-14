<div class="Group Verify VerifyExtract">
   <h1><?php echo $this->UpdaterTitle; ?></h1>
   <div class="About AboutVerifyExtract"><?php echo T("Extracting downloaded archive and verifying signature."); ?></div>
   <div class="UpdateProgress"><?php echo json_encode($this->UpdaterTasks); ?></div>
</div>