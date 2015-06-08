<?php if (!defined('APPLICATION')) exit();
require_once $this->fetchViewLocation('helper_functions');
?>

<h1 class="H"><?php echo $this->data('Title'); ?></h1>
<div class="P PageDescription">
    <?php
    echo $this->data('_Description');
    ?>
</div>

<div class="PageControls Top">
    <?php
    PagerModule::write();
    ?>
</div>

<?php
if (c('Vanilla.Discussions.Layout') == 'table'):
    if (!function_exists('WriteDiscussionHeading'))
        require_once $this->fetchViewLocation('table_functions');
    ?>
    <div class="DataTableWrap">
        <table class="DataTable DiscussionsTable">
            <thead>
            <?php
            WriteDiscussionHeading();
            ?>
            </thead>
            <tbody>
            <?php
            foreach ($this->DiscussionData->result() as $Discussion) {
                WriteDiscussionRow($Discussion, $this, Gdn::session(), false);
            }
            ?>
            </tbody>
        </table>
    </div>
<?php
else:
    ?>
    <ul class="DataList Discussions">
        <?php include($this->fetchViewLocation('discussions')); ?>
    </ul>
<?php
endif;

?>
<div class="PageControls Bottom">
    <?php
    PagerModule::write();
    ?>
</div>
