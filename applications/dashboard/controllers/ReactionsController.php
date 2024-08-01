<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * Reactions controller.
 *
 * @since 1.0.0
 */
class ReactionsController extends DashboardController
{
    /* @var Gdn_Form */
    public $Form;

    /**
     *
     */
    public function initialize()
    {
        parent::initialize();
        $this->Form = new Gdn_Form();
        $this->Application = "dashboard";
    }

    /**
     * List reactions.
     */
    public function index()
    {
        $this->permission("Garden.Community.Manage");
        $this->title(t("Reaction Types"));
        $this->setHighlightRoute();

        // Grab all of the reaction types.
        $ReactionTypes = ReactionModel::getReactionTypes();
        $this->setData("ReactionTypes", $ReactionTypes);

        Gdn_Theme::section("Settings");
        include_once $this->fetchViewLocation("settings_functions");
        $this->render("reactiontypes");
    }

    /**
     * Get a reaction.
     *
     * @param string $urlCode
     * @throws
     */
    public function get($urlCode)
    {
        $this->permission("Garden.Community.Manage");

        $reaction = ReactionModel::reactionTypes($urlCode);
        if (!$reaction) {
            throw notFoundException("reaction");
        }

        $this->setData("Reaction", $reaction);
        $this->render("blank", "utility", "dashboard");
    }

    /**
     * Add a reaction.
     *
     * Parameters:
     *  UrlCode
     *  Name
     *  Description
     *  Class
     *  Points
     */
    public function add()
    {
        $this->permission("Garden.Community.Manage");
        $this->title("Add Reaction");
        $this->addSideMenu("reactions");

        $reactionModel = new ReactionModel();
        if ($this->Form->authenticatedPostBack()) {
            $reaction = $this->Form->formValues();
            $definedReaction = $reactionModel->defineReactionType($reaction);

            if ($definedReaction) {
                $this->setData("Reaction", $reaction);
                if ($this->deliveryType() != DELIVERY_TYPE_DATA) {
                    $this->informMessage(formatString(t("New reaction created"), $reaction));
                    redirectTo("/reactions");
                }
            }
        }

        $this->render("addedit");
    }

    /**
     *
     *
     * @param $Type
     * @param $ID
     * @param $Reaction
     * @param $UserID
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function undo($Type, $ID, $Reaction, $UserID)
    {
        $this->permission(["Garden.Moderation.Manage"], false);

        if (!$this->Form->authenticatedPostBack(true)) {
            throw forbiddenException("GET");
        }

        $ReactionModel = new ReactionModel();
        $ReactionModel->react($Type, $ID, "Undo-" . $Reaction, $UserID);

        $this->jsonTarget("!parent", "", "SlideUp");

        include_once $this->fetchViewLocation("reaction_functions");
        $this->render("Blank", "Utility", "Dashboard");
    }

    /**
     * List users who reacted.
     *
     * @param $type
     * @param $iD
     * @param $reaction
     * @param null $page
     * @throws Exception
     */
    public function users($type, $id, $reaction, $page = null)
    {
        $this->permission("Garden.Reactions.View");

        $reactionModel = new ReactionModel();
        $reactionType = $reactionModel::reactionTypes($reaction);

        if (
            val("Class", $reactionType) === "Flag" &&
            !Gdn::config("Vanilla.Reactions.FlagCount.DisplayToUsers", true) &&
            !checkPermission("Garden.Moderation.Manage")
        ) {
            throw permissionException();
        }

        [$offset, $limit] = offsetLimit($page, 10);
        $this->setData("Users", $reactionModel->getUsers($type, $id, $reaction, $offset, $limit));
        $this->render();
    }

    /**
     * Edit a reaction.
     *
     * Parameters:
     *  UrlCode
     *  Name
     *  Description
     *  Class
     *  Points
     *
     * @param string $urlCode
     */
    public function edit($urlCode)
    {
        $this->permission("Garden.Community.Manage");
        $this->title("Edit Reaction");
        $this->setHighlightRoute("reactions");

        $reaction = ReactionModel::reactionTypes($urlCode);
        if (!$reaction) {
            throw notFoundException("reaction");
        }

        $this->setData("Reaction", $reaction);

        $reactionModel = new ReactionModel();
        $this->Form->setModel($reactionModel);
        $this->Form->setData($reaction);

        if ($this->Form->authenticatedPostBack()) {
            $this->Form->setFormValue("UrlCode", $urlCode);
            $this->Form->validateRule(
                "LogThreshold",
                "validateInteger",
                sprintf(t("ValidateInteger"), "'" . $this->Form->getFormValue("LogThreshold") . "'")
            );
            $this->Form->validateRule(
                "RemoveThreshold",
                "validateInteger",
                sprintf(t("ValidateInteger"), "'" . $this->Form->getFormValue("RemoveThreshold") . "'")
            );
            $this->Form->validateRule("RemovalThreshold", "validateInteger", t("ValidateInteger"));
            $formPostValues = $this->Form->formValues();

            // This is an edit. Let's flag the reaction as custom if the above fields are modified.
            // Otherwise it would be reset on utility/update
            $diff = false;
            $toCheckForDiff = ["Name", "Description", "Class", "Points"];
            foreach ($toCheckForDiff as $field) {
                if ($reaction[$field] !== val($field, $formPostValues)) {
                    $diff = true;
                    break;
                }
            }

            if ($diff) {
                $this->Form->setFormValue("Custom", 1);
            }

            // Save image and populate the Photo field with the saved image filename.
            if (isset($formPostValues["Photo"]) && $formPostValues["Photo"] !== "") {
                $image = $this->savePhoto($urlCode);
                $this->Form->setFormValue("Photo", $image);
            }

            if ($this->Form->save() !== false) {
                $reaction = ReactionModel::reactionTypes($urlCode);
                $this->setData("Reaction", $reaction);

                $this->informMessage(t("Reaction saved."));
                if ($this->_DeliveryType !== DELIVERY_TYPE_ALL) {
                    $this->render("blank", "utility", "dashboard");
                } else {
                    redirectTo("/reactions");
                }
            }
        }

        $this->render("addedit");
    }

    /**
     * Save a photo and return the temp name.
     *
     * @param string $urlCode The reaction Url code.
     * @return string
     * @throws \Garden\Container\ContainerException Container Exception.
     * @throws \Garden\Container\NotFoundException Not Found Exception.
     */
    public function savePhoto(string $urlCode): string
    {
        // Upload image
        $uploadImage = Gdn::getContainer()->get(Gdn_UploadSvg::class);

        // Validate the upload
        $tmpImage = $uploadImage->validateUpload("Photo", true);

        if ($tmpImage) {
            // Generate the target image name.
            $targetImage = $uploadImage->generateTargetName(PATH_UPLOADS . "/reactions", "", true);
            $basename = pathinfo($targetImage, PATHINFO_BASENAME);

            // Delete any previously uploaded image.
            $reaction = ReactionModel::reactionTypes($urlCode);
            if ($reaction && isset($reaction["Photo"])) {
                $uploadImage->delete($reaction["Photo"]);
            }

            // Save the uploaded image
            $props = $uploadImage->saveAs($tmpImage, "reactions/$basename");
            return sprintf($props["SaveFormat"], "reactions/$basename");
        }
    }

    /**
     * Remove a reaction photo.
     *
     * @param string $reactionUrl The reaction code.
     * @throws \Garden\Web\Exception\ResponseException Response Exception.
     */
    public function removePhoto(string $reactionUrl): void
    {
        $this->permission("Garden.Community.Manage");
        $reactionModel = new ReactionModel();
        $reaction = ReactionModel::reactionTypes($reactionUrl);
        $photo = $reaction["Photo"];

        // Only attempt to delete a physical file, not a URL.
        if (!isUrl($photo)) {
            $reactionPhoto = changeBasename($photo, "p%s");
            $upload = new Gdn_Upload();
            $upload->delete($reactionPhoto);
        }

        $reaction["Photo"] = null;
        $reactionModel->save($reaction);
        redirectTo("/reactions");
    }

    /**
     * Generate the reaction logs.
     *
     * @param $type
     * @param $iD
     * @throws Exception
     */
    public function logged($type, $iD)
    {
        $this->permission(["Garden.Moderation.Manage", "Moderation.Reactions.Edit"], false);
        $type = ucfirst($type);

        $reactionModel = new ReactionModel();
        [$row, $model] = $reactionModel->getRow($type, $iD);
        if (!$row) {
            throw notFoundException(ucfirst($type));
        }

        $reactionModel->joinUserTags($row, $type);
        touchValue("UserTags", $row, []);
        Gdn::userModel()->joinUsers($row["UserTags"], ["UserID"]);

        $this->Data = $row;
        $this->setData("RecordType", $type);
        $this->setData("RecordID", $iD);

        $this->render("log");
    }

    /**
     *
     *
     * @param bool $day
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function recalculateRecordCache($day = false)
    {
        $this->permission("Garden.Settings.Manage");

        if (!$this->Request->isAuthenticatedPostBack(true)) {
            throw forbiddenException("GET");
        }

        $reactionModel = new ReactionModel();
        $count = $reactionModel->recalculateRecordCache($day);
        $this->setData("Count", $count);
        $this->setData("Success", true);
        $this->render();
    }

    /**
     * Toggle a given reaction on or off.
     *
     * @param string $UrlCode
     * @param boolean $Active
     */
    public function toggle($UrlCode, $Active)
    {
        $this->permission("Garden.Community.Manage");

        if (!$this->Form->authenticatedPostBack(true)) {
            throw permissionException("PostBack");
        }

        $ReactionType = ReactionModel::reactionTypes($UrlCode);
        if (!$ReactionType) {
            throw notFoundException("Reaction Type");
        }

        $ReactionModel = new ReactionModel();
        $ReactionType["Active"] = $Active;
        $ReactionModel->update(["Active" => $Active], ["UrlCode" => $UrlCode]);
        Gdn::cache()->remove("ReactionTypes");

        $this->setData("Reaction", $ReactionType);

        if ($this->deliveryType() != DELIVERY_TYPE_DATA) {
            // Send back the new button.
            include_once $this->fetchViewLocation("settings_functions");
            $this->deliveryMethod(DELIVERY_METHOD_JSON);
            $this->jsonTarget(
                "#ReactionType_{$ReactionType["UrlCode"]} #reactions-toggle",
                activateButton($ReactionType),
                "ReplaceWith"
            );
            if ($Active == "1") {
                $this->informMessage(sprintf(t('Enabled %1$s'), val("Name", $ReactionType)));
            } else {
                $this->informMessage(sprintf(t('Disabled %1$s'), val("Name", $ReactionType)));
            }
        }

        $this->render("blank", "utility", "dashboard");
    }

    /**
     * Settings page.
     */
    public function settings()
    {
        $this->permission("Garden.Settings.Manage");

        $cf = new ConfigurationModule($this);

        if ($this->Form->authenticatedPostBack()) {
            // Grab the data from the form.
            $showUserReactions = $this->Form->getFormValue("Vanilla.Reactions.ShowUserReactions");
            $currentValue = Gdn::config("Vanilla.Reactions.ShowUserReactions");
            if (
                ($currentValue == "off" && $showUserReactions != "off") ||
                ($currentValue != "off" && $showUserReactions == "off")
            ) {
                ReactionModel::updateReactionViewRole(
                    $currentValue == "off" && $showUserReactions != "off" ? "add" : "remove"
                );
            }
        }
        $cf->initialize([
            "Vanilla.Reactions.ShowUserReactions" => [
                "LabelCode" => "Show Who Reacted to Posts",
                "Control" => "RadioList",
                "Items" => ["popup" => "In a popup", "avatars" => "As avatars", "off" => "Don't show"],
                "Default" => ReactionModel::RECORD_REACTIONS_DEFAULT,
            ],
            "Vanilla.Reactions.BestOfStyle" => [
                "LabelCode" => "Best of Style",
                "Control" => "RadioList",
                "Items" => ["Tiles" => "Tiles", "List" => "List"],
                "Default" => "Tiles",
            ],
            "Vanilla.Reactions.DefaultOrderBy" => [
                "LabelCode" => "Order Comments By",
                "Control" => "RadioList",
                "Items" => ["DateInserted" => "Date", "Score" => "Score"],
                "Default" => "DateInserted",
                "Description" =>
                    "You can order your comments based on reactions. We recommend ordering the comments by date.",
            ],
            "Vanilla.Reactions.DefaultEmbedOrderBy" => [
                "LabelCode" => "Order Embedded Comments By",
                "Control" => "RadioList",
                "Items" => ["DateInserted" => "Date", "Score" => "Score"],
                "Default" => "Score",
                "Description" =>
                    "Ordering your embedded comments by reaction will show just the best comments. Then users can head into the community to see the full discussion.",
            ],
            "Vanilla.Reactions.PromoteValue" => [
                "Type" => "int",
                "LabelCode" => "Promote Threshold",
                "Description" => "Points required for a post to be promoted to Best Of. Changes are not retroactive.",
                "Control" => "DropDown",
                "Items" => [3 => 3, 5 => 5, 10 => 10, 20 => 20],
                "Default" => Gdn::config("Vanilla.Reactions.PromoteValue", 5),
            ],
            "Vanilla.Reactions.BuryValue" => [
                "Type" => "int",
                "LabelCode" => "Bury Threshold",
                "Description" => "Points required for a post to be buried. Changes are not retroactive.",
                "Control" => "DropDown",
                "Items" => [-3 => -3, -5 => -5, -10 => -10, -20 => -20],
                "Default" => Gdn::config("Vanilla.Reactions.BuryValue", -5),
            ],
        ]);

        $this->setData("Title", sprintf(t("%s Settings"), t("Reactions")));
        $cf->renderAll();
    }
}
