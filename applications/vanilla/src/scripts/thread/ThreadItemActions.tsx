/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IPermissionOptions, PermissionMode } from "@library/features/users/Permission";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { t } from "@library/utility/appUtils";
import React, { useState } from "react";
import { IThreadItemContext, useThreadItemContext } from "@vanilla/addon-vanilla/thread/ThreadItemContext";
import { useDiscussionThreadContext } from "@vanilla/addon-vanilla/thread/DiscussionThreadContext";
import ThreadItemActionsClasses from "@vanilla/addon-vanilla/thread/ThreadItemActions.classes";
import { cx } from "@emotion/css";
import { Icon } from "@vanilla/icons";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { PostReactions } from "@library/postReactions/PostReactions";
import { IReaction } from "@dashboard/@types/api/reaction";
import { PostReactionsProvider } from "@library/postReactions/PostReactionsContext";
import { PostReactionsLogAsModal } from "@library/postReactions/PostReactionsLog";
import ThreadItemShareMenu from "@vanilla/addon-vanilla/thread/ThreadItemShareMenu";
import { ToolTip } from "@library/toolTip/ToolTip";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { useMobile } from "@vanilla/react-utils";
import { MetaButton } from "@library/metas/Metas";

export function QuoteButton(props: { scrapeUrl: string; categoryID: ICategory["categoryID"]; isClosed: boolean }) {
    const { scrapeUrl, isClosed, categoryID } = props;

    const { hasPermission } = usePermissionsContext();

    const permissionOptions: IPermissionOptions = {
        mode: PermissionMode.RESOURCE_IF_JUNCTION,
        resourceType: "category",
        resourceID: categoryID,
    };

    let canComment = hasPermission("comments.add", permissionOptions);

    if (isClosed) {
        const canClose = hasPermission("discussions.close", permissionOptions);
        canComment = canClose;
    }

    if (canComment) {
        return (
            <MetaButton
                icon={"editor-quote"}
                buttonClassName={cx("js-quoteButton")}
                title={t("Quote")}
                aria-label={t("Quote")}
                data-scrape-url={scrapeUrl} //An event listener is attached to this attribute in the vanilla-editor.
                onClick={() => null}
            />
        );
    }

    return null;
}

interface IProps {
    reactions?: IReaction[];
}

export default function ThreadItemActions(props: IProps) {
    const { hasPermission } = usePermissionsContext();
    const { recordUrl, recordType, recordID } = useThreadItemContext();
    const { discussion } = useDiscussionThreadContext();
    const { categoryID, closed } = discussion ?? {};
    const isMobile = useMobile();

    const classes = ThreadItemActionsClasses();

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
                        <ThreadItemShareMenu />
                    </div>
                </div>
            </PostReactionsProvider>
        </>
    );
}

export interface IReportRecordProps {
    recordType: IThreadItemContext["recordType"];
    recordID: IThreadItemContext["recordID"];
}
