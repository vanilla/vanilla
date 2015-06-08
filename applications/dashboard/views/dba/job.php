<?php if (!defined('APPLICATION')) exit; ?>
<style>
    .Complete {
        text-decoration: line-through;
    }

    .Error {
        color: red;
        text-decoration: line-through;
    }
</style>

<h1><?php echo $this->data('Title'); ?></h1>


<div class="Info">
    <?php
    echo $this->Form->open();
    echo $this->Form->errors();
    ?>

    <ol class="DBA-Jobs">
        <?php
        $i = 0;
        foreach ($this->data('Jobs') as $Name => $Job):
            ?>
            <li id="<?php echo "Job_$i"; ?>" class="DBA-Job" rel="<?php echo htmlspecialchars($Job); ?>">
                <?php
                if (!$this->Form->isPostBack()) {
                    $this->Form->setValue("Job_$i", true);
                }

                echo $this->Form->CheckBox("Job_$i", htmlspecialchars($Name));

                echo ' <span class="Count" style="display: none">0</span> ';
                ?>
            </li>
            <?php
            $i++;
        endforeach;
        ?>
    </ol>

    <?php
    if ($this->Form->isPostBack()) {
        Gdn::controller()->addDefinition('Started', 1);
    }

    echo $this->Form->button('Start');
    echo $this->Form->close();
    ?>
</div>
