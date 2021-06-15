/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

type MeIconType = "me-messages-solid" | "me-messages" | "me-notifications-solid" | "me-notifications" | "me-sign-in";

type MetaIconType =
    | "meta-comment"
    | "meta-external"
    | "meta-like"
    | "meta-smile"
    | "meta-time"
    | "meta-view"
    | "meta-resolved"
    | "meta-unresolved";

type NavigationIconType = "navigation-languages" | "navigation-ellipsis";

type SearchIconType = "search-events" | "search-search";

type DashboardIconType = "dashboard-edit";

type DiscussionIconType = "discussion-bookmark";

export type IconType =
    | MeIconType
    | MetaIconType
    | NavigationIconType
    | SearchIconType
    | DashboardIconType
    | DiscussionIconType;
