<?php /** @var TableSummaryModule $this */ ?>
<div class="table-summary-wrap">
    <div class="table-summary-title"><?php echo $this->getTitle(); ?></div>
    <table class="table-summary">
        <thead>
        <tr>
            <?php foreach($this->getColumns() as $column) { ?>
                <th <?php echo attribute(val('attributes', $column)); ?>><?php echo val('name', $column) ?></th>
            <?php } ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach($this->getRows() as $row) { ?>
            <tr <?php echo attribute(val('attributes', $row)); ?>>
                <?php foreach(val('cells', $row) as $key => $cell) { ?>
                    <td <?php echo attribute(val('attributes', $cell)); ?>><?php echo val('data', $cell); ?></td>
                <?php } ?>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
