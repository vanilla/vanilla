/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { importantUnit } from "@library/styles/styleHelpers";

export const userCardDiscussionPlacement = () => {
    cssOut(`.DataTable.DiscussionsTable .ItemIdea .DiscussionName .Wrap .Meta.Meta-Discussion`, {
        marginLeft: -globalVariables().gutter.half,
    });

    cssOut(`.DataTable.DiscussionsTable .ItemIdea .DiscussionName .Wrap .HasNew`, {
        marginTop: globalVariables().gutter.half,
        marginRight: globalVariables().gutter.half,
    });

    cssOut(`.DataTable.DiscussionsTable .ItemIdea .DiscussionName .Wrap`, {
        paddingLeft: importantUnit(globalVariables().gutter.half),
    });
};
