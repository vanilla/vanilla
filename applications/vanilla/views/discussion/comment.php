<?php if (!defined('APPLICATION')) exit();

echo "Fuck";
echo json_encode($Comment);
writeComment($Comment, $this, $Session, $CurrentOffset);
