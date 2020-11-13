import { globalVariables } from "@library/styles/globalStyleVars";
import { userPhotoVariables } from "@library/headers/mebox/pieces/userPhotoStyles";
import { forumVariables } from "@library/forms/forumStyleVars";
import { cssOut } from "@dashboard/compatibilityStyles/index";
import { absolutePosition } from "@library/styles/styleHelpersPositioning";
import { unit, importantUnit } from "@library/styles/styleHelpers";

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

    // With photo or only an idea
    cssOut(
        `
        .DataList.Discussions .ItemDiscussion-withPhoto .userCardWrapper-photo,
        .DataList.Discussions .ItemIdea:not(.ItemDiscussion-withPhoto) .idea-counter-module,
        body.Discussion .ItemDiscussion .DiscussionHeader .userCardWrapper-photo,
        body.Discussion .MessageList .CommentHeader .userCardWrapper-photo,
        `,
        {
            ...absolutePosition.topLeft(paddingTop, paddingLeft),
        },
    );

    // With Photo AND idea
    // The photo will already be placed, we need to take care of the idea
    cssOut(
        `

        .DataList.Discussions .ItemIdea.ItemDiscussion-withPhoto .idea-counter-module
        `,
        {
            ...absolutePosition.topLeft(paddingTop, paddingLeft + userPhotoVars.sizing.medium + paddingLeft),
        },
    );

    cssOut(
        `
        .DataList.Discussions .ItemDiscussion-withPhoto.ItemDiscussion-withPhoto .Discussion.ItemContent,
        .DataList.Discussions .ItemIdea.ItemIdea .Discussion.ItemContent
        `,
        {
            paddingLeft: unit(userPhotoVars.sizing.medium + formVars.spacer.size / 2),
        },
    );

    // With photo AND with idea
    cssOut(
        `.DataList.Discussions .ItemDiscussion-withPhoto.ItemDiscussion-withPhoto.ItemIdea .Discussion.ItemContent`,
        {
            paddingLeft: unit(userPhotoVars.sizing.medium + formVars.spacer.size + formVars.countBox.width),
        },
    );
};
