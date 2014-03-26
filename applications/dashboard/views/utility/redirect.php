<?php if (!defined('APPLICATION')) exit();

if (strcasecmp($this->Data('Method'), 'POST') == 0):
   $ID = 'Form_'.time();
?>
   <form id="<?php echo $ID; ?>" action="<?php echo $this->Data('Url'); ?>" method="POST">
   </form>
   <script>
      document.getElementById("<?php echo $ID; ?>").submit();
   </script>
<?php else: ?>
   <script>
      window.location.replace("<?php echo $this->Data('Url'); ?>");
   </script>
<?php endif; ?>
