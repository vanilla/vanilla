<?php if (!defined('APPLICATION')) exit();

echo $this->Form->open(array('enctype' => 'multipart/form-data'));
?>
    <ul>
        <li>
            <?php
            echo $this->Form->label('Email', 'Email'),
            $this->Form->textBox('Email');
            ?>
        </li>
    </ul>
<?php echo $this->Form->close('OK');
