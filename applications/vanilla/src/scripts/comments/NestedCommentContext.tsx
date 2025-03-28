/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { CommentDeleteMethod, IComment } from "@dashboard/@types/api/comment";
import { IUserFragment } from "@library/@types/api/users";
import apiv2 from "@library/apiv2";
import { IError } from "@library/errorPages/CoreErrorMessages";
import {
    IThreadItem,
    IThreadItemComment,
    IThreadItemHole,
    IThreadItemReply,
    IThreadResponse,
} from "@vanilla/addon-vanilla/comments/NestedCommentTypes";
import { CommentsApi } from "@vanilla/addon-vanilla/comments/CommentsApi";
import {
    deduplicateNestedItems,
    isNestedComment,
    isNestedHole,
    isNestedReply,
} from "@vanilla/addon-vanilla/comments/NestedCommentUtils";
import React, { memo, MutableRefObject, useContext, useEffect, useMemo, useRef, useState } from "react";
import { logDebug, RecordID } from "@vanilla/utils";
import { BulkAction } from "@library/bulkActions/BulkActions.types";
import type { CommentActionsComponentType } from "@vanilla/addon-vanilla/comments/CommentActionsComponentType";

type RefsByID = Record<string, React.RefObject<HTMLElement>>;
export interface INestedCommentContext {
    threadDepthLimit: number;
    threadStructure: IThreadItem[];
    commentsByID: Record<IComment["commentID"], IComment>;
    getComment: (commentID: IComment["commentID"]) => IComment | undefined;
    updateComment: (commentID: IComment["commentID"], updatedComment?: IComment) => void;
    updateCommentList: (
        commentIDs: Array<IComment["commentID"]>,
        options: {
            bulkAction: BulkAction | null;
            deleteMethod?: CommentDeleteMethod;
        },
    ) => void;
    addToThread: (apiUrl: string, path: string) => Promise<void | IError>;
    collapseThreadAtPath: (path: string) => Promise<void>;
    lastChildRefsByID: RefsByID;
    addLastChildRefID: (commentID: string, ref: React.RefObject<HTMLDivElement>) => void;
    currentReplyFormRef?: React.MutableRefObject<IThreadItemReply | null>;
    showReplyForm: (threadComment: IThreadItemComment) => void;
    switchReplyForm: (threadComment: IThreadItemComment) => void;
    addReplyToThread: (reply: IThreadItemReply, comment: IComment, mobile: boolean) => void;
    removeReplyFromThread: (threadReply: IThreadItemReply, resetReplyRef?: boolean) => void;
    showOPTag?: boolean;
    authorBadges?: {
        display: boolean;
        limit: number;
    };
    constructReplyFromComment: (threadComment: IThreadItemComment) => IThreadItemReply;
    collapsedThreadPartialsByPath?: Record<string, IThreadItem[]>;
    selectableCommentIDs: Array<IComment["commentID"]>;
    CommentActionsComponent?: CommentActionsComponentType;
}

export const NestedCommentContext = React.createContext<INestedCommentContext>({
    threadDepthLimit: 1,
    threadStructure: [],
    commentsByID: {},
    getComment: () => undefined,
    updateComment: () => {},
    updateCommentList: () => {},
    addToThread: async () => {},
    collapseThreadAtPath: async () => {},
    lastChildRefsByID: {},
    addLastChildRefID: () => {},
    currentReplyFormRef: undefined,
    showReplyForm: () => {},
    switchReplyForm: () => {},
    addReplyToThread: () => {},
    removeReplyFromThread: () => {},
    showOPTag: undefined,
    authorBadges: undefined,
    constructReplyFromComment: () => {
        return {} as IThreadItemReply;
    },
    collapsedThreadPartialsByPath: undefined,
    selectableCommentIDs: [],
});

export function useNestedCommentContext() {
    return useContext(NestedCommentContext);
}

export type NestedCommentProviderProps = IThreadResponse &
    React.PropsWithChildren<{
        threadDepthLimit?: number;
        showOPTag?: boolean;
        authorBadges?: {
            display: boolean;
            limit: number;
        };
        commentApiParams?: CommentsApi.IndexThreadParams;
        CommentActionsComponent?: CommentActionsComponentType;
        children?: React.ReactNode;
    }>;

export const NestedCommentContextProvider = memo(function NestedCommentContextProvider(
    props: NestedCommentProviderProps,
) {
    const {
        children,
        threadDepthLimit = 3,
        showOPTag,
        authorBadges,
        commentApiParams,
        CommentActionsComponent,
    } = props;

    const [threadStructure, setThreadStructure] = useState<IThreadResponse["threadStructure"]>(props.threadStructure);
    const [collapsedThreadPartialsByPath, setCollapsedThreadPartialsByPath] = useState<Record<string, IThreadItem[]>>(
        {},
    );
    const [commentsByID, setCommentsByID] = useState<IThreadResponse["commentsByID"]>(props.commentsByID);
    const [lastChildRefsByID, setLastChildRefsByID] = useState<RefsByID>({});

    /**
     * Adds a dot delimited list of commentIDs to each thread item to identify its location in the thread structure
     * Used to determine where to inject new comments or where to remove and add back comments
     */
    const addPaths = (threadItem: IThreadItem, parentCommentID: IComment["commentID"] | null, previousPath?: any) => {
        if (threadItem.type === "hole") {
            return {
                ...threadItem,
                holeID: `${parentCommentID}-${threadItem.offset}`,
                path: previousPath,
            };
        }
        if (threadItem.type === "comment" && threadItem.children) {
            return {
                ...threadItem,
                path:
                    !previousPath && threadItem.depth === 1
                        ? threadItem.commentID
                        : `${previousPath}.${threadItem.commentID}`,
                children: threadItem.children.map((child) => {
                    const path = previousPath ? `${previousPath}.${child.parentCommentID}` : child.parentCommentID;
                    return addPaths(child, threadItem.commentID, path);
                }),
            };
        }
        return threadItem;
    };

    useEffect(() => {
        const newThreadStructure = props.threadStructure.map((threadItem) => addPaths(threadItem, null));
        setThreadStructure(newThreadStructure);
    }, [props.threadStructure]);

    useEffect(() => {
        setCommentsByID((prev) => ({ ...prev, ...props.commentsByID }));
    }, [props.commentsByID]);

    const getComment = (commentID: IComment["commentID"]) => {
        return commentsByID?.[commentID];
    };

    const updateComment = async (commentID: IComment["commentID"], updatedComment?: IComment) => {
        if (updatedComment) {
            setCommentsByID((prev) => {
                if (commentID in prev) {
                    // Spread here to not overwrite the existing comment reactions or attachment data
                    return { ...prev, [commentID]: { ...prev[commentID], ...updatedComment } };
                }
                return { ...prev, [commentID]: updatedComment };
            });
        } else {
            try {
                const params = { expand: commentApiParams?.expand ?? [], quoteParent: false };
                const updatedComment = await CommentsApi.get(commentID, params);
                setCommentsByID((prev) => {
                    return { ...prev, [commentID]: { ...prev[commentID], ...updatedComment } };
                });
            } catch (error) {
                // could not find comment, deleted comment
                const newCommentsByID = { ...commentsByID };
                delete newCommentsByID[commentID];
                setCommentsByID(newCommentsByID);

                // update the thread structure as well
                const updatedThreadStructure = removeDeletedItemsFromThread(threadStructure, [commentID]);
                setThreadStructure(updatedThreadStructure);
            }
        }
    };

    // bulk actions purposes
    const updateCommentList = async (
        commentIDs: Array<IComment["commentID"]>,
        options: { bulkAction: BulkAction; deleteMethod?: CommentDeleteMethod },
    ) => {
        const params = { expand: commentApiParams?.expand ?? [] };
        const newCommentsByID = { ...commentsByID };
        let newThreadStructure = [...threadStructure];

        const shouldSkipRefetchComments =
            options.bulkAction === BulkAction.SPLIT ||
            (options.bulkAction === BulkAction.DELETE && options.deleteMethod === CommentDeleteMethod.FULL);

        const updatedComments = shouldSkipRefetchComments ? [] : await CommentsApi.getList(commentIDs, params);

        if (updatedComments.length > 0) {
            updatedComments.forEach((comment) => {
                if (newCommentsByID[comment.commentID]) {
                    newCommentsByID[comment.commentID] = { ...newCommentsByID[comment.commentID], ...comment };
                }
            });
            setCommentsByID(newCommentsByID);
        } else {
            // SPLIT or FULL DELETE bulk action
            commentIDs.forEach((commentID) => {
                delete newCommentsByID[commentID];
            });
            newThreadStructure = removeDeletedItemsFromThread(newThreadStructure, commentIDs);

            // update states
            setCommentsByID(newCommentsByID);
            setThreadStructure(newThreadStructure);
        }
    };

    const fetchPartialThread = async (apiUrl: string): Promise<IThreadResponse> => {
        const response = await apiv2(apiUrl);
        return response.data;
    };

    interface IAddChildrenParams {
        /** The path at which the partial should be nested */
        path: string;
        /** The structure to which children should be added */
        initialThreadStructure: IThreadItem[];
        /** The thread structure to add at the given child */
        partial: IThreadItem[];
        /** If the partial should replace instead of merge existing children at the path */
        replace?: boolean;
        /** If any holes at the path should be preserved */
        preserveHoles?: boolean;
        /** If a reply at the path should be preserved */
        preserveReply?: boolean;
        /** If the partial should be added before or after existing children (if replace is false or undefined) */
        type?: "prepend" | "append";
        /** The original nesting path */
        initialPath?: string;
    }

    /**
     * Recursively inject the new children into the thread structure
     */
    const addChildren = (params: IAddChildrenParams): IThreadItem[] => {
        const {
            path,
            initialThreadStructure,
            partial,
            initialPath,
            replace = false,
            preserveHoles = false,
            preserveReply = true,
            type = "append",
        } = params;

        const filterTypes = ["comment", ...(preserveReply ? ["reply"] : []), ...(preserveHoles ? ["hole"] : [])].flat();
        let location = `${path}`.split(".");

        return initialThreadStructure.map((threadItem: IThreadItem) => {
            // If the child is a comment and the commentID matches the location
            if (location.length > 0) {
                if (isNestedComment(threadItem) && String(threadItem.commentID) == location[0]) {
                    location.shift();
                    if (location.length > 0) {
                        threadItem.children = addChildren({
                            ...params,
                            initialThreadStructure: threadItem.children ?? [],
                            path: location.join("."),
                            initialPath: initialPath ?? path,
                        });
                        return threadItem;
                    } else {
                        if (!replace) {
                            const existingChildComments =
                                threadItem.children?.filter((c) => filterTypes.includes(c.type)) ?? [];
                            const mergedChildren = [existingChildComments];
                            if (type === "append") {
                                mergedChildren.push(partial);
                            } else {
                                mergedChildren.unshift(partial);
                            }
                            const newChildren = deduplicateNestedItems(mergedChildren.flat()).map((newChild) =>
                                addPaths(newChild, threadItem.commentID, initialPath ?? path),
                            );
                            threadItem.children = newChildren;
                            return threadItem;
                        }
                        threadItem.children = partial.map((newChild) =>
                            addPaths(newChild, threadItem.commentID, initialPath ?? path),
                        );
                        return threadItem;
                    }
                }
            }
            return threadItem;
        });
    };

    /**
     * Used fill a hole in the thread structure
     * @param apiUrl - The URL to fetch the partial thread
     * @param path - The path to the hole in the thread structure, a dot delimited string of commentIDs
     */
    const addToThread = async (apiUrl: string, path: string): Promise<void | IError> => {
        if (path) {
            let partial: IThreadResponse;
            if (!collapsedThreadPartialsByPath[path]) {
                // Get the partial thread
                partial = await fetchPartialThread(apiUrl).catch((error) => {
                    throw error;
                });
                // Update the comment store
                setCommentsByID((prev) => ({ ...prev, ...partial.commentsByID }));
            } else {
                partial = { threadStructure: collapsedThreadPartialsByPath[path], commentsByID: {} };
            }
            // Update the thread structure
            const newThreadStructure = addChildren({
                path,
                initialThreadStructure: threadStructure,
                partial: partial.threadStructure,
            });
            setThreadStructure(newThreadStructure);
        } else {
            logDebug("No path provided to updateThread");
        }
    };

    const getThreadPartial = (location: string[], partialStructure: IThreadItem[]) => {
        let ownLocation = location;
        let children: IThreadItem[] | undefined;

        partialStructure.forEach((child) => {
            const isComment = isNestedComment(child);
            const commentIDMatches = isComment ? `${child?.commentID}` == ownLocation[0] : false;

            if (isComment && commentIDMatches) {
                if (ownLocation.length > 1) {
                    ownLocation.shift();
                    if (ownLocation.length > 0 && child.children) {
                        children = getThreadPartial(ownLocation, child.children);
                    } else {
                        children = child.children;
                    }
                } else {
                    ownLocation.shift();
                    children = isNestedComment(child) ? child.children : [];
                }
            }
        });
        return children;
    };

    const replaceThreadAtPath = (path: string, newChildren: IThreadItem[]) => {
        const newThreadStructure = addChildren({
            path,
            initialThreadStructure: threadStructure,
            partial: newChildren,
            replace: true,
        });
        setThreadStructure(newThreadStructure);
    };

    const getUsers = (partial: IThreadItem[]): IUserFragment[] => {
        return (
            partial.reduce((acc: IUserFragment[], curr) => {
                const isComment = isNestedComment(curr);
                const isHole = isNestedHole(curr);
                if (isComment) {
                    const user = commentsByID[curr.commentID].insertUser;
                    const childUsers = getUsers(curr.children ?? []);

                    const isUserInArray = acc.some((accUsers) => accUsers.userID === user.userID);
                    if (user && !isUserInArray) {
                        return [...acc, user, childUsers].flat();
                    }
                }
                if (isHole) {
                    return [...acc, ...curr.insertUsers];
                }
                return acc;
            }, []) ?? []
        );
    };

    const getCommentCount = (partial: IThreadItem[]): number => {
        return partial.reduce((acc, curr) => {
            const isComment = isNestedComment(curr);
            const isHole = isNestedHole(curr);
            if (isComment) {
                return acc + 1 + getCommentCount(curr.children ?? []);
            }
            if (isHole) {
                return acc + curr.countAllComments;
            }
            return acc;
        }, 0);
    };

    const constructHoleFromPartial = (partial: IThreadItem[], path: string): IThreadItemHole => {
        const insertUsers = getUsers(partial);
        const countComments = getCommentCount(partial);

        return {
            type: "hole",
            parentCommentID: partial[0].parentCommentID,
            depth: partial[0].depth + 1,
            offset: 0,
            insertUsers: insertUsers.slice(0, 5),
            countAllComments: countComments,
            countAllInsertUsers: insertUsers.length,
            apiUrl: path,
            path,
        };
    };

    /**
     * Remove all children at a given path and replace it with a faux hole
     * @param path
     */
    const collapseThreadAtPath = async (path: string) => {
        return new Promise<void>((resolve) => {
            let location = `${path}`.split(".");
            const partial = getThreadPartial(location, threadStructure);
            const reply = partial?.find((child) => child.type === "reply");
            // If we got the partial
            if (partial) {
                // Cache the children
                setCollapsedThreadPartialsByPath((prev) => {
                    return { ...prev, [path]: partial.filter((child) => child.type !== "reply") };
                });
                // Create a new hole and replace the children with it
                const hole = constructHoleFromPartial(partial, path);
                const replacement = reply ? [reply, hole] : [hole];
                replaceThreadAtPath(path, replacement);
            }
            resolve();
        });
    };

    const addLastChildRefID = (commentID: string, ref: React.RefObject<HTMLElement>) => {
        setLastChildRefsByID((prev) => {
            return { ...prev, [commentID]: ref };
        });
    };

    const constructReplyFromComment = (threadComment: IThreadItemComment): IThreadItemReply => {
        return {
            type: "reply",
            parentCommentID: threadComment.commentID,
            depth: threadComment.depth + 1,
            path: String(threadComment.path),
            replyID: `reply-${threadComment.commentID}`,
            replyingTo: commentsByID[threadComment.commentID].insertUser.name,
        };
    };

    const replyRef = useRef<IThreadItemReply | null>(null);

    const addReplyForm = (threadComment: IThreadItemComment, threadStructure: IThreadItem[]) => {
        const path = String(threadComment.path);
        const isReplyOpen = !!replyRef.current;

        // If there is not reply open, or the current reply is not for this same comment
        if (!isReplyOpen || (replyRef.current && replyRef.current.replyID == `reply-${threadComment.commentID}`)) {
            const reply: IThreadItem[] = [constructReplyFromComment(threadComment)];
            if (isNestedReply(reply[0])) {
                replyRef.current = reply[0];
            }
            const newThreadStructure = addChildren({
                path,
                initialThreadStructure: threadStructure,
                partial: reply,
                preserveHoles: true,
                type: "prepend",
            });
            return newThreadStructure;
        }
        return threadStructure;
    };

    const removeReplyForm = (
        threadReply: IThreadItemReply,
        threadStructure: IThreadItem[],
        resetReplyRef: boolean = true,
    ) => {
        const location = `${threadReply.path}`.split(".");
        const partial = getThreadPartial(location, threadStructure);
        if (partial) {
            const modifiedPartial = partial.filter((child) => child.type !== "reply");
            const threadWithoutReply = addChildren({
                path: `${threadReply.path}`,
                initialThreadStructure: threadStructure,
                partial: modifiedPartial,
                replace: true,
            });
            if (resetReplyRef) {
                replyRef.current = null;
            }
            return threadWithoutReply;
        }
        return threadStructure;
    };

    const showReplyForm = (threadComment: IThreadItemComment) => {
        // Ensure we never add new reply forms to comments that already have one
        if (!threadComment.children?.some((child) => child.type === "reply")) {
            const newThreadStructure = addReplyForm(threadComment, threadStructure);
            setThreadStructure(newThreadStructure);
        }
    };

    const switchReplyForm = (threadComment: IThreadItemComment) => {
        if (replyRef.current && replyRef.current.replyID !== `reply-${threadComment.commentID}`) {
            const threadWithoutOldReply = removeReplyForm(replyRef.current, threadStructure);
            const threadWithNewReply = addReplyForm(threadComment, threadWithoutOldReply);
            setThreadStructure(threadWithNewReply);
        }
    };

    const removeReplyFromThread = (threadReply: IThreadItemReply, resetReplyRef: boolean = true) => {
        const newThreadStructure = removeReplyForm(threadReply, threadStructure, resetReplyRef);
        setThreadStructure(newThreadStructure);
    };

    const addReplyToThread = (reply: IThreadItemReply, comment: IComment, mobile = false) => {
        const newThreadItem: IThreadItemComment = {
            type: "comment",
            parentCommentID: reply.parentCommentID,
            depth: reply.depth,
            commentID: comment.commentID,
            path: `${reply.path}.${comment.commentID}`,
        };

        let newThread = addChildren({
            path: `${reply.path}`,
            initialThreadStructure: threadStructure,
            partial: [newThreadItem],
            type: "prepend",
            replace: false,
            preserveHoles: true,
            preserveReply: false,
        });

        if (!mobile && replyRef.current) {
            newThread = removeReplyForm(replyRef.current, newThread);
        }

        void updateComment(comment.commentID, comment);
        setThreadStructure(newThread);
    };

    const selectableCommentIDs = useMemo(() => {
        const visibleCommentIDs: Array<IComment["commentID"]> = [];
        function recursivelyFindThreadComments(threadItems: IThreadItem[]) {
            threadItems.forEach((threadItem) => {
                if (isNestedComment(threadItem)) {
                    visibleCommentIDs.push(threadItem.commentID);
                    if (threadItem.children) {
                        recursivelyFindThreadComments(threadItem.children);
                    }
                }
            });
        }
        recursivelyFindThreadComments(threadStructure);
        return visibleCommentIDs;
    }, [threadStructure]);

    /**
     * Recursively remove deleted item from thread structure
     */
    const removeDeletedItemsFromThread = (
        currentThreadStructure: IThreadItem[],
        commentIDs: Array<IComment["commentID"]>,
    ) => {
        return currentThreadStructure.filter((threadItem) => {
            if (isNestedComment(threadItem) && commentIDs.includes(threadItem.commentID)) {
                return false;
            }
            if (isNestedComment(threadItem) && threadItem.children && threadItem.children.length > 0) {
                threadItem.children = removeDeletedItemsFromThread(threadItem.children, commentIDs);
            }
            return true;
        });
    };

    return (
        <NestedCommentContext.Provider
            value={{
                threadDepthLimit,
                threadStructure,
                commentsByID,
                getComment,
                addToThread,
                collapseThreadAtPath,
                updateComment,
                updateCommentList,
                lastChildRefsByID,
                addLastChildRefID,
                currentReplyFormRef: replyRef,
                showReplyForm,
                switchReplyForm,
                addReplyToThread,
                removeReplyFromThread,
                showOPTag,
                authorBadges,
                constructReplyFromComment,
                collapsedThreadPartialsByPath,
                selectableCommentIDs,
                CommentActionsComponent,
            }}
        >
            {children}
        </NestedCommentContext.Provider>
    );
});
