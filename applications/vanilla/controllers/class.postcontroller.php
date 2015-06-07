<?php
/**
 * Post controller
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

/**
 * Handles posting and editing comments, discussions, and drafts via /post endpoint.
 */
class PostController extends VanillaController {

    /** @var DiscussionModel */
    public $DiscussionModel;

    /** @var Gdn_Form */
    public $Form;

    /** @var array An associative array of form types and their locations. */
    public $FormCollection;

    /** @var array Models to include. */
    public $Uses = array('Form', 'Database', 'CommentModel', 'DiscussionModel', 'DraftModel');

    /** @var bool Whether or not to show the category dropdown. */
    public $ShowCategorySelector = true;

    /**
     * General "post" form, allows posting of any kind of form. Attach to PostController_AfterFormCollection_Handler.
     *
     * @since 2.0.0
     * @access public
     */
    public function index($CurrentFormName = 'discussion') {
        $this->addJsFile('jquery.autosize.min.js');
        $this->addJsFile('autosave.js');
        $this->addJsFile('post.js');

        $this->setData('CurrentFormName', $CurrentFormName);
        $Forms = array();
        $Forms[] = array('Name' => 'Discussion', 'Label' => sprite('SpNewDiscussion').t('New Discussion'), 'Url' => 'vanilla/post/discussion');
        /*
        $Forms[] = array('Name' => 'Question', 'Label' => sprite('SpAskQuestion').t('Ask Question'), 'Url' => 'vanilla/post/discussion');
        $Forms[] = array('Name' => 'Poll', 'Label' => sprite('SpNewPoll').t('New Poll'), 'Url' => 'activity');
        */
        $this->setData('Forms', $Forms);
        $this->fireEvent('AfterForms');

        $this->setData('Breadcrumbs', array(array('Name' => t('Post'), 'Url' => '/post')));
        $this->render();
    }

    public function announceOptions() {
        $Result = array(
            '0' => '@'.t("Don't announce.")
        );

        if (c('Vanilla.Categories.Use')) {
            $Result = array_replace($Result, array(
                '2' => '@'.sprintf(t('In <b>%s.</b>'), t('the category')),
                '1' => '@'.sprintf(sprintf(t('In <b>%s</b> and recent discussions.'), t('the category'))),
            ));
        } else {
            $Result = array_replace($Result, array(
                '1' => '@'.t('In recent discussions.'),
            ));
        }

        return $Result;
    }

    /**
     * Create or update a discussion.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $CategoryID Unique ID of the category to add the discussion to.
     */
    public function discussion($CategoryUrlCode = '') {
        // Override CategoryID if categories are disabled
        $UseCategories = $this->ShowCategorySelector = (bool)c('Vanilla.Categories.Use');
        if (!$UseCategories) {
            $CategoryUrlCode = '';
        }

        // Setup head
        $this->addJsFile('jquery.autosize.min.js');
        $this->addJsFile('autosave.js');
        $this->addJsFile('post.js');

        $Session = Gdn::session();

        Gdn_Theme::section('PostDiscussion');

        // Set discussion, draft, and category data
        $DiscussionID = isset($this->Discussion) ? $this->Discussion->DiscussionID : '';
        $DraftID = isset($this->Draft) ? $this->Draft->DraftID : 0;
        $Category = false;
        if (isset($this->Discussion)) {
            $this->CategoryID = $this->Discussion->CategoryID;
            $Category = CategoryModel::categories($this->CategoryID);
        } elseif ($CategoryUrlCode != '') {
            $CategoryModel = new CategoryModel();
            if (is_numeric($CategoryUrlCode)) {
                $Category = CategoryModel::categories($CategoryUrlCode);
            } else {
                $Category = $CategoryModel->GetByCode($CategoryUrlCode);
            }

            if ($Category) {
                $this->CategoryID = val('CategoryID', $Category);
            }

        }
        if ($Category) {
            $this->Category = (object)$Category;
            $this->setData('Category', $Category);
        } else {
            $this->CategoryID = 0;
            $this->Category = null;
        }

        $CategoryData = $UseCategories ? CategoryModel::categories() : false;

        // Check permission
        if (isset($this->Discussion)) {
            // Make sure that content can (still) be edited.
            $CanEdit = DiscussionModel::canEdit($this->Discussion);
            if (!$CanEdit) {
                throw permissionException('Vanilla.Discussions.Edit');
            }

            // Make sure only moderators can edit closed things
            if ($this->Discussion->Closed) {
                $this->permission('Vanilla.Discussions.Edit', true, 'Category', $this->Category->PermissionCategoryID);
            }

            $this->Form->setFormValue('DiscussionID', $this->Discussion->DiscussionID);

            $this->title(t('Edit Discussion'));

            if ($this->Discussion->Type) {
                $this->setData('Type', $this->Discussion->Type);
            } else {
                $this->setData('Type', 'Discussion');
            }
        } else {
            // Permission to add
            $this->permission('Vanilla.Discussions.Add');
            $this->title(t('New Discussion'));
        }

        touchValue('Type', $this->Data, 'Discussion');

        // See if we should hide the category dropdown.
        $AllowedCategories = CategoryModel::GetByPermission('Discussions.Add', $this->Form->getValue('CategoryID', $this->CategoryID), array('Archived' => 0, 'AllowDiscussions' => 1), array('AllowedDiscussionTypes' => $this->Data['Type']));
        if (count($AllowedCategories) == 1) {
            $AllowedCategory = array_pop($AllowedCategories);
            $this->ShowCategorySelector = false;
            $this->Form->addHidden('CategoryID', $AllowedCategory['CategoryID']);

            if ($this->Form->isPostBack() && !$this->Form->getFormValue('CategoryID')) {
                $this->Form->setFormValue('CategoryID', $AllowedCategory['CategoryID']);
            }
        }

        // Set the model on the form
        $this->Form->setModel($this->DiscussionModel);
        if (!$this->Form->isPostBack()) {
            // Prep form with current data for editing
            if (isset($this->Discussion)) {
                $this->Form->setData($this->Discussion);
            } elseif (isset($this->Draft))
                $this->Form->setData($this->Draft);
            else {
                if ($this->Category !== null) {
                    $this->Form->setData(array('CategoryID' => $this->Category->CategoryID));
                }
                $this->PopulateForm($this->Form);
            }

        } elseif ($this->Form->authenticatedPostBack()) { // Form was submitted
            // Save as a draft?
            $FormValues = $this->Form->formValues();
            $FormValues = $this->DiscussionModel->filterForm($FormValues);
            $this->deliveryType(GetIncomingValue('DeliveryType', $this->_DeliveryType));
            if ($DraftID == 0) {
                $DraftID = $this->Form->getFormValue('DraftID', 0);
            }

            $Draft = $this->Form->ButtonExists('Save Draft') ? true : false;
            $Preview = $this->Form->ButtonExists('Preview') ? true : false;
            if (!$Preview) {
                if (!is_object($this->Category) && is_array($CategoryData) && isset($FormValues['CategoryID'])) {
                    $this->Category = val($FormValues['CategoryID'], $CategoryData);
                }

                if (is_object($this->Category)) {
                    // Check category permissions.
                    if ($this->Form->getFormValue('Announce', '') && !$Session->checkPermission('Vanilla.Discussions.Announce', true, 'Category', $this->Category->PermissionCategoryID)) {
                        $this->Form->addError('You do not have permission to announce in this category', 'Announce');
                    }

                    if ($this->Form->getFormValue('Close', '') && !$Session->checkPermission('Vanilla.Discussions.Close', true, 'Category', $this->Category->PermissionCategoryID)) {
                        $this->Form->addError('You do not have permission to close in this category', 'Close');
                    }

                    if ($this->Form->getFormValue('Sink', '') && !$Session->checkPermission('Vanilla.Discussions.Sink', true, 'Category', $this->Category->PermissionCategoryID)) {
                        $this->Form->addError('You do not have permission to sink in this category', 'Sink');
                    }

                    if (!isset($this->Discussion) && (!$Session->checkPermission('Vanilla.Discussions.Add', true, 'Category', $this->Category->PermissionCategoryID) || !$this->Category->AllowDiscussions)) {
                        $this->Form->addError('You do not have permission to start discussions in this category', 'CategoryID');
                    }
                }

                // Make sure that the title will not be invisible after rendering
                $Name = trim($this->Form->getFormValue('Name', ''));
                if ($Name != '' && Gdn_Format::text($Name) == '') {
                    $this->Form->addError(t('You have entered an invalid discussion title'), 'Name');
                } else {
                    // Trim the name.
                    $FormValues['Name'] = $Name;
                    $this->Form->setFormValue('Name', $Name);
                }

                if ($this->Form->errorCount() == 0) {
                    if ($Draft) {
                        $DraftID = $this->DraftModel->save($FormValues);
                        $this->Form->setValidationResults($this->DraftModel->validationResults());
                    } else {
                        $DiscussionID = $this->DiscussionModel->save($FormValues, $this->CommentModel);
                        $this->Form->setValidationResults($this->DiscussionModel->validationResults());

                        if ($DiscussionID > 0) {
                            if ($DraftID > 0) {
                                $this->DraftModel->delete($DraftID);
                            }
                        }
                        if ($DiscussionID == SPAM || $DiscussionID == UNAPPROVED) {
                            $this->StatusMessage = t('DiscussionRequiresApprovalStatus', 'Your discussion will appear after it is approved.');
                            $this->render('Spam');
                            return;
                        }
                    }
                }
            } else {
                // If this was a preview click, create a discussion/comment shell with the values for this comment
                $this->Discussion = new stdClass();
                $this->Discussion->Name = $this->Form->getValue('Name', '');
                $this->Comment = new stdClass();
                $this->Comment->InsertUserID = $Session->User->UserID;
                $this->Comment->InsertName = $Session->User->Name;
                $this->Comment->InsertPhoto = $Session->User->Photo;
                $this->Comment->DateInserted = Gdn_Format::date();
                $this->Comment->Body = arrayValue('Body', $FormValues, '');
                $this->Comment->Format = val('Format', $FormValues, c('Garden.InputFormatter'));

                $this->EventArguments['Discussion'] = &$this->Discussion;
                $this->EventArguments['Comment'] = &$this->Comment;
                $this->fireEvent('BeforeDiscussionPreview');

                if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
                    $this->AddAsset('Content', $this->fetchView('preview'));
                } else {
                    $this->View = 'preview';
                }
            }
            if ($this->Form->errorCount() > 0) {
                // Return the form errors
                $this->ErrorMessage($this->Form->errors());
            } elseif ($DiscussionID > 0 || $DraftID > 0) {
                // Make sure that the ajax request form knows about the newly created discussion or draft id
                $this->setJson('DiscussionID', $DiscussionID);
                $this->setJson('DraftID', $DraftID);

                if (!$Preview) {
                    // If the discussion was not a draft
                    if (!$Draft) {
                        // Redirect to the new discussion
                        $Discussion = $this->DiscussionModel->getID($DiscussionID, DATASET_TYPE_OBJECT, array('Slave' => false));
                        $this->setData('Discussion', $Discussion);
                        $this->EventArguments['Discussion'] = $Discussion;
                        $this->fireEvent('AfterDiscussionSave');

                        if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
                            redirect(DiscussionUrl($Discussion)).'?new=1';
                        } else {
                            $this->RedirectUrl = DiscussionUrl($Discussion, '', true).'?new=1';
                        }
                    } else {
                        // If this was a draft save, notify the user about the save
                        $this->informMessage(sprintf(t('Draft saved at %s'), Gdn_Format::date()));
                    }
                }
            }
        }

        // Add hidden fields for editing
        $this->Form->addHidden('DiscussionID', $DiscussionID);
        $this->Form->addHidden('DraftID', $DraftID, true);

        $this->fireEvent('BeforeDiscussionRender');

        if ($this->CategoryID) {
            $Breadcrumbs = CategoryModel::GetAncestors($this->CategoryID);
        } else {
            $Breadcrumbs = array();
        }
        $Breadcrumbs[] = array('Name' => $this->data('Title'), 'Url' => '/post/discussion');

        $this->setData('Breadcrumbs', $Breadcrumbs);

        $this->setData('_AnnounceOptions', $this->AnnounceOptions());

        // Render view (posts/discussion.php or post/preview.php)
        $this->render();
    }

    /**
     * Edit a discussion (wrapper for PostController::Discussion).
     *
     * Will throw an error if both params are blank.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $DiscussionID Unique ID of the discussion to edit.
     * @param int $DraftID Unique ID of draft discussion to edit.
     */
    public function editDiscussion($DiscussionID = '', $DraftID = '') {
        if ($DraftID != '') {
            $this->Draft = $this->DraftModel->getID($DraftID);
            $this->CategoryID = $this->Draft->CategoryID;

            // Verify this is their draft
            if (val('InsertUserID', $this->Draft) != Gdn::session()->UserID) {
                throw permissionException();
            }
        } else {
            $this->setData('Discussion', $this->DiscussionModel->getID($DiscussionID), true);
            $this->CategoryID = $this->Discussion->CategoryID;
        }

        if (c('Garden.ForceInputFormatter')) {
            $this->Form->removeFormValue('Format');
        }

        // Set view and render
        $this->View = 'Discussion';
        $this->Discussion($this->CategoryID);
    }

    /**
     * Create or update a comment.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $DiscussionID Unique ID to add the comment to. If blank, this method will throw an error.
     */
    public function comment($DiscussionID = '') {
        // Get $DiscussionID from RequestArgs if valid
        if ($DiscussionID == '' && count($this->RequestArgs)) {
            if (is_numeric($this->RequestArgs[0])) {
                $DiscussionID = $this->RequestArgs[0];
            }
        }

        // If invalid $DiscussionID, get from form.
        $this->Form->setModel($this->CommentModel);
        $DiscussionID = is_numeric($DiscussionID) ? $DiscussionID : $this->Form->getFormValue('DiscussionID', 0);

        // Set discussion data
        $this->DiscussionID = $DiscussionID;
        $this->Discussion = $Discussion = $this->DiscussionModel->getID($DiscussionID);

        // Is this an embedded comment being posted to a discussion that doesn't exist yet?
        $vanilla_type = $this->Form->getFormValue('vanilla_type', '');
        $vanilla_url = $this->Form->getFormValue('vanilla_url', '');
        $vanilla_category_id = $this->Form->getFormValue('vanilla_category_id', '');
        $Attributes = array('ForeignUrl' => $vanilla_url);
        $vanilla_identifier = $this->Form->getFormValue('vanilla_identifier', '');

        // Only allow vanilla identifiers of 32 chars or less - md5 if larger
        if (strlen($vanilla_identifier) > 32) {
            $Attributes['vanilla_identifier'] = $vanilla_identifier;
            $vanilla_identifier = md5($vanilla_identifier);
        }

        if (!$Discussion && $vanilla_url != '' && $vanilla_identifier != '') {
            $Discussion = $Discussion = $this->DiscussionModel->GetForeignID($vanilla_identifier, $vanilla_type);

            if ($Discussion) {
                $this->DiscussionID = $DiscussionID = $Discussion->DiscussionID;
                $this->Form->setValue('DiscussionID', $DiscussionID);
            }
        }

        // If so, create it!
        if (!$Discussion && $vanilla_url != '' && $vanilla_identifier != '') {
            // Add these values back to the form if they exist!
            $this->Form->addHidden('vanilla_identifier', $vanilla_identifier);
            $this->Form->addHidden('vanilla_type', $vanilla_type);
            $this->Form->addHidden('vanilla_url', $vanilla_url);
            $this->Form->addHidden('vanilla_category_id', $vanilla_category_id);

            $PageInfo = FetchPageInfo($vanilla_url);

            if (!($Title = $this->Form->getFormValue('Name'))) {
                $Title = val('Title', $PageInfo, '');
                if ($Title == '') {
                    $Title = t('Undefined discussion subject.');
                }
            }

            $Description = val('Description', $PageInfo, '');
            $Images = val('Images', $PageInfo, array());
            $LinkText = t('EmbededDiscussionLinkText', 'Read the full story here');

            if (!$Description && count($Images) == 0) {
                $Body = formatString(
                    '<p><a href="{Url}">{LinkText}</a></p>',
                    array('Url' => $vanilla_url, 'LinkText' => $LinkText)
                );
            } else {
                $Body = formatString('
            <div class="EmbeddedContent">{Image}<strong>{Title}</strong>
               <p>{Excerpt}</p>
               <p><a href="{Url}">{LinkText}</a></p>
               <div class="ClearFix"></div>
            </div>', array(
                    'Title' => $Title,
                    'Excerpt' => $Description,
                    'Image' => (count($Images) > 0 ? img(val(0, $Images), array('class' => 'LeftAlign')) : ''),
                    'Url' => $vanilla_url,
                    'LinkText' => $LinkText
                ));
            }

            if ($Body == '') {
                $Body = $vanilla_url;
            }
            if ($Body == '') {
                $Body = t('Undefined discussion body.');
            }

            // Validate the CategoryID for inserting.
            $Category = CategoryModel::categories($vanilla_category_id);
            if (!$Category) {
                $vanilla_category_id = c('Vanilla.Embed.DefaultCategoryID', 0);
                if ($vanilla_category_id <= 0) {
                    // No default category defined, so grab the first non-root category and use that.
                    $vanilla_category_id = $this->DiscussionModel
                        ->SQL
                        ->select('CategoryID')
                        ->from('Category')
                        ->where('CategoryID >', 0)
                        ->get()
                        ->firstRow()
                        ->CategoryID;
                    // No categories in the db? default to 0
                    if (!$vanilla_category_id) {
                        $vanilla_category_id = 0;
                    }
                }
            } else {
                $vanilla_category_id = $Category['CategoryID'];
            }

            $EmbedUserID = c('Garden.Embed.UserID');
            if ($EmbedUserID) {
                $EmbedUser = Gdn::userModel()->getID($EmbedUserID);
            }
            if (!$EmbedUserID || !$EmbedUser) {
                $EmbedUserID = Gdn::userModel()->GetSystemUserID();
            }

            $EmbeddedDiscussionData = array(
                'InsertUserID' => $EmbedUserID,
                'DateInserted' => Gdn_Format::toDateTime(),
                'DateUpdated' => Gdn_Format::toDateTime(),
                'CategoryID' => $vanilla_category_id,
                'ForeignID' => $vanilla_identifier,
                'Type' => $vanilla_type,
                'Name' => $Title,
                'Body' => $Body,
                'Format' => 'Html',
                'Attributes' => serialize($Attributes)
            );
            $this->EventArguments['Discussion'] =& $EmbeddedDiscussionData;
            $this->fireEvent('BeforeEmbedDiscussion');
            $DiscussionID = $this->DiscussionModel->SQL->insert(
                'Discussion',
                $EmbeddedDiscussionData
            );
            $ValidationResults = $this->DiscussionModel->validationResults();
            if (count($ValidationResults) == 0 && $DiscussionID > 0) {
                $this->Form->addHidden('DiscussionID', $DiscussionID); // Put this in the form so reposts won't cause new discussions.
                $this->Form->setFormValue('DiscussionID', $DiscussionID); // Put this in the form values so it is used when saving comments.
                $this->setJson('DiscussionID', $DiscussionID);
                $this->Discussion = $Discussion = $this->DiscussionModel->getID($DiscussionID, DATASET_TYPE_OBJECT, array('Slave' => false));
                // Update the category discussion count
                if ($vanilla_category_id > 0) {
                    $this->DiscussionModel->UpdateDiscussionCount($vanilla_category_id, $DiscussionID);
                }

            }
        }

        // If no discussion was found, error out
        if (!$Discussion) {
            $this->Form->addError(t('Failed to find discussion for commenting.'));
        }

        $PermissionCategoryID = val('PermissionCategoryID', $Discussion);

        // Setup head
        $this->addJsFile('jquery.autosize.min.js');
        $this->addJsFile('autosave.js');
        $this->addJsFile('post.js');

        // Setup comment model, $CommentID, $DraftID
        $Session = Gdn::session();
        $CommentID = isset($this->Comment) && property_exists($this->Comment, 'CommentID') ? $this->Comment->CommentID : '';
        $DraftID = isset($this->Comment) && property_exists($this->Comment, 'DraftID') ? $this->Comment->DraftID : '';
        $this->EventArguments['CommentID'] = $CommentID;
        $this->EventArguments['DraftID'] = $DraftID;

        // Determine whether we are editing
        $Editing = $CommentID > 0 || $DraftID > 0;
        $this->EventArguments['Editing'] = $Editing;

        // If closed, cancel & go to discussion
        if ($Discussion && $Discussion->Closed == 1 && !$Editing && !$Session->checkPermission('Vanilla.Discussions.Close', true, 'Category', $PermissionCategoryID)) {
            redirect(DiscussionUrl($Discussion));
        }

        // Add hidden IDs to form
        $this->Form->addHidden('DiscussionID', $DiscussionID);
        $this->Form->addHidden('CommentID', $CommentID);
        $this->Form->addHidden('DraftID', $DraftID, true);

        // Check permissions
        if ($Discussion && $Editing) {
            // Permission to edit
            if ($this->Comment->InsertUserID != $Session->UserID) {
                $this->permission('Vanilla.Comments.Edit', true, 'Category', $Discussion->PermissionCategoryID);
            }

            // Make sure that content can (still) be edited.
            $EditContentTimeout = c('Garden.EditContentTimeout', -1);
            $CanEdit = $EditContentTimeout == -1 || strtotime($this->Comment->DateInserted) + $EditContentTimeout > time();
            if (!$CanEdit) {
                $this->permission('Vanilla.Comments.Edit', true, 'Category', $Discussion->PermissionCategoryID);
            }

            // Make sure only moderators can edit closed things
            if ($Discussion->Closed) {
                $this->permission('Vanilla.Comments.Edit', true, 'Category', $Discussion->PermissionCategoryID);
            }

            $this->Form->setFormValue('CommentID', $CommentID);
        } elseif ($Discussion) {
            // Permission to add
            $this->permission('Vanilla.Comments.Add', true, 'Category', $Discussion->PermissionCategoryID);
        }

        if ($this->Form->authenticatedPostBack()) {
            // Save as a draft?
            $FormValues = $this->Form->formValues();
            $FormValues = $this->CommentModel->filterForm($FormValues);

            if (!$Editing) {
                unset($FormValues['CommentID']);
            }

            if ($DraftID == 0) {
                $DraftID = $this->Form->getFormValue('DraftID', 0);
            }

            $Type = GetIncomingValue('Type');
            $Draft = $Type == 'Draft';
            $this->EventArguments['Draft'] = $Draft;
            $Preview = $Type == 'Preview';
            if ($Draft) {
                $DraftID = $this->DraftModel->save($FormValues);
                $this->Form->addHidden('DraftID', $DraftID, true);
                $this->Form->setValidationResults($this->DraftModel->validationResults());
            } elseif (!$Preview) {
                // Fix an undefined title if we can.
                if ($this->Form->getFormValue('Name') && val('Name', $Discussion) == t('Undefined discussion subject.')) {
                    $Set = array('Name' => $this->Form->getFormValue('Name'));

                    if (isset($vanilla_url) && $vanilla_url && strpos(val('Body', $Discussion), t('Undefined discussion subject.')) !== false) {
                        $LinkText = t('EmbededDiscussionLinkText', 'Read the full story here');
                        $Set['Body'] = formatString(
                            '<p><a href="{Url}">{LinkText}</a></p>',
                            array('Url' => $vanilla_url, 'LinkText' => $LinkText)
                        );
                    }

                    $this->DiscussionModel->setField(val('DiscussionID', $Discussion), $Set);
                }

                $Inserted = !$CommentID;
                $CommentID = $this->CommentModel->save($FormValues);

                // The comment is now half-saved.
                if (is_numeric($CommentID) && $CommentID > 0) {
                    if (in_array($this->deliveryType(), array(DELIVERY_TYPE_ALL, DELIVERY_TYPE_DATA))) {
                        $this->CommentModel->Save2($CommentID, $Inserted, true, true);
                    } else {
                        $this->jsonTarget('', url("/post/comment2.json?commentid=$CommentID&inserted=$Inserted"), 'Ajax');
                    }

                    // $Discussion = $this->DiscussionModel->getID($DiscussionID);
                    $Comment = $this->CommentModel->getID($CommentID, DATASET_TYPE_OBJECT, array('Slave' => false));

                    $this->EventArguments['Discussion'] = $Discussion;
                    $this->EventArguments['Comment'] = $Comment;
                    $this->fireEvent('AfterCommentSave');
                } elseif ($CommentID === SPAM || $CommentID === UNAPPROVED) {
                    $this->StatusMessage = t('CommentRequiresApprovalStatus', 'Your comment will appear after it is approved.');
                }

                $this->Form->setValidationResults($this->CommentModel->validationResults());
                if ($CommentID > 0 && $DraftID > 0) {
                    $this->DraftModel->delete($DraftID);
                }
            }

            // Handle non-ajax requests first:
            if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
                if ($this->Form->errorCount() == 0) {
                    // Make sure that this form knows what comment we are editing.
                    if ($CommentID > 0) {
                        $this->Form->addHidden('CommentID', $CommentID);
                    }

                    // If the comment was not a draft
                    if (!$Draft) {
                        // Redirect to the new comment.
                        if ($CommentID > 0) {
                            redirect("discussion/comment/$CommentID/#Comment_$CommentID");
                        } elseif ($CommentID == SPAM) {
                            $this->setData('DiscussionUrl', DiscussionUrl($Discussion));
                            $this->View = 'Spam';

                        }
                    } elseif ($Preview) {
                        // If this was a preview click, create a comment shell with the values for this comment
                        $this->Comment = new stdClass();
                        $this->Comment->InsertUserID = $Session->User->UserID;
                        $this->Comment->InsertName = $Session->User->Name;
                        $this->Comment->InsertPhoto = $Session->User->Photo;
                        $this->Comment->DateInserted = Gdn_Format::date();
                        $this->Comment->Body = arrayValue('Body', $FormValues, '');
                        $this->Comment->Format = val('Format', $FormValues, c('Garden.InputFormatter'));
                        $this->AddAsset('Content', $this->fetchView('preview'));
                    } else {
                        // If this was a draft save, notify the user about the save
                        $this->informMessage(sprintf(t('Draft saved at %s'), Gdn_Format::date()));
                    }
                }
            } else {
                // Handle ajax-based requests
                if ($this->Form->errorCount() > 0) {
                    // Return the form errors
                    $this->ErrorMessage($this->Form->errors());
                } else {
                    // Make sure that the ajax request form knows about the newly created comment or draft id
                    $this->setJson('CommentID', $CommentID);
                    $this->setJson('DraftID', $DraftID);

                    if ($Preview) {
                        // If this was a preview click, create a comment shell with the values for this comment
                        $this->Comment = new stdClass();
                        $this->Comment->InsertUserID = $Session->User->UserID;
                        $this->Comment->InsertName = $Session->User->Name;
                        $this->Comment->InsertPhoto = $Session->User->Photo;
                        $this->Comment->DateInserted = Gdn_Format::date();
                        $this->Comment->Body = arrayValue('Body', $FormValues, '');
                        $this->View = 'preview';
                    } elseif (!$Draft) { // If the comment was not a draft
                        // If Editing a comment
                        if ($Editing) {
                            // Just reload the comment in question
                            $this->Offset = 1;
                            $Comments = $this->CommentModel->GetIDData($CommentID, array('Slave' => false));
                            $this->setData('Comments', $Comments);
                            $this->setData('Discussion', $Discussion);
                            // Load the discussion
                            $this->ControllerName = 'discussion';
                            $this->View = 'comments';

                            // Also define the discussion url in case this request came from the post screen and needs to be redirected to the discussion
                            $this->setJson('DiscussionUrl', DiscussionUrl($this->Discussion).'#Comment_'.$CommentID);
                        } else {
                            // If the comment model isn't sorted by DateInserted or CommentID then we can't do any fancy loading of comments.
                            $OrderBy = valr('0.0', $this->CommentModel->orderBy());
//                     $Redirect = !in_array($OrderBy, array('c.DateInserted', 'c.CommentID'));
//							$DisplayNewCommentOnly = $this->Form->getFormValue('DisplayNewCommentOnly');

//                     if (!$Redirect) {
//                        // Otherwise load all new comments that the user hasn't seen yet
//                        $LastCommentID = $this->Form->getFormValue('LastCommentID');
//                        if (!is_numeric($LastCommentID))
//                           $LastCommentID = $CommentID - 1; // Failsafe back to this new comment if the lastcommentid was not defined properly
//
//                        // Don't reload the first comment if this new comment is the first one.
//                        $this->Offset = $LastCommentID == 0 ? 1 : $this->CommentModel->GetOffset($LastCommentID);
//                        // Do not load more than a single page of data...
//                        $Limit = c('Vanilla.Comments.PerPage', 30);
//
//                        // Redirect if the new new comment isn't on the same page.
//                        $Redirect |= !$DisplayNewCommentOnly && PageNumber($this->Offset, $Limit) != PageNumber($Discussion->CountComments - 1, $Limit);
//                     }

//                     if ($Redirect) {
//                        // The user posted a comment on a page other than the last one, so just redirect to the last page.
//                        $this->RedirectUrl = Gdn::request()->Url("discussion/comment/$CommentID/#Comment_$CommentID", true);
//                     } else {
//                        // Make sure to load all new comments since the page was last loaded by this user
//								if ($DisplayNewCommentOnly)
                            $this->Offset = $this->CommentModel->GetOffset($CommentID);
                            $Comments = $this->CommentModel->GetIDData($CommentID, array('Slave' => false));
                            $this->setData('Comments', $Comments);

                            $this->setData('NewComments', true);

                            $this->ClassName = 'DiscussionController';
                            $this->ControllerName = 'discussion';
                            $this->View = 'comments';
//                     }

                            // Make sure to set the user's discussion watch records
                            $CountComments = $this->CommentModel->getCount($DiscussionID);
                            $Limit = is_object($this->data('Comments')) ? $this->data('Comments')->numRows() : $Discussion->CountComments;
                            $Offset = $CountComments - $Limit;
                            $this->CommentModel->SetWatch($this->Discussion, $Limit, $Offset, $CountComments);
                        }
                    } else {
                        // If this was a draft save, notify the user about the save
                        $this->informMessage(sprintf(t('Draft saved at %s'), Gdn_Format::date()));
                    }
                    // And update the draft count
                    $UserModel = Gdn::userModel();
                    $CountDrafts = $UserModel->getAttribute($Session->UserID, 'CountDrafts', 0);
                    $this->setJson('MyDrafts', t('My Drafts'));
                    $this->setJson('CountDrafts', $CountDrafts);
                }
            }
        } elseif ($this->Request->isPostBack()) {
            throw new Gdn_UserException('Invalid CSRF token.', 401);
        } else {
            // Load form
            if (isset($this->Comment)) {
                $this->Form->setData((array)$this->Comment);
            }
        }

        // Include data for FireEvent
        if (property_exists($this, 'Discussion')) {
            $this->EventArguments['Discussion'] = $this->Discussion;
        }
        if (property_exists($this, 'Comment')) {
            $this->EventArguments['Comment'] = $this->Comment;
        }

        $this->fireEvent('BeforeCommentRender');

        if ($this->deliveryType() == DELIVERY_TYPE_DATA) {
            $Comment = $this->data('Comments')->firstRow(DATASET_TYPE_ARRAY);
            if ($Comment) {
                $Photo = $Comment['InsertPhoto'];

                if (strpos($Photo, '//') === false) {
                    $Photo = Gdn_Upload::url(changeBasename($Photo, 'n%s'));
                }

                $Comment['InsertPhoto'] = $Photo;
            }
            $this->Data = array('Comment' => $Comment);
            $this->RenderData($this->Data);
        } else {
            require_once $this->fetchViewLocation('helper_functions', 'Discussion');
            // Render default view.
            $this->render();
        }
    }

    /**
     * Triggers saving the extra info about a comment
     * like notifications and unread totals.
     *
     * @since 2.0.?
     * @access public
     *
     * @param int $CommentID Unique ID of the comment.
     * @param bool $Inserted
     */
    public function comment2($CommentID, $Inserted = false) {
        $this->CommentModel->Save2($CommentID, $Inserted);
        $this->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     * Edit a comment (wrapper for PostController::Comment).
     *
     * Will throw an error if both params are blank.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $CommentID Unique ID of the comment to edit.
     * @param int $DraftID Unique ID of the draft to edit.
     */
    public function editComment($CommentID = '', $DraftID = '') {
        if (is_numeric($CommentID) && $CommentID > 0) {
            $this->Form->setModel($this->CommentModel);
            $this->Comment = $this->CommentModel->getID($CommentID);
        } else {
            $this->Form->setModel($this->DraftModel);
            $this->Comment = $this->DraftModel->getID($DraftID);
        }

        if (c('Garden.ForceInputFormatter')) {
            $this->Form->removeFormValue('Format');
        }

        $this->View = 'editcomment';
        $this->Comment($this->Comment->DiscussionID);
    }

    /**
     * Include CSS for all methods.
     *
     * Always called by dispatcher before controller's requested method.
     *
     * @since 2.0.0
     * @access public
     */
    public function initialize() {
        parent::initialize();
        $this->addModule('NewDiscussionModule');
    }

    public function notifyNewDiscussion($DiscussionID) {
        if (!c('Vanilla.QueueNotifications')) {
            throw forbiddenException('NotifyNewDiscussion');
        }

        if (!$this->Request->isPostBack()) {
            throw forbiddenException('GET');
        }

        // Grab the discussion.
        $Discussion = $this->DiscussionModel->getID($DiscussionID);
        if (!$Discussion) {
            throw notFoundException('Discussion');
        }

        if (val('Notified', $Discussion) != ActivityModel::SENT_PENDING) {
            die('Not pending');
        }

        // Mark the notification as in progress.
        $this->DiscussionModel->setField($DiscussionID, 'Notified', ActivityModel::SENT_INPROGRESS);

        $HeadlineFormat = t($Code, '{ActivityUserID,user} started a new discussion: <a href="{Url,html}">{Data.Name,text}</a>');
        $Category = CategoryModel::categories(val('CategoryID', $Discussion));
        $Activity = array(
            'ActivityType' => 'Discussion',
            'ActivityUserID' => $Discussion->InsertUserID,
            'HeadlineFormat' => $HeadlineFormat,
            'RecordType' => 'Discussion',
            'RecordID' => $DiscussionID,
            'Route' => DiscussionUrl($Discussion),
            'Data' => array(
                'Name' => $Discussion->Name,
                'Category' => val('Name', $Category)
            )
        );

        $ActivityModel = new ActivityModel();
        $this->DiscussionModel->NotifyNewDiscussion($Discussion, $ActivityModel, $Activity);
        $ActivityModel->SaveQueue();
        $this->DiscussionModel->setField($DiscussionID, 'Notified', ActivityModel::SENT_OK);

        die('OK');
    }

    /**
     * Pre-populate the form with values from the query string.
     *
     * @param Gdn_Form $Form
     * @param bool $LimitCategories Whether to turn off the category dropdown if there is only one category to show.
     */
    protected function populateForm($Form) {
        $Get = $this->Request->get();
        $Get = array_change_key_case($Get);
        $Values = arrayTranslate($Get, array('name' => 'Name', 'tags' => 'Tags', 'body' => 'Body'));
        foreach ($Values as $Key => $Value) {
            $Form->setValue($Key, $Value);
        }

        if (isset($Get['category'])) {
            $Category = CategoryModel::categories($Get['category']);
            if ($Category && $Category['PermsDiscussionsAdd']) {
                $Form->setValue('CategoryID', $Category['CategoryID']);
            }
        }
    }
}

function checkOrRadio($FieldName, $LabelCode, $ListOptions, $Attributes = array()) {
    $Form = Gdn::controller()->Form;

    if (count($ListOptions) == 2 && array_key_exists(0, $ListOptions)) {
        unset($ListOptions[0]);
        $Value = array_pop(array_keys($ListOptions));

        // This can be represented by a checkbox.
        return $Form->CheckBox($FieldName, $LabelCode);
    } else {
        $CssClass = val('ListClass', $Attributes, 'List Inline');

        $Result = ' <b>'.t($LabelCode)."</b> <ul class=\"$CssClass\">";
        foreach ($ListOptions as $Value => $Code) {
            $Result .= ' <li>'.$Form->Radio($FieldName, $Code, array('Value' => $Value)).'</li> ';
        }
        $Result .= '</ul>';
        return $Result;
    }
}
