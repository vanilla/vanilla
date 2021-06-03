/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

type MeIconType = "me-messages-solid" | "me-messages" | "me-notifications-solid" | "me-notifications";

type MetaIconType =
    | "meta-comment"
    | "meta-external"
    | "meta-like"
    | "meta-smile"
    | "meta-time"
    | "meta-view"
    | "meta-resolved"
    | "meta-unresolved";

type NavigationIconType = "navigation-languages";

type SearchIconType = "search-events" | "search-search";

type DashboardIconType = "dashboard-edit";

export type IconType = MeIconType | MetaIconType | NavigationIconType | SearchIconType | DashboardIconType;
