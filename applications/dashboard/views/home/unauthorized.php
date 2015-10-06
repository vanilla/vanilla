<?php if (!defined('APPLICATION')) exit(); ?>

<div class="SplashInfo">
    <h1><?php echo t('PermissionErrorTitle', 'Permission Problem'); ?></h1>

    <p><?php echo $this->sanitize($this->data('Message', t('PermissionErrorMessage', "You don't have permission to do that."))); ?></p>
</div>

<?php if (debug() && $this->data('Trace')): ?>
<h2>Trace</h2>
<pre stye="text-align"><?php echo htmlspecialchars($this->data('Trace')); ?></pre>
<?php endif; ?>
