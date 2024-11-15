/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IComment } from "@dashboard/@types/api/comment";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { IUserFragment } from "@library/@types/api/users";
import apiv2 from "@library/apiv2";
import { IError } from "@library/errorPages/CoreErrorMessages";
import {
    IThreadItem,
    IThreadItemComment,
    IThreadItemHole,
    IThreadItemReply,
    IThreadResponse,
} from "@vanilla/addon-vanilla/thread/@types/CommentThreadTypes";
import { CommentsApi } from "@vanilla/addon-vanilla/thread/CommentsApi";
import { IDraftProps } from "@vanilla/addon-vanilla/thread/components/NewCommentEditor";
import {
    deduplicateThreadItems,
    isThreadComment,
    isThreadHole,
    isThreadReply,
} from "@vanilla/addon-vanilla/thread/threadUtils";
import { logDebug } from "@vanilla/utils";
import React, { MutableRefObject, useContext, useEffect, useRef, useState } from "react";

type RefsByID = Record<string, React.RefObject<HTMLElement>>;
export interface ICommentThreadContext {
    threadDepthLimit: number;
    discussion: IDiscussion;
    threadStructure: IThreadItem[];
    commentsByID: Record<IComment["commentID"], IComment>;
    getComment: (commentID: IComment["commentID"]) => IComment | undefined;
    updateComment: (commentID: IComment["commentID"], updatedComment?: IComment) => void;
    addToThread: (apiUrl: string, path: string) => Promise<void | IError>;
    collapseThreadAtPath: (path: string) => Promise<void>;
    lastChildRefsByID: RefsByID;
    addLastChildRefID: (commentID: string, ref: React.RefObject<HTMLDivElement>) => void;
    currentReplyFormRef?: React.MutableRefObject<IThreadItemReply | null>;
    showReplyForm: (threadComment: IThreadItemComment) => void;
    switchReplyForm: (threadComment: IThreadItemComment) => void;
    addReplyToThread: (reply: IThreadItemReply, comment: IComment, mobile: boolean) => void;
    removeReplyFromThread: (threadReply: IThreadItemReply) => void;
    draft?: IDraftProps;
    showOPTag?: boolean;
    constructReplyFromComment: (threadComment: IThreadItemComment) => IThreadItemReply;
    collapsedThreadPartialsByPath?: Record<string, IThreadItem[]>;
    visibleReplyFormRef?: MutableRefObject<HTMLFormElement | null>;
    setVisibleReplyFormRef?: (ref: MutableRefObject<HTMLFormElement | null>) => void;
}

export const CommentThreadContext = React.createContext<ICommentThreadContext>({
    threadDepthLimit: 1,
    discussion: {} as IDiscussion,
    threadStructure: [],
    commentsByID: {},
    getComment: () => undefined,
    updateComment: () => {},
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
    constructReplyFromComment: () => {
        return {} as IThreadItemReply;
    },
    collapsedThreadPartialsByPath: undefined,
    visibleReplyFormRef: undefined,
    setVisibleReplyFormRef: () => {},
});

export function useCommentThread() {
    return useContext(CommentThreadContext);
}

export type CommentThreadProviderProps = IThreadResponse &
    React.PropsWithChildren<{
        discussion: IDiscussion;
        threadDepthLimit?: number;
        draft?: IDraftProps;
        showOPTag?: boolean;
        commentApiParams?: CommentsApi.IndexThreadParams;
    }>;

export function CommentThreadProvider(props: CommentThreadProviderProps) {
    const { children, threadDepthLimit = 3, draft, showOPTag, commentApiParams } = props;

    const [threadStructure, setThreadStructure] = useState<IThreadResponse["threadStructure"]>(props.threadStructure);
    const [collapsedThreadPartialsByPath, setCollapsedThreadPartialsByPath] = useState<Record<string, IThreadItem[]>>(
        {},
    );
    const [commentsByID, setCommentsByID] = useState<IThreadResponse["commentsByID"]>(props.commentsByID);
    const [lastChildRefsByID, setLastChildRefsByID] = useState<RefsByID>({});
    const [visibleReplyFormRef, setVisibleReplyFormRef] = useState<MutableRefObject<HTMLFormElement | null>>();

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
            const params = { expand: commentApiParams?.expand ?? [] };
            const comment = await CommentsApi.get(commentID, params);
            setCommentsByID((prev) => {
                return { ...prev, [commentID]: { ...prev[commentID], ...comment } };
            });
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

        return initialThreadStructure.map((threadItem) => {
            // If the child is a comment and the commentID matches the location
            if (location.length > 0) {
                if (isThreadComment(threadItem) && String(threadItem.commentID) == location[0]) {
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
                            const newChildren = deduplicateThreadItems(mergedChildren.flat()).map((newChild) =>
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
            const isComment = isThreadComment(child);
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
                    children = isThreadComment(child) ? child.children : [];
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
                const isComment = isThreadComment(curr);
                const isHole = isThreadHole(curr);
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
            const isComment = isThreadComment(curr);
            const isHole = isThreadHole(curr);
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
            if (isThreadReply(reply[0])) {
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

    const removeReplyForm = (threadReply: IThreadItemReply, threadStructure: IThreadItem[]) => {
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
            replyRef.current = null;
            return threadWithoutReply;
        }
        return threadStructure;
    };

    const showReplyForm = (threadComment: IThreadItemComment) => {
        const newThreadStructure = addReplyForm(threadComment, threadStructure);
        setThreadStructure(newThreadStructure);
    };

    const switchReplyForm = (threadComment: IThreadItemComment) => {
        if (replyRef.current && replyRef.current.replyID !== `reply-${threadComment.commentID}`) {
            const threadWithoutOldReply = removeReplyForm(replyRef.current, threadStructure);
            const threadWithNewReply = addReplyForm(threadComment, threadWithoutOldReply);
            setThreadStructure(threadWithNewReply);
        }
    };

    const removeReplyFromThread = (threadReply: IThreadItemReply) => {
        const newThreadStructure = removeReplyForm(threadReply, threadStructure);
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

        updateComment(comment.commentID, comment);
        setThreadStructure(newThread);
    };

    return (
        <CommentThreadContext.Provider
            value={{
                threadDepthLimit,
                discussion: props.discussion,
                threadStructure,
                commentsByID,
                getComment,
                addToThread,
                collapseThreadAtPath,
                updateComment,
                lastChildRefsByID,
                addLastChildRefID,
                currentReplyFormRef: replyRef,
                showReplyForm,
                switchReplyForm,
                addReplyToThread,
                removeReplyFromThread,
                draft,
                showOPTag,
                constructReplyFromComment,
                collapsedThreadPartialsByPath,
                visibleReplyFormRef,
                setVisibleReplyFormRef,
            }}
        >
            {children}
        </CommentThreadContext.Provider>
    );
}
