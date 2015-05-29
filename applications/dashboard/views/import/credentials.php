<?php if (!defined('APPLICATION')) exit();

echo $this->Form->Open(array('enctype' => 'multipart/form-data'));
?>
    <ul>
        <li>
            <?php
            echo $this->Form->Label('Email', 'Email'),
            $this->Form->TextBox('Email');
            ?>
        </li>
    </ul>
<?php echo $this->Form->Close('OK');
