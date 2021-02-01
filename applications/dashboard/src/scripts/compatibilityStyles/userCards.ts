import { globalVariables } from "@library/styles/globalStyleVars";
import { userPhotoVariables } from "@library/headers/mebox/pieces/userPhotoStyles";
import { forumVariables } from "@library/forms/forumStyleVars";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { absolutePosition } from "@library/styles/styleHelpersPositioning";
import { importantUnit } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";

export const userCardDiscussionPlacement = () => {
    const discussionItemPadding = globalVariables().itemList.padding;
    const userPhotoVars = userPhotoVariables();
    const formVars = forumVariables();

    const paddingTop: number =
        parseInt(`${discussionItemPadding.top ?? discussionItemPadding.vertical ?? discussionItemPadding.all}`) ?? 0;
    const paddingLeft: number =
        parseInt(`${discussionItemPadding.left ?? discussionItemPadding.horizontal ?? discussionItemPadding.all}`) ?? 0;

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
