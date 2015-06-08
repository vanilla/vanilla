<?php if (!defined('APPLICATION')) exit();
$this->fireEvent('BeforeCommentPreviewFormat');
$this->Comment->Body = Gdn_Format::to($this->Comment->Body, val('Format', $this->Comment, c('Garden.InputFormatter')));
$this->fireEvent('AfterCommentPreviewFormat');
?>
<div class="Preview">
    <div class="Message"><?php echo $this->Comment->Body; ?></div>
</div>
