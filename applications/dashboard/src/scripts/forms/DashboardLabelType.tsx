/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

export const DashboardLabelType = {
    STANDARD: "standard",
    WIDE: "wide",
    VERTICAL: "vertical",
    JUSTIFIED: "justified",
    NONE: "none",
} as const;

export type DashboardLabelType = (typeof DashboardLabelType)[keyof typeof DashboardLabelType];
