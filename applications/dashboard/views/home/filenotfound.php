<?php if (!defined('APPLICATION')) exit();
$tag = headingTag($this);
?>

<div class="Center SplashInfo">
    <?php echo "<$tag className='pageNotFoundTitle'>{$this->sanitize($this->data('Message', t('Page Not Found')))}</$tag>"; ?>

    <div id="Message"><?php echo $this->sanitize($this->data('Description', t('The page you were looking for could not be found.'))); ?></div>
</div>

<?php if (debug() && $this->data('Trace')): ?>
<h2>Trace</h2>
<pre stye="text-align"><?php echo htmlspecialchars($this->data('Trace')); ?></pre>
<?php endif; ?>
