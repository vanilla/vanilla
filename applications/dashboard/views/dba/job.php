<?php if (!defined('APPLICATION')) exit; ?>
<style>
    .Complete {
        text-decoration: line-through;
    }

    .Error {
        color: red;
        text-decoration: line-through;
    }

    .DBA-Job.isWorking .TinyProgress
    .DBA-Job.isWorking .DBA-percentage,
    {
        display: block;
    }

    .DBA-percentage {
        min-width: 39px;
        display: none;
        text-align: center;
        padding-top: 0;
        padding-bottom: 0;
    }

</style>


<?php

echo heading($this->data('Title'));
echo $this->Form->open();
echo $this->Form->errors();
?>
<ul>

<?php
        $i = 0;
        foreach ($this->data('Jobs') as $Name => $Job):
            ?>
            <li id="<?php echo "Job_$i"; ?>" class="form-group DBA-Job" rel="<?php echo htmlspecialchars($Job); ?>">
                <?php
                if (!$this->Form->isPostBack()) {
                    $this->Form->setValue("Job_$i", true);
                }
                ?>

                <div class="label-wrap-wide label-flex">
                    <div class="flex-grow">
                        <?php
                        echo $this->Form->checkBox("Job_$i");
                        ?>
                        <div class="js-onComplete"">
                            <?php echo htmlspecialchars($Name); ?>
                        </div>
                    </div>
                    <span class="TinyProgress" style="display: none;">&nbsp;</span>
                    <span class="DBA-percentage Count">0%</span>
                </div>
            </li>
            <?php
            $i++;
        endforeach;

        if ($this->Form->isPostBack()) {
            Gdn::controller()->addDefinition('Started', 1);
        }

        echo '<div class="form-footer">';
        echo $this->Form->button('Start');
        echo '</div>';

        echo $this->Form->close();
    ?>
</ul>
