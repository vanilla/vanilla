<?php if (!defined('APPLICATION')) exit();
$this->FireEvent('BeforeCommentPreviewFormat');
$this->Comment->Body = Gdn_Format::To($this->Comment->Body, GetValue('Format', $this->Comment, C('Garden.InputFormatter')));
$this->FireEvent('AfterCommentPreviewFormat');
?>
<div class="Preview">
   <div class="Message"><?php echo $this->Comment->Body; ?></div>
</div>