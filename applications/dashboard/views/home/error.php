<?php if (!defined('APPLICATION')) exit(); ?>
<div class="SplashInfo">
    <div class="Center">
        <h1><?php echo $this->data('Title', t('Whoops!')); ?></h1>

        <div id="Message">
            <?php
            echo Gdn_Format::markdown($this->sanitize($this->data('Exception', 'No error message supplied.')));
            ?>
        </div>
    </div>
    <?php if (debug() && $this->data('Trace')): ?>
        <h2>Error</h2>
        <?php echo $this->data('Code').' '.htmlspecialchars(Gdn_Controller::getStatusMessage($this->data('Code'))); ?>
        <h2>Trace</h2>
        <pre stye="text-align"><?php echo htmlspecialchars($this->data('Trace')); ?></pre>
    <?php endif; ?>
    <!-- Code: <?php $this->data('Code', 400); ?> -->
</div>
