/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

type DashboardIconType = "dashboard-edit" | "vanilla-logo";

type DataIconType = "data-folder-tabs" | "data-pencil" | "data-refresh" | "data-trash" | "data-add" | "data-up" | "data-down" | "data-swap" | "data-replace";

type DiscussionIconType = "discussion-bookmark-solid" | "discussion-bookmark";

type EditorIconType = "editor-eye-slash" | "editor-eye";

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

type NavigationIconType = "navigation-ellipsis" | "navigation-languages";

type SearchIconType = "search-events" | "search-search";

type AnalyticsIconType = "analytics-add" | "analytics-remove";
type StatusIconTypes = "status-warning";

type NewPostIconType = "new-question" | "new-poll" | "new-idea" | "new-discussion" | "new-event";

type ExternalIconType = "external-link";

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
    | NewPostIconType
    | SearchIconType
    | StatusIconTypes;
