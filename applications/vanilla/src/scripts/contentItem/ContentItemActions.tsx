/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IReaction } from "@dashboard/@types/api/reaction";
import { IPermissionOptions, PermissionMode } from "@library/features/users/Permission";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { PostReactions } from "@library/postReactions/PostReactions";
import { PostReactionsProvider } from "@library/postReactions/PostReactionsContext";
import { PostReactionsLogAsModal } from "@library/postReactions/PostReactionsLog";
import { useCommentThreadParentContext } from "@vanilla/addon-vanilla/comments/CommentThreadParentContext";
import ContentItemClasses from "@vanilla/addon-vanilla/contentItem/ContentItem.classes";
import { useContentItemContext, type IContentItemContext } from "@vanilla/addon-vanilla/contentItem/ContentItemContext";
import { ContentItemShareMenu } from "@vanilla/addon-vanilla/contentItem/ContentItemShareMenu";
import { useMobile } from "@vanilla/react-utils";

interface IProps {
    reactions?: IReaction[];
}

export function ContentItemActions(props: IProps) {
    const { hasPermission } = usePermissionsContext();
    const { recordType, recordID } = useContentItemContext();
    const commentParent = useCommentThreadParentContext();
    const { categoryID, closed } = commentParent;
    const isMobile = useMobile();

    const classes = ContentItemClasses.useAsHook();

    const permissionOptions: IPermissionOptions = {
        mode: PermissionMode.RESOURCE_IF_JUNCTION,
        resourceType: "category",
        resourceID: categoryID,
    };

    let canComment = hasPermission("comments.add", permissionOptions);

    if (closed) {
        const canClose = hasPermission("discussions.close", permissionOptions);
        canComment = canClose;
    }

    const canManageReactions = hasPermission("community.moderate");

    return (
        <>
            <PostReactionsProvider recordID={recordID} recordType={recordType}>
                {canComment && (
                    <div className={classes.reactionItemsContainer}>
                        <PostReactions reactions={props.reactions} />
                    </div>
                )}

                <div className={classes.actionItemsContainer}>
                    {canManageReactions && !isMobile && (
                        <div className={classes.actionItem}>
                            <PostReactionsLogAsModal className={classes.actionButton} />
                        </div>
                    )}

                    <div className={classes.actionItem}>
                        <ContentItemShareMenu />
                    </div>
                </div>
            </PostReactionsProvider>
        </>
    );
}

export interface IReportRecordProps {
    recordType: IContentItemContext["recordType"];
    recordID: IContentItemContext["recordID"];
}
