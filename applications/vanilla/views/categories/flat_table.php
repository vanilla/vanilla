<?php if (!defined('APPLICATION')) exit();
$userID = Gdn::session()->UserID;
$categoryID = $this->Category->CategoryID;

$tag = headingTag($this);
echo "<$tag class='H HomepageTitle'>$this->data('Title')</$tag>";
?>
    <div class="P PageDescription"><?php echo $this->description(); ?></div>
<?php
$this->fireEvent('AfterDescription');
$this->fireEvent('AfterPageTitle');

$categories = $this->data('CategoryTree');

writeCategoryTable($categories, 1);

?>
<div class="PageControls Bottom">
    <?php PagerModule::write(); ?>
</div>
