<?php use Vanilla\Theme\BoxThemeShim;

if (!defined('APPLICATION')) return;
require_once PATH_APPLICATIONS . '/vanilla/views/discussions/helper_functions.php';
$discussions = $this->data('discussions');
$title = $this->data('title');
?>
<?php if ($title): ?>
    <?php BoxThemeShim::startHeading(); ?>
    <h2 class="H"><?php echo htmlspecialchars($title); ?></h2>
    <?php BoxThemeShim::endHeading(); ?>
<?php endif; ?>

<?php if (count($discussions) > 0): ?>
    <ul class="DataList Discussions pageBox">
        <?php
            foreach ($discussions as $discussion) {
                writeDiscussion($discussion, Gdn::controller(), Gdn::session());
            }
        ?>
    </ul>
<?php else : ?>
    <div class="Empty"><?php echo t('No discussions were found.'); ?></div>
<? endif ?>
