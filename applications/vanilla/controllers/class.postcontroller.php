<?php
/**
 * Post controller
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Vanilla
 * @since 2.0
 */

use Vanilla\Formatting\FormatService;
use Vanilla\Premoderation\PremoderationException;

/**
 * Handles posting and editing comments, discussions, and drafts via /post endpoint.
 */
class PostController extends VanillaController
{
    use \Vanilla\Formatting\FormatCompatTrait;

    /** @var DiscussionModel */
    public $DiscussionModel;

    /** @var CommentModel */
    public $CommentModel;

    /** @var DraftModel */
    public $DraftModel;

    /** @var Gdn_Form */
    public $Form;

    /** @var array An associative array of form types and their locations. */
    public $FormCollection;

    /** @var array Models to include. */
    public $Uses = ["Form", "Database", "CommentModel", "DiscussionModel", "DraftModel"];

    /** @var bool Whether or not to show the category dropdown. */
    public $ShowCategorySelector = true;

    /** @var int */
    public $CategoryID = 0;

    /** @var null|array */
    public $Category = null;

    /** @var null|array */
    public $Context = null;

    /** @var FormatService */
    private $formatService;

    /**
     * DI.
     *
     * @param FormatService $formatService
     */
    public function __construct(FormatService $formatService)
    {
        parent::__construct();
        $this->formatService = $formatService;
    }

    /**
     * General "post" form, allows posting of any kind of form. Attach to PostController_AfterFormCollection_Handler.
     *
     * @param string $currentFormName
     * @deprecated
     * @codeCoverageIgnore
     */
    public function index($currentFormName = "discussion")
    {
        $this->addJsFile("jquery.autosize.min.js");
        $this->addJsFile("post.js");

        $this->setData("CurrentFormName", $currentFormName);
        $forms = [];
        $forms[] = [
            "Name" => "Discussion",
            "Label" => sprite("SpNewDiscussion") . t("New Discussion"),
            "Url" => "vanilla/post/discussion",
        ];
        /*
        $Forms[] = array('Name' => 'Question', 'Label' => sprite('SpAskQuestion').t('Ask Question'), 'Url' => 'vanilla/post/discussion');
        $Forms[] = array('Name' => 'Poll', 'Label' => sprite('SpNewPoll').t('New Poll'), 'Url' => 'activity');
        */
        $this->setData("Forms", $forms);
        $this->fireEvent("AfterForms");

        $this->setData("Breadcrumbs", [["Name" => t("Post"), "Url" => "/post"]]);
        $this->render();
    }

    /**
     * Filters fields out based on a list of field names.
     *
     * @param array $fields The form fields to filter.
     * @param array $filters An array of field names to filter out.
     * @return array The filtered fields.
     */
    private function filterFormValues(array $fields, array $filters)
    {
        $result = array_diff_key($fields, array_flip($filters));
        return $result;
    }

    /**
     * Get available announcement options for discussions.
     *
     * @return array
     */
    public function announceOptions()
    {
        $result = [
            "0" => "@" . t("Don't announce."),
        ];

        if (c("Vanilla.Categories.Use")) {
            $result = array_replace($result, [
                "2" => "@" . sprintf(t("In <b>%s.</b>"), t("the category")),
                "1" => "@" . sprintf(sprintf(t("In <b>%s</b> and recent discussions."), t("the category"))),
            ]);
        } else {
            $result = array_replace($result, [
                "1" => "@" . t("In recent discussions."),
            ]);
        }

        return $result;
    }

    /**
     * Create or update a discussion.
     *
     * @param int|string $categoryID Unique ID of the category to add the discussion to.
     * @throws Gdn_UserException Invalid draftID provided.
     */
    public function discussion($categoryID = "")
    {
        // Override CategoryID if categories are disabled
        $useCategories = $this->ShowCategorySelector = (bool) c("Vanilla.Categories.Use");
        if (!$useCategories) {
            $categoryID = "";
        }

        // Setup head
        $this->addJsFile("jquery.autosize.min.js");
        $this->addJsFile("post.js");

        $session = Gdn::session();

        Gdn_Theme::section("PostDiscussion");

        // Set discussion, draft, and category data
        $discussionID = isset($this->Discussion) ? $this->Discussion->DiscussionID : "";
        $draftID = isset($this->Draft) ? $this->Draft->DraftID : 0;
        $category = false;
        $categoryModel = new CategoryModel();

        if (isset($this->Discussion)) {
            $this->CategoryID = $this->Discussion->CategoryID;
            $category = CategoryModel::categories($this->CategoryID);
        } elseif ($categoryID != "") {
            $category = CategoryModel::categories($categoryID);

            if ($category) {
                $this->CategoryID = val("CategoryID", $category);
            }
        }
        if ($category) {
            $this->Category = (object) $category;
            $this->setData("Category", $category);
            $this->Form->addHidden("CategoryID", $this->Category->CategoryID);
            if (val("DisplayAs", $this->Category) == "Discussions" && !$draftID) {
                $this->ShowCategorySelector = false;
            }
        }

        $categoryData = $this->ShowCategorySelector ? CategoryModel::categories() : false;

        // Check permission
        if (isset($this->Discussion) and $draftID == 0) {
            // Make sure that content can (still) be edited.
            $canEdit = DiscussionModel::canEdit($this->Discussion);
            if (!$canEdit) {
                throw permissionException("Vanilla.Discussions.Edit");
            }

            // Make sure only moderators can edit closed things
            if ($this->Discussion->Closed) {
                $this->categoryPermission($this->Category, "Vanilla.Discussions.Edit");
            }

            $this->Form->setFormValue("DiscussionID", $this->Discussion->DiscussionID);

            $this->title(t("Edit Discussion"));

            if ($this->Discussion->Type) {
                $this->setData("Type", $this->Discussion->Type);
            } else {
                $this->setData("Type", "Discussion");
            }
        } else {
            // New discussion? Make sure a discussion ID didn't sneak in.
            $this->Form->removeFormValue("DiscussionID");

            // Permission to add.
            if ($this->Category) {
                $this->categoryPermission($this->Category, "Vanilla.Discussions.Add");
            } else {
                $this->permission("Vanilla.Discussions.Add");
            }
            $this->title(t("New Discussion"));
        }

        touchValue("Type", $this->Data, "Discussion");
        $this->Form->addHidden("Type", $this->Data["Type"]);
        $id = $category["CategoryID"] ?? null;
        // Remove Announce parameter if it was injected into the form.
        if (!CategoryModel::checkPermission($id, "Vanilla.Discussions.Announce")) {
            $this->Form->removeFormValue("Announce");
        }

        if (!$useCategories || $this->ShowCategorySelector) {
            // See if we should fill the CategoryID value.
            $allowedCategories = CategoryModel::getByPermission(
                "Discussions.Add",
                $this->Form->getValue("CategoryID", $this->CategoryID),
                ["Archived" => 0, "AllowDiscussions" => 1, "DisplayAs" => CategoryModel::DISPLAY_DISCUSSIONS],
                ["AllowedDiscussionTypes" => $this->Data["Type"]]
            );
            $allowedCategoriesCount = count($allowedCategories);

            if ($this->ShowCategorySelector && $allowedCategoriesCount === 1) {
                $this->ShowCategorySelector = false;
            }

            if (!$this->ShowCategorySelector && $allowedCategoriesCount) {
                $allowedCategory = array_pop($allowedCategories);
                $this->Form->addHidden("CategoryID", $allowedCategory["CategoryID"]);

                if ($this->Form->isPostBack() && !$this->Form->getFormValue("CategoryID")) {
                    $this->Form->setFormValue("CategoryID", $allowedCategory["CategoryID"]);
                }
            }
        }

        // Set the model on the form
        $this->Form->setModel($this->DiscussionModel);
        if (!$this->Form->isPostBack()) {
            // Prep form with current data for editing
            if (isset($this->Discussion)) {
                $this->Form->setData($this->Discussion);
            } elseif (isset($this->Draft)) {
                $this->Form->setData($this->Draft);
            } else {
                if ($this->Category !== null) {
                    $this->Form->setData(["CategoryID" => $this->Category->CategoryID]);
                }
                $this->populateForm($this->Form);
            }

            // Decode HTML entities escaped by DiscussionModel::calculate() here.
            $this->Form->setValue("Name", htmlspecialchars_decode($this->Form->getValue("Name")));
        } elseif ($this->Form->authenticatedPostBack(true)) {
            // Form was submitted
            // Save as a draft?
            $formValues = $this->Form->formValues();
            $filters = ["Score"];
            $formValues = $this->filterFormValues($formValues, $filters);
            $formValues = $this->DiscussionModel->filterForm($formValues);
            $this->deliveryType(Gdn::request()->getValue("DeliveryType", $this->_DeliveryType));
            if ($draftID == 0) {
                $draftID = $this->Form->getFormValue("DraftID", 0);
                if ($draftID) {
                    if (!is_numeric($draftID)) {
                        throw new Gdn_UserException("Invalid draft ID.");
                    }

                    $draftObject = $this->DraftModel->getID($draftID, DATASET_TYPE_ARRAY);
                    if (!$draftObject) {
                        throw notFoundException("Draft");
                    } elseif (
                        val("InsertUserID", $draftObject) != Gdn::session()->UserID &&
                        !checkPermission("Garden.Community.Manage")
                    ) {
                        throw permissionException("Garden.Community.Manage");
                    }
                }
            } else {
                if ($draftID != $formValues["DraftID"]) {
                    throw new Exception("DraftID mismatch.");
                }
            }

            $draft = $this->Form->buttonExists("Save_Draft") ? true : false;
            $preview = $this->Form->buttonExists("Preview") ? true : false;
            if (!$preview) {
                if (!is_object($this->Category) && is_array($categoryData) && isset($formValues["CategoryID"])) {
                    $this->Category = val($formValues["CategoryID"], $categoryData);
                    $formCategoryID = $formValues["CategoryID"];
                    if ($formCategoryID) {
                        $formCategory = $categoryModel->getID($formCategoryID);
                        if (!$formCategory) {
                            $this->Form->addError(t("Category does not exist."));
                        }
                    }
                }

                if (!empty($this->Category)) {
                    // Check category permissions.
                    if (
                        $this->Form->getFormValue("Announce") &&
                        !CategoryModel::checkPermission($this->Category, "Vanilla.Discussions.Announce")
                    ) {
                        $this->Form->addError("You do not have permission to announce in this category", "Announce");
                    }

                    if (
                        $this->Form->getFormValue("Close") &&
                        !CategoryModel::checkPermission($this->Category, "Vanilla.Discussions.Close")
                    ) {
                        $this->Form->addError("You do not have permission to close in this category", "Close");
                    }

                    if (
                        $this->Form->getFormValue("Sink") &&
                        !CategoryModel::checkPermission($this->Category, "Vanilla.Discussions.Sink")
                    ) {
                        $this->Form->addError("You do not have permission to sink in this category", "Sink");
                    }

                    if (
                        !isset($this->Discussion) &&
                        !CategoryModel::checkPermission($this->Category, "Vanilla.Discussions.Add")
                    ) {
                        $this->Form->addError(
                            "You do not have permission to start discussions in this category",
                            "CategoryID"
                        );
                    }

                    // Check for uploads against category->AllowFileUploads. (admins bypass this condition)
                    if (!boolval($session->User->Admin)) {
                        $objCategory = (object) $this->Category;

                        if (!CategoryModel::checkAllowFileUploads($objCategory)) {
                            $hasUpload = $this->hasFormUploads(
                                $this->Form->getFormValue("Format"),
                                $this->Form->getFormValue("Body")
                            );

                            if ($hasUpload) {
                                $this->Form->addError(
                                    "You are not allowed to post attachments in this category. Please remove any attachments to proceed."
                                );
                            }
                        }
                    }
                }

                $isTitleValid = true;
                $name = trim($this->Form->getFormValue("Name", ""));

                if (!$draft) {
                    // Let's be super aggressive and disallow titles with no word characters in them!
                    $hasWordCharacter = preg_match("/\w/u", $name) === 1;

                    if (!$hasWordCharacter || ($name != "" && Gdn_Format::text($name) == "")) {
                        $this->Form->addError(t("You have entered an invalid discussion title"), "Name");
                        $isTitleValid = false;
                    }
                }

                if ($isTitleValid && isset($formValues["Name"])) {
                    // Trim the name.
                    $formValues["Name"] = $name;
                    $this->Form->setFormValue("Name", $name);
                }

                if ($this->Form->errorCount() == 0) {
                    if ($draft) {
                        $formValues["Type"] = $formValues["Type"] ?? "Discussion";
                        $draftID = $this->DraftModel->save($formValues);
                        $this->Form->setValidationResults($this->DraftModel->validationResults());
                    } else {
                        try {
                            $discussionID = $this->DiscussionModel->save($formValues);
                        } catch (PremoderationException $e) {
                            $discussionID = SPAM;
                        }
                        $this->Form->setValidationResults($this->DiscussionModel->validationResults());

                        if ($discussionID > 0) {
                            if ($draftID > 0) {
                                $this->DraftModel->deleteID($draftID);
                            }
                        }
                        if ($discussionID == SPAM || $discussionID == UNAPPROVED) {
                            $this->StatusMessage = t("Your discussion will appear after it is approved.");

                            // Clear out the form so that a draft won't save.
                            $this->Form->formValues([]);

                            $this->render("Spam");
                            return;
                        }
                    }
                }
            } else {
                // If this was a preview click, create a discussion/comment shell with the values for this comment
                $this->Discussion = new stdClass();
                $this->Discussion->Name = $this->Form->getValue("Name", "");
                $this->Comment = new stdClass();
                $this->Comment->InsertUserID = $session->User->UserID;
                $this->Comment->InsertName = $session->User->Name;
                $this->Comment->InsertPhoto = $session->User->Photo;
                $this->Comment->DateInserted = Gdn_Format::date();
                $this->Comment->Body = val("Body", $formValues, "");
                $this->Comment->Format = val("Format", $formValues, c("Garden.InputFormatter"));

                $this->EventArguments["Discussion"] = &$this->Discussion;
                $this->EventArguments["Comment"] = &$this->Comment;
                $this->fireEvent("BeforeDiscussionPreview");

                if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
                    $this->addAsset("Content", $this->fetchView("preview"));
                } else {
                    $this->View = "preview";
                }
            }
            if ($this->Form->errorCount() > 0) {
                // Return the form errors
                $this->errorMessage($this->Form->errors());
            } elseif ($discussionID > 0 || $draftID > 0) {
                // Make sure that the ajax request form knows about the newly created discussion or draft id
                $this->setJson("DiscussionID", $discussionID);
                $this->setJson("CategoryID", $this->Form->getFormValue("CategoryID", $this->CategoryID));
                $this->setJson("DraftID", $draftID);
                $this->setJson("Type", $this->Data["Type"]);

                if (!$preview) {
                    // If the discussion was not a draft
                    if (!$draft) {
                        // Redirect to the new discussion
                        $discussion = $this->DiscussionModel->getID($discussionID, DATASET_TYPE_OBJECT, [
                            "Slave" => false,
                        ]);
                        $this->setData("Discussion", $discussion);
                        $this->EventArguments["Discussion"] = $discussion;
                        $this->fireEvent("AfterDiscussionSave");

                        if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
                            redirectTo(discussionUrl($discussion, 1) . "?new=1");
                        } else {
                            $this->setRedirectTo(discussionUrl($discussion, 1, true) . "?new=1");
                        }
                    } else {
                        // If this was a draft save, notify the user about the save
                        $this->informMessage(sprintf(t("Draft saved at %s"), Gdn_Format::date()));
                    }
                }
            }
        }

        // Add hidden fields for editing
        $this->Form->addHidden("DiscussionID", $discussionID);
        $this->Form->addHidden("DraftID", $draftID, true);

        $this->fireEvent("BeforeDiscussionRender");

        if ($this->CategoryID) {
            $breadcrumbs = CategoryModel::getAncestors($this->CategoryID);
        } else {
            $breadcrumbs = [];
        }

        $breadcrumbs[] = [
            "Name" => $this->data("Title"),
            "Url" => val("AddUrl", val($this->data("Type"), DiscussionModel::discussionTypes()), "/post/discussion"),
        ];

        $this->setData("Breadcrumbs", $breadcrumbs);

        $this->setData("_AnnounceOptions", $this->announceOptions());

        // Render view (posts/discussion.php or post/preview.php)
        $this->render();
    }

    /**
     * Check for uploads on the post body
     *
     * @param string $format
     * @param string $body
     * @return bool
     */
    public function hasFormUploads($format, $body)
    {
        $attachments = $this->formatService->parseAttachments($body, $format);

        return !!count($attachments);
    }

    /**
     * Edit a discussion (wrapper for PostController::Discussion).
     *
     * Will throw an error if both params are blank.
     *
     * @param int $discussionID Unique ID of the discussion to edit.
     * @param int $draftID Unique ID of draft discussion to edit.
     */
    public function editDiscussion($discussionID = 0, $draftID = 0)
    {
        if ($draftID != 0) {
            $record = $this->Draft = $this->DraftModel->getID($draftID);
            $this->CategoryID = $this->Draft->CategoryID;
            $this->setData("Type", $record->Type ?? "Discussion");
            $this->setData("Discussion", $record, true);
            // Verify this is their draft
            if (val("InsertUserID", $this->Draft) != Gdn::session()->UserID) {
                throw permissionException();
            }
        } else {
            $record = $this->DiscussionModel->getID($discussionID);
            $this->setData("Discussion", $record, true);
            $this->CategoryID = $this->Discussion->CategoryID;
        }

        // Normalize the edit data.
        $this->applyFormatCompatibility($record, "Body", "Format");

        // Verify we can add to the category content
        $this->categoryPermission($this->CategoryID, "Vanilla.Discussions.Add");

        if (Gdn::config("Garden.ForceInputFormatter")) {
            $format = Gdn::config("Garden.InputFormatter", "");
            $this->Form->setFormValue("Format", $format);
        }

        if ($discussionID > 0) {
            $this->setData("_CancelUrl", discussionUrl($this->data("Discussion")));
        } else {
            $this->setData("_CancelUrl", url("drafts"));
        }

        // Set view and render
        $this->View = "Discussion";
        $this->discussion($this->CategoryID);
    }

    /**
     * Create or update a comment.
     *
     * @param int|string $DiscussionID Unique ID to add the comment to. If blank, this method will throw an error.
     * @throws Gdn_UserException Invalid draftID provided.
     */
    public function comment($DiscussionID = "")
    {
        /** @var object|null $Comment Saved comment data as an object */
        $Comment = null;

        // Get $DiscussionID from RequestArgs if valid
        if ($DiscussionID == "" && count($this->RequestArgs)) {
            if (is_numeric($this->RequestArgs[0])) {
                $DiscussionID = $this->RequestArgs[0];
            }
        }

        // If invalid $DiscussionID, get from form.
        $this->Form->setModel($this->CommentModel);
        $DiscussionID = is_numeric($DiscussionID) ? $DiscussionID : $this->Form->getFormValue("DiscussionID", 0);

        // Set discussion data
        $this->DiscussionID = $DiscussionID;
        $this->Discussion = $Discussion = $this->DiscussionModel->getID($DiscussionID);

        $isAuthenticatedPostback = $this->Form->authenticatedPostBack();

        // Is this an embedded comment being posted to a discussion that doesn't exist yet?
        if (c("Garden.Embed.Allow")) {
            $vanilla_type = $this->Form->getFormValue("vanilla_type", "");
            $vanilla_url = $this->Form->getFormValue("vanilla_url", "");
            $vanilla_category_id = $this->Form->getFormValue("vanilla_category_id", "");
            $Attributes = ["ForeignUrl" => $vanilla_url];
            $vanilla_identifier = $this->Form->getFormValue("vanilla_identifier", "");
            $isEmbeddedComments = $vanilla_url != "" && $vanilla_identifier != "";

            // If we already have a discussion with this ForeginID, add the discussion id to the form, to avoid duplicate discussions.
            if ($isEmbeddedComments && !$DiscussionID) {
                $Discussion = $this->DiscussionModel
                    ->getWhere(["ForeignID" => $vanilla_identifier])
                    ->firstRow(DATASET_TYPE_OBJECT);
                if (!empty($Discussion)) {
                    $DiscussionID = $this->DiscussionID = $Discussion->DiscussionID;
                    $this->Discussion = $Discussion;
                    $this->Form->setFormValue("DiscussionID", $DiscussionID);
                }
            }
            // Only allow vanilla identifiers of 32 chars or less - md5 if larger
            if (strlen($vanilla_identifier) > 32) {
                $Attributes["vanilla_identifier"] = $vanilla_identifier;
                $vanilla_identifier = md5($vanilla_identifier);
            }

            // If so, create it!
            if (!$Discussion && $isEmbeddedComments && $isAuthenticatedPostback) {
                // Add these values back to the form if they exist!
                $this->Form->addHidden("vanilla_identifier", $vanilla_identifier);
                $this->Form->addHidden("vanilla_type", $vanilla_type);
                $this->Form->addHidden("vanilla_url", $vanilla_url);
                $this->Form->addHidden("vanilla_category_id", $vanilla_category_id);

                $PageInfo = $this->DiscussionModel->fetchPageInfo($vanilla_url);

                // Validate the CategoryID for inserting.
                $Category = CategoryModel::categories($vanilla_category_id);
                if (!$Category) {
                    $vanilla_category_id = c("Vanilla.Embed.DefaultCategoryID", 0);
                    if ($vanilla_category_id <= 0) {
                        // No default category defined, so grab the first non-root category and use that.
                        $vanilla_category_id = $this->DiscussionModel->SQL
                            ->select("CategoryID")
                            ->from("Category")
                            ->where("CategoryID >", 0)
                            ->get()
                            ->firstRow()->CategoryID;
                        // No categories in the db? default to 0
                        if (!$vanilla_category_id) {
                            $vanilla_category_id = 0;
                        }
                    }
                } else {
                    $vanilla_category_id = $Category["CategoryID"];
                }

                // Ensure the user can comment in this category before creating a discussion in it.
                $this->categoryPermission($vanilla_category_id, "Vanilla.Comments.Add");

                $EmbedUserID = c("Garden.Embed.UserID");
                if ($EmbedUserID) {
                    $EmbedUser = Gdn::userModel()->getID($EmbedUserID);
                }
                if (!$EmbedUserID || !$EmbedUser) {
                    $EmbedUserID = Gdn::userModel()->getSystemUserID();
                }

                $EmbeddedDiscussionData = [
                    "InsertUserID" => $EmbedUserID,
                    "DateInserted" => Gdn_Format::toDateTime(),
                    "DateUpdated" => Gdn_Format::toDateTime(),
                    "CategoryID" => $vanilla_category_id,
                    "ForeignID" => $vanilla_identifier,
                    "Type" => $vanilla_type,
                    "Name" => $PageInfo["Name"],
                    "Body" => $PageInfo["Body"],
                    "Format" => $PageInfo["Format"],
                    "Attributes" => dbencode($Attributes),
                ];
                $this->EventArguments["Discussion"] = &$EmbeddedDiscussionData;
                $this->fireEvent("BeforeEmbedDiscussion");
                $DiscussionID = $this->DiscussionModel->save($EmbeddedDiscussionData);
                $ValidationResults = $this->DiscussionModel->validationResults();
                if (count($ValidationResults) == 0 && $DiscussionID > 0) {
                    $this->Form->addHidden("DiscussionID", $DiscussionID); // Put this in the form so reposts won't cause new discussions.
                    $this->Form->setFormValue("DiscussionID", $DiscussionID); // Put this in the form values so it is used when saving comments.
                    $this->setJson("DiscussionID", $DiscussionID);
                    $this->Discussion = $Discussion = $this->DiscussionModel->getID(
                        $DiscussionID,
                        DATASET_TYPE_OBJECT,
                        ["Slave" => false]
                    );
                }
            }

            /*
             * Special care is taken for embedded comments.  Since we don't currently use an advanced editor for these
             * comments, we may need to apply certain filters and fixes to the data to maintain its intended display
             * with the input format (e.g. maintaining newlines).
             */
            if ($isEmbeddedComments) {
                $inputFormatter = $this->Form->getFormValue("Format", c("Garden.InputFormatter"));

                switch ($inputFormatter) {
                    case "Wysiwyg":
                        $this->Form->setFormValue("Body", nl2br($this->Form->getFormValue("Body")));
                        break;
                }
            }
        }

        // If no discussion was found, error out
        if (!$Discussion) {
            $this->Form->addError(t("Failed to find discussion for commenting."));
        }

        // Setup head
        $this->addJsFile("jquery.autosize.min.js");
        $this->addJsFile("post.js");

        // Setup comment model, $CommentID, $DraftID
        $Session = Gdn::session();
        $CommentID =
            isset($this->Comment) && property_exists($this->Comment, "CommentID") ? $this->Comment->CommentID : "";
        $DraftID = isset($this->Comment) && property_exists($this->Comment, "DraftID") ? $this->Comment->DraftID : "";
        if (!is_numeric($DraftID) && $DraftID !== "") {
            throw new Gdn_UserException("Invalid draft ID.");
        }
        if ($DraftID !== "") {
            if (
                $Session->UserID !== $this->Comment->InsertUserID &&
                !$Session->checkPermission("Garden.Settings.Manage")
            ) {
                throw new \Garden\Web\Exception\ForbiddenException(t("ErrorPermission"));
            }
        }
        $this->EventArguments["CommentID"] = $CommentID;
        $this->EventArguments["DraftID"] = $DraftID;

        // Determine whether we are editing
        $Editing = $CommentID > 0 || $DraftID > 0;
        $this->EventArguments["Editing"] = $Editing;

        // If closed, cancel & go to discussion
        if (
            $Discussion &&
            $Discussion->Closed == 1 &&
            !$Editing &&
            !CategoryModel::checkPermission($Discussion->CategoryID, "Vanilla.Discussions.Close")
        ) {
            redirectTo(discussionUrl($Discussion));
        }

        // Add hidden IDs to form
        $this->Form->addHidden("DiscussionID", $DiscussionID);
        $this->Form->addHidden("CommentID", $CommentID);
        $this->Form->addHidden("DraftID", $DraftID, true);

        // Check permissions
        if ($Discussion && $Editing) {
            // Make sure that content can (still) be edited.
            $editTimeout = 0;
            if (!CommentModel::canEdit($this->Comment, $editTimeout, $Discussion)) {
                throw permissionException("Vanilla.Comments.Edit");
            }

            $this->Form->setFormValue("CommentID", $CommentID);
        } elseif ($Discussion) {
            // Permission to add
            $this->categoryPermission($Discussion->CategoryID, "Vanilla.Comments.Add");
        }

        if ($isAuthenticatedPostback) {
            // Save as a draft?
            $FormValues = $this->Form->formValues();

            if (isset($FormValues["DiscussionID"])) {
                $formID = (int) $FormValues["DiscussionID"];
                $DiscussionID = (int) $DiscussionID;
                if ($formID !== $DiscussionID) {
                    throw new Exception("DiscussionID mismatch.");
                }
            }

            $filters = ["Score"];
            $FormValues = $this->filterFormValues($FormValues, $filters);
            $FormValues = $this->CommentModel->filterForm($FormValues);
            $formDiscussion = $this->DiscussionModel->getID($this->Form->_FormValues["DiscussionID"]);

            if (
                $formDiscussion &&
                $formDiscussion->Closed === 1 &&
                !CategoryModel::checkPermission($formDiscussion->CategoryID, "Vanilla.Discussions.Close")
            ) {
                throw new Exception(t("You cannot comment in a closed discussion."));
            }

            if (!$Editing) {
                unset($FormValues["CommentID"]);
            }

            if ((int) $DraftID == 0) {
                $DraftID = $this->Form->getFormValue("DraftID", 0);
                if ($DraftID) {
                    $draft = $this->DraftModel->getID($DraftID, DATASET_TYPE_ARRAY);
                    if (!$draft) {
                        throw notFoundException("Draft");
                    } elseif (
                        val("InsertUserID", $draft) != $Session->UserID &&
                        !checkPermission("Garden.Community.Manage")
                    ) {
                        throw permissionException("Garden.Community.Manage");
                    }
                }
            }

            $Type = $this->Request->getValue("Type");
            $Draft = $Type == "Draft";
            $this->EventArguments["Draft"] = $Draft;
            $Preview = $Type == "Preview";
            if ($Draft) {
                $DraftID = $this->DraftModel->save($FormValues);
                $this->Form->addHidden("DraftID", $DraftID, true);
                $this->Form->setValidationResults($this->DraftModel->validationResults());
            } elseif (!$Preview) {
                // Fix an undefined title if we can.
                if (
                    $this->Form->getFormValue("Name") &&
                    val("Name", $Discussion) == t("Undefined discussion subject.")
                ) {
                    $Set = ["Name" => $this->Form->getFormValue("Name")];

                    if (
                        isset($vanilla_url) &&
                        $vanilla_url &&
                        strpos(val("Body", $Discussion), t("Undefined discussion subject.")) !== false
                    ) {
                        $LinkText = t("EmbededDiscussionLinkText", "Read the full story here");
                        $Set["Body"] = formatString('<p><a href="{Url}">{LinkText}</a></p>', [
                            "Url" => $vanilla_url,
                            "LinkText" => $LinkText,
                        ]);
                    }

                    $this->DiscussionModel->setField(val("DiscussionID", $Discussion), $Set);
                }

                $Inserted = !$CommentID;
                try {
                    $CommentID = $this->CommentModel->save($FormValues);
                } catch (PremoderationException $e) {
                    $CommentID = SPAM;
                }

                // The comment is now half-saved.
                if (is_numeric($CommentID) && $CommentID > 0) {
                    // $Discussion = $this->DiscussionModel->getID($DiscussionID);
                    $Comment = $this->CommentModel->getID($CommentID, DATASET_TYPE_OBJECT, ["Slave" => false]);

                    $this->EventArguments["Discussion"] = $Discussion;
                    $this->EventArguments["Comment"] = $Comment;
                    $this->fireEvent("AfterCommentSave");
                } elseif ($CommentID === SPAM || $CommentID === UNAPPROVED) {
                    $this->StatusMessage = t("Your comment will appear after it is approved.");
                }

                $this->Form->setValidationResults($this->CommentModel->validationResults());
                if ($CommentID > 0 && $DraftID > 0) {
                    $this->DraftModel->deleteID($DraftID);
                }
            }

            // Handle non-ajax requests first:
            if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
                if ($this->Form->errorCount() == 0) {
                    // Make sure that this form knows what comment we are editing.
                    if ($CommentID > 0) {
                        $this->Form->addHidden("CommentID", $CommentID);
                    }

                    // If the comment was not a draft
                    if (!$Draft) {
                        // Redirect to the new comment.
                        if ($CommentID > 0) {
                            redirectTo("discussion/comment/$CommentID/#Comment_$CommentID");
                        } elseif ($CommentID == SPAM) {
                            $this->setData("DiscussionUrl", discussionUrl($Discussion));
                            $this->View = "Spam";
                        }
                    } elseif ($Preview) {
                        // If this was a preview click, create a comment shell with the values for this comment
                        $this->Comment = new stdClass();
                        $this->Comment->InsertUserID = $Session->User->UserID;
                        $this->Comment->InsertName = $Session->User->Name;
                        $this->Comment->InsertPhoto = $Session->User->Photo;
                        $this->Comment->DateInserted = Gdn_Format::date();
                        $this->Comment->Body = val("Body", $FormValues, "");
                        $this->Comment->Format = val("Format", $FormValues, c("Garden.InputFormatter"));
                        $this->addAsset("Content", $this->fetchView("preview"));
                    } else {
                        // If this was a draft save, notify the user about the save
                        $this->informMessage(sprintf(t("Draft saved at %s"), Gdn_Format::date()));
                    }
                }
            } else {
                // Handle ajax-based requests
                if ($this->Form->errorCount() > 0) {
                    // Return the form errors
                    $this->errorMessage($this->Form->errors());
                } else {
                    // Make sure that the ajax request form knows about the newly created comment or draft id
                    $this->setJson("CommentID", $CommentID);
                    $this->setJson("DiscussionID", $DiscussionID);
                    $this->setJson("DraftID", $DraftID);

                    if ($Preview) {
                        // If this was a preview click, create a comment shell with the values for this comment
                        $this->Comment = new stdClass();
                        $this->Comment->InsertUserID = $Session->User->UserID;
                        $this->Comment->InsertName = $Session->User->Name;
                        $this->Comment->InsertPhoto = $Session->User->Photo;
                        $this->Comment->DateInserted = Gdn_Format::date();
                        $this->Comment->Body = val("Body", $FormValues, "");
                        $this->Comment->Format = val("Format", $FormValues, c("Garden.InputFormatter"));
                        $this->View = "preview";
                    } elseif (!$Draft) {
                        // If the comment was not a draft
                        // If Editing a comment
                        if ($Editing) {
                            // Just reload the comment in question
                            $this->Offset = 1;
                            $Comments = $this->CommentModel->getID($CommentID);
                            $this->setData("Comments", new Gdn_DataSet([$Comments]));
                            $this->setData("Discussion", $Discussion);
                            // Load the discussion
                            $this->ControllerName = "discussion";
                            $this->View = "comments";

                            // Also define the discussion url in case this request came from the post screen and needs to be redirected to the discussion
                            $this->setJson(
                                "DiscussionUrl",
                                discussionUrl($this->Discussion) . "#Comment_" . $CommentID
                            );
                        } else {
                            // If the comment model isn't sorted by DateInserted or CommentID then we can't do any fancy loading of comments.
                            $OrderBy = valr("0.0", $this->CommentModel->orderBy());

                            if (is_numeric($CommentID)) {
                                $this->Offset = $this->CommentModel->getDiscussionThreadOffset($CommentID);
                                $Comments = new Gdn_DataSet([$this->CommentModel->getID($CommentID)]);
                            } else {
                                $Comments = new Gdn_DataSet();
                            }

                            $this->setData("Comments", $Comments);

                            $this->setData("NewComments", true);

                            $this->ClassName = "DiscussionController";
                            $this->ControllerName = "discussion";
                            $this->View = "comments";
                            //                     }

                            // Make sure to set the user's discussion watch records
                            $CountComments = $this->CommentModel->getCountByDiscussion($DiscussionID);
                            $Limit = is_object($this->data("Comments"))
                                ? $this->data("Comments")->numRows()
                                : $Discussion->CountComments;
                            $Offset = $CountComments - $Limit;
                            $this->Offset = $this->Offset ?? $Offset;
                            $this->DiscussionModel->setWatch(
                                $this->Discussion,
                                $Limit,
                                $Offset,
                                $CountComments,
                                isset($Comment) ? $Comment->DateInserted : null
                            );
                        }
                    } else {
                        // If this was a draft save, notify the user about the save
                        $this->informMessage(sprintf(t("Draft saved at %s"), Gdn_Format::date()));
                    }
                    // And update the draft count
                    $UserModel = Gdn::userModel();
                    $CountDrafts = $UserModel->getAttribute($Session->UserID, "CountDrafts", 0);
                    $this->setJson("MyDrafts", t("My Drafts"));
                    $this->setJson("CountDrafts", $CountDrafts);
                }
            }
        } elseif ($this->Request->isPostBack()) {
            throw new Gdn_UserException(t("Invalid CSRF token.", "Invalid CSRF token. Please try again."), 401);
        } else {
            // Load form
            if (isset($this->Comment)) {
                $this->Form->setData((array) $this->Comment);
            }
        }

        // Include data for FireEvent
        if (property_exists($this, "Discussion")) {
            $this->EventArguments["Discussion"] = $this->Discussion;
        }
        if (property_exists($this, "Comment")) {
            $this->EventArguments["Comment"] = $this->Comment;
        }

        $this->fireEvent("BeforeCommentRender");

        include_once $this->fetchViewLocation("reaction_functions", "reactions", "dashboard");

        if ($this->deliveryType() == DELIVERY_TYPE_DATA) {
            if ($this->data("Comments") instanceof Gdn_DataSet || is_array($this->data("Comments"))) {
                $Comment =
                    $this->data("Comments") instanceof Gdn_DataSet
                        ? $this->data("Comments")->firstRow(DATASET_TYPE_ARRAY)
                        : (array) $this->data("Comments")[0];
                if ($Comment) {
                    $Photo = $Comment["InsertPhoto"];

                    if (strpos($Photo, "//") === false) {
                        $Photo = Gdn_Upload::url(changeBasename($Photo, "n%s"));
                    }

                    $Comment["InsertPhoto"] = $Photo;
                }
                $this->Data = ["Comment" => $Comment];
            }
            $this->renderData($this->Data);
        } else {
            require_once $this->fetchViewLocation("helper_functions", "Discussion");
            // Render default view.
            $this->render();
        }
    }

    /**
     * Edit a comment (wrapper for PostController::Comment).
     *
     * Will throw an error if both params are blank.
     *
     * @param int $commentID Unique ID of the comment to edit.
     * @param int $draftID Unique ID of the draft to edit.
     */
    public function editComment($commentID = 0, $draftID = 0)
    {
        if (is_numeric($commentID) && $commentID > 0) {
            $this->Form->setModel($this->CommentModel);
            $this->Comment = $this->CommentModel->getID($commentID);
        } else {
            $this->Form->setModel($this->DraftModel);
            $this->Comment = $this->DraftModel->getID($draftID);
        }

        // Normalize the edit data.
        $this->applyFormatCompatibility($this->Comment, "Body", "Format");

        if (Gdn::config("Garden.ForceInputFormatter")) {
            $format = Gdn::config("Garden.InputFormatter", "");
            $this->Form->setFormValue("Format", $format);
        }

        $this->View = "editcomment";
        $this->comment($this->Comment->DiscussionID ?? 0);
    }

    /**
     * Include CSS for all methods.
     *
     * Always called by dispatcher before controller's requested method.
     */
    public function initialize()
    {
        parent::initialize();

        $this->CssClass = "NoPanel";
    }

    /**
     * Pre-populate the form with values from the query string.
     *
     * @param Gdn_Form $form
     */
    protected function populateForm($form)
    {
        $get = $this->Request->get();
        $get = array_change_key_case($get);
        $values = arrayTranslate($get, ["name" => "Name", "tags" => "Tags", "body" => "Body"]);
        foreach ($values as $key => $value) {
            $form->setValue($key, $value);
        }

        if (isset($get["category"])) {
            $category = CategoryModel::categories($get["category"]);
            if ($category && $category["PermsDiscussionsAdd"]) {
                $form->setValue("CategoryID", $category["CategoryID"]);
            }
        }
    }
}

function checkOrRadio($fieldName, $labelCode, $listOptions, $attributes = [])
{
    $form = Gdn::controller()->Form;

    if (count($listOptions) == 2 && array_key_exists(0, $listOptions)) {
        unset($listOptions[0]);

        // This can be represented by a checkbox.
        return $form->checkBox($fieldName, $labelCode);
    } else {
        $cssClass = val("ListClass", $attributes, "List Inline");

        $result = " <b>" . t($labelCode) . "</b> <ul class=\"$cssClass\">";
        foreach ($listOptions as $value => $code) {
            $result .=
                " <li>" . $form->radio($fieldName, $code, ["Value" => $value, "class" => "radio-inline"]) . "</li> ";
        }
        $result .= "</ul>";
        return $result;
    }
}
