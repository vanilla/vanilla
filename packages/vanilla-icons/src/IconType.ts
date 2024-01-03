/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

type AnalyticsIconType = "analytics-add" | "analytics-remove";

type DashboardIconType = "dashboard-edit";

type DataIconType =
    | "data-add"
    | "data-article"
    | "data-down"
    | "data-drag-and-drop"
    | "data-folder-tabs"
    | "data-information"
    | "data-left"
    | "data-online"
    | "data-pencil"
    | "data-refresh"
    | "data-replace"
    | "data-right"
    | "data-settings"
    | "data-site-metric"
    | "data-sort-dropdown"
    | "data-swap"
    | "data-trash"
    | "data-up";

type DiscussionIconType = "discussion-bookmark-solid" | "discussion-bookmark";

type EditorIconType =
    | "editor-eye-slash"
    | "editor-eye"
    | "editor-link-card"
    | "editor-link-rich"
    | "editor-link-text"
    | "editor-link"
    | "editor-unlink";

type EventIconType = "event-attending" | "event-registered";

type ExternalIconType = "external-link";

type MeIconType =
    | "me-inbox"
    | "me-messages-solid"
    | "me-messages"
    | "me-notifications-solid"
    | "me-notifications"
    | "me-notifications-small"
    | "me-sign-in";

type MetaIconType =
    | "meta-comment"
    | "meta-external"
    | "meta-like"
    | "meta-resolved"
    | "meta-smile"
    | "meta-time"
    | "meta-unresolved"
    | "meta-view";

type NavigationIconType =
    | "navigation-breadcrumb-active"
    | "navigation-breadcrumb-inactive"
    | "navigation-collapseAll"
    | "navigation-ellipsis"
    | "navigation-expandAll"
    | "navigation-languages"
    | "navigation-skip";

type NewIconType = "new-discussion" | "new-event" | "new-idea" | "new-poll" | "new-question";

type NotificationIconType = "notification-alert";

type ProfileIconType = "profile-crown" | "profile-lock";

type ReactionIconType = "reaction-comments";

type SearchIconType =
    | "search-answered"
    | "search-categories"
    | "search-subcategories"
    | "search-discussion"
    | "search-events"
    | "search-filter-applied"
    | "search-filter-small-applied"
    | "search-filter-small"
    | "search-filter"
    | "search-groups"
    | "search-ideas"
    | "search-kb"
    | "search-members"
    | "search-post-count"
    | "search-questions"
    | "search-search";

type StatusIconType = "status-warning";

type UserIconType = "user-spoof";

type VanillaIconType = "vanilla-logo";

type UserManagementIconType = "user-spoof";

type EventIcon = "event-interested-filled" | "event-interested-empty" | "event-registered";

export type IconType =
    | AnalyticsIconType
    | DashboardIconType
    | DataIconType
    | DiscussionIconType
    | EditorIconType
    | EventIconType
    | ExternalIconType
    | MeIconType
    | MetaIconType
    | NavigationIconType
    | NewIconType
    | NotificationIconType
    | ProfileIconType
    | ReactionIconType
    | SearchIconType
    | StatusIconType
    | UserIconType
    | VanillaIconType
    | UserManagementIconType
    | EventIcon;
