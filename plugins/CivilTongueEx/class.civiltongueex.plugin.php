<?php
/**
 * @copyright 2008-2022 Vanilla Forums, Inc.
 * @license GPLv2
 *
 * Based on the Civil Tongue plugin.
 */

// 1.0 - Fix empty pattern when list ends in semi-colon, use non-custom permission (2012-03-12 Lincoln)

use CivilTongueEx\Library\ContentFilter;

/**
 * Class CivilTonguePlugin
 */
class CivilTonguePlugin extends Gdn_Plugin
{
    /** @var mixed  */
    public $Replacement;

    /** @var \CivilTongueEx\Library\ContentFilter */
    protected $contentFilter;

    /**
     * CivilTonguePlugin constructor.
     *
     * @param ContentFilter $contentFilter
     */
    public function __construct(ContentFilter $contentFilter)
    {
        parent::__construct();
        $this->setReplacement(c("Plugins.CivilTongue.Replacement", ""));
        $this->contentFilter = $contentFilter;
    }

    /**
     *
     *
     * @param $sender
     * @param $args
     */
    public function base_filterContent_handler($sender, $args)
    {
        if (!isset($args["String"])) {
            return;
        }

        $args["String"] = $this->replace($args["String"]);
    }

    /**
     *
     *
     * @param $sender
     * @param array $args
     * @throws Gdn_UserException
     */
    public function pluginController_tongue_create($sender, $args = [])
    {
        $sender->permission("Garden.Settings.Manage");
        $sender->Form = new Gdn_Form();
        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->setField(["Plugins.CivilTongue.Words", "Plugins.CivilTongue.Replacement"]);
        $sender->Form->setModel($configurationModel);

        if ($sender->Form->authenticatedPostBack() === false) {
            $sender->Form->setData($configurationModel->Data);
        } else {
            $data = $sender->Form->formValues();

            if ($sender->Form->save() !== false) {
                $sender->StatusMessage = t("Your settings have been saved.");
            }
        }

        $sender->addSideMenu("plugin/tongue");
        $sender->setData("Title", t("Civil Tongue"));
        $sender->render($this->getView("index.php"));
    }

    /**
     * Censor words in /discussions
     *
     * @param DiscussionsController $sender
     */
    public function discussionsController_render_before($sender)
    {
        $discussions = $sender->data("Discussions", []);
        if (is_array($discussions) || $discussions instanceof \Traversable) {
            foreach ($discussions as &$discussion) {
                $discussion->Name = $this->replace($discussion->Name);
                $discussion->Body = $this->replace($discussion->Body);
                $discussion->Category = $this->replace($discussion->Category);
            }
        }
    }

    /**
     * Clean up the search results.
     *
     * @param SearchController $sender
     */
    public function searchController_render_before($sender)
    {
        if (isset($sender->Data["SearchResults"])) {
            $results = &$sender->Data["SearchResults"];
            foreach ($results as &$row) {
                $row["Title"] = $this->replace($row["Title"]);
                $row["Summary"] = $this->replace($row["Summary"]);
            }
        }
    }

    /**
     * Clean up the search results.
     *
     * @param RootController $sender
     */
    public function rootController_bestOf_render($sender)
    {
        if (isset($sender->Data["Data"])) {
            foreach ($sender->Data["Data"] as &$row) {
                $row["Name"] = $this->replace($row["Name"]);
                $row["Body"] = $this->replace($row["Body"]);
            }
        }
    }

    /**
     * Replace black listed words according to pattern
     *
     * @param string $text
     * @return ?string
     */
    public function replace($text = ""): ?string
    {
        return $this->contentFilter->replace($text);
    }

    /**
     * Get word patterns.
     *
     * @return array
     */
    public function getpatterns(): array
    {
        return $this->contentFilter->getPatterns();
    }

    /**
     *
     */
    public function setup()
    {
        // Set default configuration
        saveToConfig("Plugins.CivilTongue.Replacement", "****");
    }

    /**
     * Cleanup Emails.
     *
     * @param Gdn_Email $sender
     */
    public function gdn_email_beforeSendMail_handler($sender)
    {
        $sender->getMailer()->setSubject($this->replace($sender->getMailer()->getSubject()), false);
        $sender->getMailer()->setBodyContent($this->replace($sender->getMailer()->getBodyContent()));
        $sender->getMailer()->setTextContent($this->replace($sender->getMailer()->getTextContent()));
    }

    /**
     * Cleanup Activity messages.
     *
     * @param ActivityModel $sender
     * @param array $args
     */
    public function activityModel_beforeSave_handler($sender, $args)
    {
        $activity = val("Activity", $args);
        setValue("Story", $activity, $this->replace(val("Story", $activity)));
        $sender->EventArguments["Activity"] = $activity;
    }

    /**
     * Cleanup private messages displayed on the messages page.
     *
     * @param $sender
     * @param $args
     */
    public function messagesController_beforeMessages_handler($sender, $args)
    {
        foreach ($args["MessageData"] as &$message) {
            $body = val("Body", $message);
            if ($body) {
                $message->Body = $this->replace($body);
            }
        }
    }

    /**
     * Cleanup private messages displayed on the messages page.
     *
     * @param $sender
     * @param $args
     */
    public function messagesController_beforeMessagesAll_handler($sender, $args)
    {
        $conversations = val("Conversations", $args);
        foreach ($conversations as $key => &$conversation) {
            if (val("LastBody", $conversation)) {
                $conversation["LastBody"] = $this->replace($conversation["LastBody"]);
                $args["Conversations"][$key]["LastBody"] = $this->replace($args["Conversations"][$key]["LastBody"]);
            }
        }
    }

    /**
     * Filter content in conversation notifications.
     *
     * @param ConversationModel $sender The sending object.
     * @param array $args
     */
    public function conversationModel_afterAdd_handler($sender, $args)
    {
        if (val("Body", $args)) {
            $args["Body"] = $this->replace($args["Body"]);
        }
        if (val("Subject", $args)) {
            $args["Subject"] = $this->replace($args["Subject"]);
        }
    }

    /**
     * Filter content in converation message notifications.
     *
     * @param ConversationMessageModel $sender The sending object.
     * @param array $args
     */
    public function conversationMessageModel_afterAdd_handler($sender, $args)
    {
        if (val("Body", $args)) {
            $args["Body"] = $this->replace($args["Body"]);
        }
        if (val("Subject", $args)) {
            $args["Subject"] = $this->replace($args["Subject"]);
        }
    }

    /**
     * This view gets loaded in via ajax. We need to filter with an event before it's rendered.
     *
     * @param PollModule $sender Poll Module.
     * @param array $args Sending arguments.
     * @psalm-suppress UndefinedDocblockClass
     */
    public function pollModule_afterLoadPoll_handler($sender, $args)
    {
        if ($options = val("PollOptions", $args)) {
            foreach ($options as &$option) {
                $option["Body"] = $this->replace($option["Body"]);
            }
            $args["PollOptions"] = $options;
        }
        if ($name = val("Name", val("Poll", $args))) {
            $args["Poll"]->Name = $this->replace($name);
        }
    }

    /**
     * Replace bad words in the group list
     *
     * Vanilla's proprietary group plugin hook.
     *
     * @param SettingsController $sender Sending controller instance
     * @param array $args Event's arguments
     */
    public function groupsController_beforeGroupLists_handler($sender, $args)
    {
        $sections = ["MyGroups", "NewGroups", "Groups"];

        foreach ($sections as $section) {
            $groups = $sender->data($section);
            if ($groups) {
                foreach ($groups as &$group) {
                    $group["Name"] = $this->replace($group["Name"]);
                    $group["Description"] = $this->replace($group["Description"]);
                }
                $sender->setData($section, $groups);
            }
        }
    }

    /**
     * Replace bad words in the group browsing list
     *
     * Vanilla's proprietary group plugin hook.
     *
     * @param SettingsController $sender Sending controller instance
     * @param array $args Event's arguments
     */
    public function groupsController_beforeBrowseGroupList_handler($sender, $args)
    {
        $groups = $sender->data("Groups");
        if ($groups) {
            foreach ($groups as &$group) {
                $group["Name"] = $this->replace($group["Name"]);
                $group["Description"] = $this->replace($group["Description"]);
            }
            $sender->setData("Groups", $groups);
        }
    }

    /**
     * Replace bad words in the group view and the events list
     *
     * Vanilla's proprietary group plugin hook.
     *
     * @param SettingsController $sender Sending controller instance
     * @param array $args Event's arguments
     */
    public function base_groupLoaded_handler($sender, $args)
    {
        $args["Group"]["Name"] = $this->replace($args["Group"]["Name"]);
        $args["Group"]["Description"] = $this->replace($args["Group"]["Description"]);
    }

    /**
     * Replace bad words in the event list of a group
     *
     * Vanilla's proprietary group plugin hook.
     *
     * @param SettingsController $sender Sending controller instance
     * @param array $args Event's arguments
     */
    public function groupController_groupEventsLoaded_handler($sender, $args)
    {
        $events = &$args["Events"];
        foreach ($events as &$event) {
            $event["Name"] = $this->replace($event["Name"]);
            $event["Body"] = $this->replace($event["Body"]);
            $event["Location"] = $this->replace($event["Location"]);
        }
    }

    /**
     * Replace bad words in the events list
     *
     * Vanilla's proprietary group plugin hook.
     *
     * @param SettingsController $sender Sending controller instance
     * @param array $args Event's arguments
     */
    public function eventsController_eventsLoaded_handler($sender, $args)
    {
        $sections = ["UpcomingEvents", "RecentEvents"];

        foreach ($sections as $section) {
            $events = &$args[$section];
            foreach ($events as &$event) {
                $event["Name"] = $this->replace($event["Name"]);
                $event["Body"] = $this->replace($event["Body"]);
                $event["Location"] = $this->replace($event["Location"]);
            }
            unset($events, $event);
        }
    }

    /**
     * Replace bad words in the event view
     *
     * Vanilla's proprietary group plugin hook.
     *
     * @param SettingsController $sender Sending controller instance
     * @param array $args Event's arguments
     */
    public function eventController_eventLoaded_handler($sender, $args)
    {
        $args["Event"]["Name"] = $this->replace($args["Event"]["Name"]);
        $args["Event"]["Body"] = $this->replace($args["Event"]["Body"]);
        $args["Event"]["Location"] = $this->replace($args["Event"]["Location"]);

        if (isset($args["Group"])) {
            $args["Group"]["Name"] = $this->replace($args["Group"]["Name"]);
            $args["Group"]["Description"] = $this->replace($args["Group"]["Description"]);
        }
    }

    /**
     * Replace dirty words in meta tags, so they won't appear when reposted to facebook, twitter, et al.
     *
     * @param HeadModule $sender The head module.
     */
    public function headModule_beforeToString_handler($sender)
    {
        $tags = $sender->getTags();
        $newTags = [];
        foreach ($tags as $tag) {
            if ($tag["_tag"] === "meta") {
                setValue("content", $tag, $this->replace($tag["content"]));
            }
            $newTags[] = $tag;
        }
        $sender->tags($newTags);
    }

    /**
     * Get the replacement string.
     *
     * @return string
     */
    public function getReplacement(): string
    {
        return $this->Replacement;
    }

    /**
     * Set the replacement string.
     *
     * @param mixed $replacement
     */
    public function setReplacement($replacement): void
    {
        $this->Replacement = $replacement;
    }
}
