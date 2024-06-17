<?php if (!defined("APPLICATION")) {
    exit();
} ?>
    <h1><?php echo $this->data("Title"); ?></h1>
<?php
include $this->fetchViewLocation("helper_functions");
$automationRules = "<p><b>" . t("Trigger") . ":</b></p>";
$automationRules .= "<p>" . $this->data("AutomationDispatchRecord.TriggerName") . "</p><br/>";
$automationRules .= "<p><b>" . t("Action") . ":</b></p>";
$automationRules .= "<p>" . $this->data("AutomationDispatchRecord.ActionName") . "</p><br/>";
$automationRules .= "<p><b>" . t("Trigger Type") . ":</b></p>";
$automationRules .= "<p>" . $this->data("AutomationDispatchRecord.DispatchType") . "</p><br/>";
$automationRules .= "<p><b>" . t("Dispatch Status") . ":</b></p>";
$automationRules .= "<p>" . $this->data("AutomationDispatchRecord.DispatchStatus") . "</p>";

helpAsset(t("Automation Rule"), $automationRules);

echo '<noscript><div class="Errors"><ul><li>', t("This page requires Javascript."), "</li></ul></div></noscript>";
echo $this->Form->open();
echo $this->Form->errors();
?>
    <div class="toolbar flex-wrap js-toolbar-sticky">
        <?php PagerModule::write(["Sender" => $this, "Limit" => 10, "View" => "pager-dashboard"]); ?>
    </div>

<div id="LogTable">
<div class="table-wrap">
        <table id="Log" class="table-data table-data-content js-tj">
            <thead>
            <tr>
                <th class="content-cell column-full content-cell-responsive" data-tj-main="true"><?php echo t(
                    "Record Content",
                    "Content"
                ); ?></th>
                <th class="UsernameCell column-lg username-cell-responsive"><?php echo t("Updated By"); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php
            $logModel = $this->data("LogModel");
            foreach ($this->data("Log") as $row):

                // Insert user block with date.
                $user = Gdn::userModel()->getID($row["InsertUserID"] ?? 0);
                $viewPersonalInfo = gdn::session()->checkPermission("Garden.PersonalInfo.View");
                $userBlock = new MediaItemModule(val("Name", $user), userUrl($user));
                $userBlock
                    ->setView("media-sm")
                    ->setImage(userPhotoUrl($user))
                    ->addMeta(Gdn_Format::dateFull($row["DateInserted"], "html"));
                ?>
                <tr id="<?php echo "LogID_{$row["LogID"]}"; ?>">
                    <!-- <td class="column-checkbox">&nbsp;</td> -->
                    <td class="content-cell">
                        <?php
                        if ($this->data("AutomationDispatchRecord.RecordType") === "User") {
                            echo '<div class="post-content js-collapsable" data-className="userContent">',
                                $logModel->formatRecord(["RecordName" => "Name", "RecordEmail" => "Email"], $row),
                                "</div>";
                        } else {
                            echo '<div class="post-content js-collapsable" data-className="userContent">',
                                $logModel->formatContent([
                                    "Data" => [
                                        "Name" => $row["RecordName"],
                                        "Body" => $row["RecordBody"],
                                        "Format" => $row["Format"],
                                    ],
                                    "RecordType" => "Discussion",
                                ]),
                                "</div>";
                        }
                        if (isset($row["logData"])) {
                            echo '<div class="Meta-Container">' . $row["logData"] . "</div>";
                        }
                        ?>
                    </td>
                    <td class="UsernameCell">
                        <?php echo $userBlock; ?>
                    </td>
                </tr>
            <?php
            endforeach;
            ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$this->addDefinition("ExpandText", t("more"));
$this->addDefinition("CollapseText", t("less"));
echo $this->Form->close();

