<?php if (!defined("APPLICATION")) {
    exit();
} ?>
    <h1><?php echo $this->data("Title"); ?></h1>
<?php
helpAsset(
    $this->data("Title"),
    t("Every edit or deletion is recorded here. Use &lsquo;Restore&rsquo; to undo any change.")
);
$links = "<ul>";
$links .= "<li>" . anchor(t("Discussions"), "dashboard/log/edits/discussion") . "</li>";
$links .= "<li>" . anchor(t("Comments"), "dashboard/log/edits/comment") . "</li>";
$links .= "<li>" . anchor(t("Users"), "dashboard/log/edits/user") . "</li>";
if (gdn::session()->checkPermission("site.manage")) {
    $links .= "<li>" . anchor(t("Configurations"), "dashboard/log/edits/configuration") . "</li>";
    $links .= "<li>" . anchor(t("Spoofs"), "dashboard/log/edits/spoof") . "</li>";
}
$links .= "</ul>";
helpAsset("Filters", $links);

echo '<noscript><div class="Errors"><ul><li>', t("This page requires Javascript."), "</li></ul></div></noscript>";
echo $this->Form->open();
echo $this->Form->errors();
?>
    <div class="toolbar flex-wrap js-toolbar-sticky">
        <div class="toolbar-buttons">
        <?php
        echo anchor(t("Restore"), "#", ["class" => "RestoreButton btn btn-primary"]);
        echo anchor(t("Delete Forever"), "#", ["class" => "DeleteButton btn btn-primary"]);
        ?>
        </div>
        <?php PagerModule::write(["Sender" => $this, "Limit" => 10, "View" => "pager-dashboard"]); ?>
    </div>
<?php
echo '<div id="LogTable">';
include __DIR__ . "/table.php";
echo "</div>";
?>
<?php
$this->addDefinition("ExpandText", t("more"));
$this->addDefinition("CollapseText", t("less"));
echo $this->Form->close();

