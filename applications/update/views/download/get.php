<div class="Group Download DownloadGet">
   <h1><?php echo $this->UpdaterTitle; ?></h1>
   <div class="AboutDownload"><?php echo T("Downloading the latest version of Vanilla Forums. This could take a while depending on your internet connection."); ?></div>
   <div class="UpdateProgress"><?php echo json_encode($this->UpdaterTasks); ?></div>
</div>