<?php if (!defined('APPLICATION')) exit(); ?>

<div class="Center SplashInfo">
    <h1><?php echo $this->data('Message', t('Page Not Found')); ?></h1>

    <div id="Message"><?php echo $this->sanitize($this->data('Description', t('The page you were looking for could not be found.'))); ?></div>
</div>

<?php if (debug() && $this->data('Trace')): ?>
<h2>Trace</h2>
<pre stye="text-align"><?php echo htmlspecialchars($this->data('Trace')); ?></pre>
<?php endif; ?>
