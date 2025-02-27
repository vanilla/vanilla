/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { PermissionOverridesContext } from "@library/features/users/PermissionOverrideContext";
import type { RecordID } from "@vanilla/utils";
import { createContext, useContext, useState } from "react";

export interface ICommentParent {
    recordType: string;
    recordID: RecordID;
    dateInserted: string;
    insertUserID: RecordID;
    categoryID: number;
    url: string;
    closed?: boolean;
    permissionsOverrides?: Record<string, boolean> | null;
}

interface ICommentThreadParentContext extends ICommentParent {
    currentPage: number;
    setCurrentPage: (page: number) => void;
}

export const UNKNOWN_COMMENT_PARENT: ICommentThreadParentContext = {
    recordType: "unknown",
    recordID: -5,
    insertUserID: -5,
    permissionsOverrides: null,
    closed: false,
    url: "/unknown-comment-parent",
    categoryID: -5,
    dateInserted: new Date().toISOString(),
    currentPage: 1,
    setCurrentPage() {},
};

const context = createContext<ICommentThreadParentContext>(UNKNOWN_COMMENT_PARENT);

export function CommentThreadParentContext(
    props: Omit<ICommentThreadParentContext, "setCurrentPage"> & { children: React.ReactNode },
) {
    const [currentPage, setCurrentPage] = useState(props.currentPage);
    return (
        <PermissionOverridesContext permissions={props.permissionsOverrides ?? {}}>
            <context.Provider value={{ ...props, currentPage, setCurrentPage }}>{props.children}</context.Provider>
        </PermissionOverridesContext>
    );
}

export function useCommentThreadParentContext() {
    return useContext(context);
}

export function isDiscussionCommentParent(
    commentParent: ICommentParent,
): commentParent is ICommentParent & { recordType: "discussion" } {
    return commentParent.recordType === "discussion";
}
