<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Forum\Addon;

use Garden\PsrEventHandlersInterface;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Community\Events\DiscussionStatusEvent;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\Models\RecordStatusModel;
use DiscussionModel;
use Gdn_Request;
use DiscussionStatusModel;

/**
 * Legacy event handlers for the resolved.
 *
 * This code is for the legacy interface. The interface revolves around the "triage" functionality.
 */
class LegacyResolvedEventHandlers implements PsrEventHandlersInterface
{
    /**
     * DI.
     */
    public function __construct(
        private DiscussionModel $discussionModel,
        private DiscussionStatusModel $discussionStatusModel,
        private ConfigurationInterface $config
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function getPsrEventHandlerMethods(): array
    {
        return ["handleDiscussionStatusEvent"];
    }

    /**
     * Check if our current user has access to "triage" / "resolved" functionality.
     *
     * @return bool
     */
    private function triageEnabled(): bool
    {
        return $this->config->get("triage.enabled", false) && checkPermission("staff.allow");
    }

    /**
     * Generate the option for the option menu.
     *
     * @param array|object $discussion
     * @param string $format Either string or array.
     * @return string|array Generated option.
     */
    private function generateOptionMenuItem($discussion, $format)
    {
        $resolved = $this->isResolved(val("internalStatusID", $discussion));
        $discussionID = val("DiscussionID", $discussion);
        $toggledResolved = $resolved ? 0 : 1;

        $label = t($toggledResolved ? "Resolve" : "Unresolve");
        $url = "/discussion/resolve?discussionID={$discussionID}&resolve={$toggledResolved}";

        if ($format === "string") {
            $option = anchor($label, $url, "ResolveDiscussion Hijack");
        } else {
            $option = [
                "Label" => $label,
                "Url" => $url,
                "Class" => "ResolveDiscussion Hijack",
            ];
        }

        return $option;
    }

    /**
     * Get resolved/unresolved markup.
     *
     * @param array|object $discussion
     * @return string
     */
    private function generateStateIndicator($discussion)
    {
        $name = $this->isResolved(val("internalStatusID", $discussion)) ? "resolved" : "unresolved";

        $markup = '<span title="' . t(ucfirst($name)) . '" class="MItem MItem-Resolved">';
        $markup .= file_get_contents(PATH_ROOT . "/resources/design/{$name}.svg");
        $markup .= "</span>";

        return $markup;
    }

    /**
     * Get the discussion name for the resolved state.
     * Prepend [RESOLVED] to the discussion's name if resolved.
     *
     * @param array|object $discussion The discussion.
     * @return string
     */
    private function getUpdatedDiscussionName($discussion)
    {
        if ($this->triageEnabled()) {
            $newName = $this->generateStateIndicator($discussion) . val("Name", $discussion);
        } else {
            $newName = val("Name", $discussion, "");
        }
        return $newName;
    }

    /**
     * Update the UI.
     *
     * @param $discussion
     */
    private function setJSONTarget($discussion)
    {
        $controller = \Gdn::controller();
        if (!$controller) {
            return;
        }
        // Discussion list.
        $controller->jsonTarget(
            "#Discussion_{$discussion["DiscussionID"]} .MItem-Resolved",
            $this->generateStateIndicator($discussion),
            "ReplaceWith"
        );

        if (c("Resolved2.DiscussionTitle.DisplayResolved")) {
            // Update the discussion title.
            $controller->jsonTarget(".Discussion #Item_0 h1", $this->getUpdatedDiscussionName($discussion));

            // Highlight the discussion title.
            $controller->jsonTarget(".Discussion #Item_0", null, "Highlight");
        }

        // Update the option menu.
        $controller->jsonTarget(
            ".Discussion #Item_0 .OptionsMenu .ResolveDiscussion",
            $this->generateOptionMenuItem($discussion, "string"),
            "ReplaceWith"
        );
    }

    /**
     * Set a discussion's resolved state for save new discussion handler.
     *
     * @param array $discussion
     * @param bool $resolved
     * @return array The resolved discussion.
     */
    private function setResolved(array $discussion, bool $resolved): array
    {
        $resolutionFields = [
            "internalStatusID" => $resolved
                ? RecordStatusModel::DISCUSSION_STATUS_RESOLVED
                : RecordStatusModel::DISCUSSION_STATUS_UNRESOLVED,
            "DateResolved" => $resolved ? CurrentTimeStamp::getMySQL() : null,
            "ResolvedUserID" => $resolved ? \Gdn::session()->UserID : null,
        ];

        // Only set CountResolved if the field is not empty.
        $currentCountResolved = val("CountResolved", $discussion, null);
        if (!empty($currentCountResolved) || in_array($currentCountResolved, [0, "0"], true)) {
            $countResolved = $currentCountResolved + (int) $resolved;
            $resolutionFields["CountResolved"] = $countResolved;
        }

        $discussion = array_merge($discussion, $resolutionFields);

        return $discussion;
    }

    /**
     * Set resolved metric on discussions.
     *
     * @param mixed $sender Event's source.
     * @param array $args Event's arguments.
     */
    public function analyticsTracker_beforeTrackEvent_handler(mixed $sender, $args)
    {
        if (!in_array($args["Collection"], ["post", "post_modify"])) {
            return;
        }

        if (in_array($args["Event"], ["discussion_add", "discussion_edit"])) {
            $discussion = $this->discussionModel->getID($args["Data"]["discussionID"], DATASET_TYPE_ARRAY);

            $dateResolved = $discussion["DateResolved"]
                ? \Vanilla\Analytics\TrackableDateUtils::getDateTime($discussion["DateResolved"])
                : null;
            if ($dateResolved) {
                $timeResolved = $dateResolved["timestamp"] - $args["Data"]["dateInserted"]["timestamp"];
            } else {
                $timeResolved = null;
            }

            $trackUserModel = \Gdn::getContainer()->get(\Vanilla\Analytics\TrackableUserModel::class);

            $resolvedMetric = [
                "resolved" => (int) $this->isResolved($discussion["internalStatusID"]),
                "countResolved" => $discussion["CountResolved"],
                "dateResolved" => $dateResolved,
                "resolvedUser" => $discussion["ResolvedUserID"]
                    ? $trackUserModel->getTrackableUser($discussion["ResolvedUserID"])
                    : null,
                "time" => $timeResolved,
            ];

            $args["Data"]["resolvedMetric"] = $resolvedMetric;
        }
    }

    /**
     * Add resolved/unresolved icon
     *
     * @param \Gdn_Controller $sender Event's source.
     * @param array $args Event's arguments.
     */
    public function base_beforeDiscussionMeta_handler(\Gdn_Controller $sender, $args)
    {
        if (!$this->triageEnabled()) {
            return;
        }

        echo $this->generateStateIndicator($args["Discussion"]);
    }

    /**
     * Allow staff to Resolve via discussion options.
     *
     * @param \Gdn_Controller $sender Sending controller instance.
     * @param array $args Event's arguments.
     */
    public function base_discussionOptions_handler(\Gdn_Controller $sender, $args)
    {
        if (!$this->triageEnabled()) {
            return;
        }

        $discussion = $args["Discussion"];
        $controller = \Gdn::controller();

        // Deal with inconsistencies in how options are passed
        $options = val("Options", $controller);
        if ($options) {
            $options .= wrap($this->generateOptionMenuItem($discussion, "string"), "li", [
                "role" => "presentation",
                "class" => "no-icon",
            ]);
            setValue("Options", $controller, $options);
        } else {
            $args["DiscussionOptions"]["ResolveDiscussion"] = $this->generateOptionMenuItem($discussion, "array");
        }
    }

    /**
     * Set discussion's resolved state when a new comment is made.
     *
     * @param \CommentModel $sender Sending model instance.
     * @param array $args Event's arguments.
     */
    public function commentModel_afterSaveComment_handler(\CommentModel $sender, $args)
    {
        $discussionID = valr("FormPostValues.DiscussionID", $args, null);
        if ($discussionID === null) {
            // Not an initial discussion
            return;
        }
        $discussion = $this->discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);
        $isResolved = $this->isResolved(val("internalStatusID", $discussion));
        if ($isResolved xor checkPermission("Garden.Staff.Allow")) {
            $resolved = checkPermission("Garden.Staff.Allow");
            // Resolve the discussion.
            $discussion = $this->discussionStatusModel->updateDiscussionStatus(
                $discussionID,
                $resolved
                    ? RecordStatusModel::DISCUSSION_STATUS_RESOLVED
                    : RecordStatusModel::DISCUSSION_STATUS_UNRESOLVED
            );

            if ($resolved) {
                $this->setJSONTarget($discussion);
            }
        }
    }

    /**
     * Handles correcting the resolved status of a discussion if a discussion has been transformed into a comment by
     * automated workflow. In these cases, we don't want the discussion to be marked automatically resolved when the
     * comment is added by a user with "Garden.Staff.Allow".
     *
     * @param mixed $sender
     * @param array $args
     */
    public function base_transformDiscussionToComment_handler(mixed $sender, array $args): void
    {
        $destDiscussion = $args["DestinationDiscussion"];
        $savedDiscussion = $this->discussionModel->getID($destDiscussion["DiscussionID"], DATASET_TYPE_ARRAY);
        if ($destDiscussion["internalStatusID"] !== $savedDiscussion["internalStatusID"]) {
            // Resolve the discussion.
            $destDiscussion = $this->discussionStatusModel->updateDiscussionStatus(
                $destDiscussion["DiscussionID"],
                $this->isResolved($destDiscussion["internalStatusID"])
                    ? RecordStatusModel::DISCUSSION_STATUS_RESOLVED
                    : RecordStatusModel::DISCUSSION_STATUS_UNRESOLVED
            );
            $this->setJSONTarget($destDiscussion);
        }
    }

    /**
     * Show [RESOLVED] in discussion title when viewing single.
     */
    public function discussionController_beforeDiscussionOptions_handler()
    {
        if (!$this->triageEnabled()) {
            return;
        }

        $controller = \Gdn::controller();
        $discussion = $controller->data("Discussion");

        if (checkPermission("Garden.Staff.Allow")) {
            $newName = $this->getUpdatedDiscussionName($discussion);
            setValue("displayName", $discussion, $newName);
            $controller->setData("Discussion", $discussion);
        }
    }

    /**
     * Handle discussion option menu Resolve action.
     *
     * @throws \Exception Throws an exception when the discussion is not found, or the request is not a POST
     */
    public function discussionController_resolve_create(\DiscussionController $controller)
    {
        $controller->permission("Garden.Staff.Allow");

        $discussionID = \Gdn::request()->get("discussionID");
        $resolved = \Gdn::request()->get("resolve") ? 1 : 0;

        // Make sure we are posting back.
        if (!\Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new \Exception("Requires POST", 405);
        }

        $discussion = $this->discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);
        if (!$discussion) {
            throw new NotFoundException("Discussion", [
                "discussionID" => $discussionID,
            ]);
        }
        // Resolve the discussion.
        $discussion = $this->discussionStatusModel->updateDiscussionStatus(
            $discussionID,
            $resolved ? RecordStatusModel::DISCUSSION_STATUS_RESOLVED : RecordStatusModel::DISCUSSION_STATUS_UNRESOLVED
        );

        $controller->sendOptions((object) $discussion);
        $this->setJSONTarget($discussion);

        $controller->render("blank", "utility", "dashboard");
    }

    /**
     * Update old status columns
     *
     * @param DiscussionStatusEvent $event
     *
     * @return DiscussionStatusEvent event passed in.
     */
    public function handleDiscussionStatusEvent(DiscussionStatusEvent $event): DiscussionStatusEvent
    {
        if ($event->isInternal()) {
            $payload = $event->getPayload();
            $discussion = $payload["discussion"];
            $statusID = $discussion["internalStatusID"] ?? 0;
            $resolved = $statusID == RecordStatusModel::DISCUSSION_STATUS_RESOLVED;
            $resolutionFields = [
                "Resolved" => $resolved ? 1 : 0,
                "DateResolved" => $resolved ? CurrentTimeStamp::getMySQL() : null,
                "ResolvedUserID" => $resolved ? \Gdn::session()->UserID : null,
            ];

            // Only set CountResolved if the field is not empty.
            $currentCountResolved = val("CountResolved", $discussion, null);
            if (!empty($currentCountResolved) || in_array($currentCountResolved, [0, "0"], true)) {
                $countResolved = $currentCountResolved + $resolutionFields["Resolved"];
                $resolutionFields["CountResolved"] = $countResolved;
            }

            $this->discussionModel->setField($discussion["discussionID"], $resolutionFields);
            $this->trackDiscussionResolvedStatus($discussion["discussionID"]);
        }

        return $event;
    }

    /**
     * Initialize CountResolved and add resolved fields if needed.
     * Update discussion if resolved value is provided.
     *
     * @param DiscussionModel $sender Sending model instance.
     * @param array $args Event's arguments.
     */
    public function discussionModel_beforeSaveDiscussion_handler(DiscussionModel $sender, array $args): void
    {
        $insert = $args["Insert"];
        $canMarkAsResolved = checkPermission("Garden.Staff.Allow");
        $resolvedValue = $args["FormPostValues"]["Resolved"] ?? null;
        if ($resolvedValue === null) {
            $resolvedValue = isset($args["FormPostValues"]["internalStatusID"])
                ? $this->isResolved($args["FormPostValues"]["internalStatusID"])
                : null;
        }

        if ($insert) {
            $args["FormPostValues"]["CountResolved"] = 0;
        }
        if ($insert || ($canMarkAsResolved && is_bool($resolvedValue))) {
            $args["FormPostValues"] = $this->setResolved($args["FormPostValues"], $resolvedValue ?? false);
        }
    }

    /**
     * Set discussion resolved status.
     *
     * @param DiscussionModel $sender
     * @param array $args
     */
    public function discussionModel_afterSaveDiscussion_handler(DiscussionModel $sender, array $args): void
    {
        $canMarkAsResolved = checkPermission("Garden.Staff.Allow");
        $discussion = $args["FormPostValues"];
        $resolved = $discussion["Resolved"] ?? $this->isResolved($discussion["internalStatusID"] ?? null);
        $insert = $args["Insert"];
        // On update if we send a value.
        if (!$insert && $canMarkAsResolved && !empty($resolved)) {
            $this->trackDiscussionResolvedStatus($discussion["DiscussionID"]);
        }
    }

    /**
     * check if the status is resolved
     * @param ?int $internalStatusID
     * @return bool
     */
    public function isResolved(?int $internalStatusID): bool
    {
        return $internalStatusID === RecordStatusModel::DISCUSSION_STATUS_RESOLVED;
    }

    /**
     * Track discussion when we change the resolved status.
     *
     * @param int $discussionID
     * @psalm-suppress UndefinedClass
     */
    private function trackDiscussionResolvedStatus(int $discussionID): void
    {
        // Force a trackEvent since we are calling update instead of DiscussionModel->save()
        if (class_exists(\AnalyticsData::class) && class_exists(\AnalyticsTracker::class)) {
            $type = "discussion_edit";
            $collection = "post_modify";

            $data = \AnalyticsData::getDiscussion($discussionID);
            \AnalyticsTracker::getInstance()->trackEvent($collection, $type, $data);
        }
    }
}
