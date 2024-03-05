/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useThemeCache } from "@library/styles/styleUtils";
import { contributionItemVariables } from "@library/contributionItems/ContributionItem.variables";

export const reactionsVariables = useThemeCache(() => {
    /**
     * @varGroup reactions
     * @commonTitle Reactions
     * @expand contributionItems
     * @commonDescription Variables affecting Reactions
     */
    return contributionItemVariables("reactions");
});
