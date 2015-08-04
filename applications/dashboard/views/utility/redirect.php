<?php if (!defined('APPLICATION')) exit();

if (strcasecmp($this->data('Method'), 'POST') == 0):
    $ID = 'Form_'.time();
    ?>
    <form id="<?php echo $ID; ?>" action="<?php echo $this->data('Url'); ?>" method="POST">
    </form>
    <script>
        document.getElementById("<?php echo $ID; ?>").submit();
    </script>
<?php else: ?>
    <script>
        window.location.replace("<?php echo $this->data('Url'); ?>");
    </script>
<?php endif; ?>
