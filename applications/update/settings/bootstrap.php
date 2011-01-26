<?php if (!defined('APPLICATION')) exit();

$UpdatePath = C('Update.Local.Store');
if (!is_dir($UpdatePath))
   @mkdir($UpdatePath);

$ScratchPath = C('Update.Local.Scratch');
if (!is_dir($ScratchPath))
   @mkdir($ScratchPath);
   
$BackupPath = C('Update.Local.Backups');
if (!is_dir($ScratchPath))
   @mkdir($ScratchPath);

// Make sure we can write to the scratchpath
if (!is_dir($ScratchPath) || !is_writable($ScratchPath)) 
   SaveToConfig('Update.Local.Writable', FALSE);
else
   SaveToConfig('Update.Local.Writable', TRUE);