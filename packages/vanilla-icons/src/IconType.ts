/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

type AnalyticsIconType = "analytics-add" | "analytics-remove";

type DashboardIconType = "dashboard-edit";

type DataIconType =
    | "data-add"
    | "data-down"
    | "data-folder-tabs"
    | "data-left"
    | "data-pencil"
    | "data-refresh"
    | "data-replace"
    | "data-right"
    | "data-swap"
    | "data-trash"
    | "data-up";

type DiscussionIconType = "discussion-bookmark-solid" | "discussion-bookmark";

type EditorIconType = "editor-eye-slash" | "editor-eye";

type ExternalIconType = "external-link";

type MeIconType = "me-messages-solid" | "me-messages" | "me-notifications-solid" | "me-notifications" | "me-sign-in";

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
    | "navigation-collapseAll"
    | "navigation-ellipsis"
    | "navigation-expandAll"
    | "navigation-languages";

type NewIconType = "new-discussion" | "new-event" | "new-idea" | "new-poll" | "new-question";

type SearchIconType = "search-events" | "search-search";

type StatusIconType = "status-warning";

type VanillaIconType = "vanilla-logo";

export type IconType =
    | AnalyticsIconType
    | DashboardIconType
    | DataIconType
    | DiscussionIconType
    | EditorIconType
    | ExternalIconType
    | MeIconType
    | MetaIconType
    | NavigationIconType
    | NewIconType
    | SearchIconType
    | StatusIconType
    | VanillaIconType;
