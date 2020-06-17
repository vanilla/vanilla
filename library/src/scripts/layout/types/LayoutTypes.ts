/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

export enum LayoutTypes {
    THREE_COLUMNS = "three columns", // Dynamic layout with up to 3 columns that adjusts to its contents. This is the default for KB
    ONE_COLUMN = "one column", // Single column, but full width of page
    NARROW = "one column narrow", // Single column, but narrower than default
    LEGACY = "legacy", // Legacy layout used on the Forum pages. The media queries are also used for older components. Newer ones should use the context
}
