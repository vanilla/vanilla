/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { menuBarClasses } from "@library/MenuBar/MenuBar.classes";

export const MenuBarItemSeparator = (props: { spacing?: { leftSpace?: number; rightSpace?: number } }) => {
    return <span role="separator" className={menuBarClasses().menuItemSeparator(props.spacing)}></span>;
};
