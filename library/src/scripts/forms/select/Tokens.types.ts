/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import type { IComboBoxOption } from "@library/features/search/ISearchBarProps";

export interface IGroupOption {
    label: string;
    options: IComboBoxOption[];
}
