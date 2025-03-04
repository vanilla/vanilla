/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

export type IconData = {
    height: string | null;
    width: string | null;
    viewBox: string | null;
    [key: string]: any;
};

export const coreIconsData = {
    add: {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "ai-indicator": {
        width: "16",
        height: "16",
        fill: "currentColor",
        viewBox: "0 0 16 16",
    },
    "ai-sparkle-monocolor": {
        width: "16",
        height: "16",
        viewBox: "0 0 16 16",
        fill: "currentColor",
    },
    "bookmark-empty": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        stroke: "currentColor",
        strokeWidth: "1.4",
    },
    "bookmark-filled": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "currentColor",
        stroke: "currentColor",
        strokeWidth: "1.4",
    },
    "collapse-all": {
        viewBox: "0 0 24 24",
        role: "img",
    },
    "copy-link": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "create-discussion": {
        width: "24px",
        height: "21px",
        viewBox: "0 0 24 21",
    },
    "create-event": {
        width: "24px",
        height: "24px",
        viewBox: "0 0 24 24",
    },
    "create-idea": {
        viewBox: "0 0 24 24",
        width: "24",
        height: "24",
    },
    "create-poll": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
    },
    "create-question": {
        width: "24px",
        height: "24px",
        viewBox: "0 0 24 24",
    },
    "dashboard-edit": {
        width: "22",
        height: "22",
        viewBox: "0 0 22 22",
    },
    "data-checked": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "data-site-metric": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    delete: {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "dismiss-compact": {
        viewBox: "0 0 9.5 9.5",
    },
    dismiss: {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "edit-filters": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        ariahidden: "true",
    },
    edit: {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "editor-link-card": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "editor-link-rich": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "editor-link-text": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "editor-unlink": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "event-interested-empty": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "event-interested-filled": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "expand-all": {
        viewBox: "0 0 24 24",
        role: "img",
    },
    "filter-add": {
        width: "18",
        height: "18",
        viewBox: "0 0 18 18",
        fill: "none",
    },
    "filter-applied": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "filter-compact-applied": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "filter-compact": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "filter-remove": {
        width: "18",
        height: "18",
        viewBox: "0 0 18 18",
        fill: "none",
    },
    filter: {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    folder: {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
    },
    "follow-empty": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
    },
    "follow-filled": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
    },
    "hide-content": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
    },
    info: {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "logo-github": {
        width: "60",
        height: "60",
        viewBox: "0 0 60 60",
        fill: "none",
    },
    "logo-jira": {
        width: "60",
        height: "60",
        viewBox: "0 0 60 60",
        fill: "none",
    },
    "logo-salesforce": {
        width: "60",
        height: "60",
        viewBox: "0 0 60 60",
    },
    "logo-zendesk": {
        width: "60",
        height: "60",
        viewBox: "0 0 60 60",
    },
    "me-messages-empty": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
    },
    "me-messages-filled": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
    },
    "me-notifications-empty": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
    },
    "me-notifications-filled": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
    },
    "me-sign-in": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
    },
    "me-subcommunities": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
    },
    "meta-answered": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "meta-article": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "meta-categories": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "meta-child-categories": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "meta-comments": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
    },
    "meta-discussions": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "meta-events": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
    },
    "meta-external-compact": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
    },
    "meta-external": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "meta-follower": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "meta-groups": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "meta-ideas": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "meta-knowledge-bases": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "meta-points": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
    },
    "meta-posts": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "meta-questions": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "meta-time": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
    },
    "meta-users": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "meta-views": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
    },
    "move-down": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "move-drag": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "move-left": {
        style: {
            transform: " rotate(-90deg)",
        },
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "move-right": {
        style: {
            transform: " rotate(90deg)",
        },
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "move-up": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "navigation-breadcrumb-active": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "navigation-breadcrumb-inactive": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "notify-email": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "options-menu": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "pager-skip": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
    },
    "quote-content": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
    },
    "reaction-arrow-down": {
        width: "25",
        height: "24",
        viewBox: "0 0 25 24",
        fill: "none",
    },
    "reaction-arrow-up": {
        width: "25",
        height: "24",
        viewBox: "0 0 25 24",
        fill: "none",
    },
    "reaction-awesome": {
        width: "25",
        height: "24",
        viewBox: "0 0 25 24",
        fill: "none",
    },
    "reaction-comments": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "reaction-dislike": {
        width: "25",
        height: "24",
        viewBox: "0 0 25 24",
        fill: "none",
    },
    "reaction-expressionless": {
        width: "25",
        height: "24",
        viewBox: "0 0 25 24",
        fill: "none",
    },
    "reaction-fire": {
        width: "25",
        height: "24",
        viewBox: "0 0 25 24",
        fill: "none",
    },
    "reaction-funny": {
        width: "25",
        height: "24",
        viewBox: "0 0 25 24",
        fill: "none",
    },
    "reaction-insightful": {
        width: "25",
        height: "24",
        viewBox: "0 0 25 24",
        fill: "none",
    },
    "reaction-like": {
        width: "25",
        height: "24",
        viewBox: "0 0 25 24",
        fill: "none",
    },
    "reaction-log": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "reaction-love": {
        width: "25",
        height: "24",
        viewBox: "0 0 25 24",
        fill: "none",
    },
    "reaction-more": {
        width: "25",
        height: "24",
        viewBox: "0 0 25 24",
        fill: "none",
    },
    "reaction-off-topic": {
        width: "25",
        height: "24",
        viewBox: "0 0 25 24",
        fill: "none",
    },
    "reaction-support": {
        width: "25",
        height: "24",
        viewBox: "0 0 25 24",
        fill: "none",
    },
    "reaction-thumbs-down": {
        width: "25",
        height: "24",
        viewBox: "0 0 25 24",
        fill: "none",
    },
    "reaction-thumbs-up": {
        width: "25",
        height: "24",
        viewBox: "0 0 25 24",
        fill: "none",
    },
    "reaction-very-negative": {
        width: "25",
        height: "24",
        viewBox: "0 0 25 24",
        fill: "none",
    },
    "reaction-wtf": {
        width: "25",
        height: "24",
        viewBox: "0 0 25 24",
        fill: "none",
    },
    refresh: {
        viewBox: "0 0 24 24",
        fill: "#555A62",
    },
    replace: {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "report-content": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    resolved: {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "search-all": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
    },
    "search-articles": {
        viewBox: "0 0 14.666 14.666",
    },
    "search-categories": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "search-discussions": {
        viewBox: "0 0 18.869 15.804",
    },
    "search-groups": {
        viewBox: "0 0 17 16",
    },
    "search-ideas": {
        viewBox: "0 0 18.444 16.791",
    },
    "search-knowledge-bases": {
        viewBox: "0 0 16 16",
    },
    "search-members": {
        viewBox: "0 0 20 20",
    },
    "search-places": {
        viewBox: "0 0 15.122 16.416",
    },
    "search-polls": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "search-questions": {
        viewBox: "0 0 26 26",
    },
    search: {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
    },
    send: {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    share: {
        width: "16",
        height: "16",
        viewBox: "0 0 16 16",
        fill: "none",
    },
    "show-content": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "sort-by": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "status-alert": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "status-running": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "status-success": {
        width: "16",
        height: "16",
        viewBox: "0 0 16 16",
        fill: "none",
    },
    "status-warning": {
        width: "16",
        height: "16",
        viewBox: "0 0 16 16",
        fill: "none",
    },
    swap: {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    undo: {
        width: "16",
        height: "17",
        viewBox: "0 0 16 17",
        fill: "none",
    },
    unresolved: {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "user-spoof": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "vanilla-logo": {
        viewBox: "0 0 347.01 143.98",
    },
    "visibility-internal": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "visibility-private": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
    "whos-online": {
        width: "24",
        height: "24",
        viewBox: "0 0 24 24",
        fill: "none",
    },
};

export type IconType = keyof typeof coreIconsData;
