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
import { ReportModal } from "@vanilla/addon-vanilla/thread/ReportModal";

function QuoteButton(props: { scrapeUrl: string }) {
    const { scrapeUrl } = props;
    const classes = ThreadItemActionsClasses();

    return (
        <Button
            buttonType={ButtonTypes.TEXT}
            title={t("Quote")}
            className={cx("js-quoteButton", classes.actionButton)}
            data-scrape-url={scrapeUrl} //An event listener is attached to this attribute in the vanilla-editor.
        >
            <Icon icon="editor-quote" />
            {t("Quote")}
        </Button>
    );
}

interface IProps {
    reactions?: IReaction[];
}

export default function ThreadItemActions(props: IProps) {
    const { hasPermission } = usePermissionsContext();
    const { recordUrl, recordType, recordID } = useThreadItemContext();
    const { discussion } = useDiscussionThreadContext();
    const { categoryID, closed } = discussion ?? {};

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

    const canQuote = canComment;

    const canManageReactions = hasPermission("community.moderate");

    return (
        <div className={classes.root}>
            <PostReactionsProvider recordID={recordID} recordType={recordType}>
                <PostReactions reactions={props.reactions} />
                <div className={classes.actionItemsContainer}>
                    {canManageReactions && (
                        <div className={classes.actionItem}>
                            <PostReactionsLogAsModal className={classes.actionButton} />
                        </div>
                    )}
                    {canQuote && (
                        <div className={classes.actionItem}>
                            <QuoteButton scrapeUrl={recordUrl} />
                        </div>
                    )}

                    <div className={classes.actionItem}>
                        <ThreadItemShareMenu />
                    </div>
                </div>
            </PostReactionsProvider>
        </div>
    );
}

export interface IReportRecordProps {
    recordType: IThreadItemContext["recordType"];
    recordID: IThreadItemContext["recordID"];
}
