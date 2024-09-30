/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IComment } from "@dashboard/@types/api/comment";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import apiv2 from "@library/apiv2";
import { IThreadItem, IThreadResponse } from "@vanilla/addon-vanilla/thread/@types/CommentThreadTypes";
import CommentsApi from "@vanilla/addon-vanilla/thread/CommentsApi";
import { deduplicateThreadItems } from "@vanilla/addon-vanilla/thread/threadUtils";
import { logDebug } from "@vanilla/utils";
import React, { useContext, useEffect, useState } from "react";

type RefsByID = Record<string, React.RefObject<HTMLElement>>;
interface ICommentThreadContext {
    discussion: IDiscussion;
    threadStructure: IThreadItem[];
    commentsByID: Record<IComment["commentID"], IComment>;
    getComment: (commentID: IComment["commentID"]) => IComment | undefined;
    updateComment: (commentID: IComment["commentID"], updatedComment?: IComment) => void;
    updateThread: (apiUrl: string, path: string) => void;
    lastChildRefsByID: RefsByID;
    addLastChildRefID: (commentID: string, ref: React.RefObject<HTMLDivElement>) => void;
}

export const CommentThreadContext = React.createContext<ICommentThreadContext>({
    discussion: {} as IDiscussion,
    threadStructure: [],
    commentsByID: {},
    getComment: () => undefined,
    updateComment: () => {},
    updateThread: () => {},
    lastChildRefsByID: {},
    addLastChildRefID: () => {},
});

export function useCommentThread() {
    return useContext(CommentThreadContext);
}

export type CommentThreadProviderProps = IThreadResponse &
    React.PropsWithChildren<{
        discussion: IDiscussion;
    }>;

export function CommentThreadProvider(props: CommentThreadProviderProps) {
    const { children } = props;

    const [threadStructure, setThreadStructure] = useState<IThreadResponse["threadStructure"]>(props.threadStructure);
    const [commentsByID, setCommentsByID] = useState<IThreadResponse["commentsByID"]>(props.commentsByID);
    const [lastChildRefsByID, setLastChildRefsByID] = useState<RefsByID>({});

    const addHoleMeta = (
        threadItem: IThreadItem,
        parentCommentID: IComment["commentID"] | null,
        previousPath?: any,
    ) => {
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
                children: threadItem.children.map((child) => {
                    const path = previousPath ? `${previousPath}.${child.parentCommentID}` : child.parentCommentID;
                    return addHoleMeta(child, threadItem.commentID, path);
                }),
            };
        }
        return threadItem;
    };

    useEffect(() => {
        const newThreadStructure = props.threadStructure.map((threadItem) => addHoleMeta(threadItem, null));
        setThreadStructure(newThreadStructure);
    }, [props.threadStructure]);

    const getComment = (commentID: IComment["commentID"]) => {
        return commentsByID?.[commentID];
    };

    const updateComment = async (commentID: IComment["commentID"], updatedComment?: IComment) => {
        if (updatedComment) {
            setCommentsByID((prev) => {
                // Spread here to not overwrite the existing comment reactions or attachment data
                return { ...prev, [commentID]: { ...prev[commentID], ...updatedComment } };
            });
        } else {
            const comment = await CommentsApi.get(commentID);
            setCommentsByID((prev) => {
                return { ...prev, [commentID]: { ...prev[commentID], ...comment } };
            });
        }
    };

    const fetchPartialThread = async (apiUrl: string) => {
        const response = await apiv2(apiUrl);
        return response.data;
    };

    /**
     * Used fill a hole in the thread structure
     * @param apiUrl - The URL to fetch the partial thread
     * @param path - The path to the hole in the thread structure, a dot delimited string of commentIDs
     */
    const updateThread = async (apiUrl: string, path: string) => {
        if (path) {
            // Get the partial thread
            const partial = await fetchPartialThread(apiUrl);
            // Update the comment store
            setCommentsByID((prev) => ({ ...prev, ...partial.commentsByID }));

            // Update the thread structure
            let newThreadStructure = [...threadStructure];
            let location = `${path}`.split(".");

            /**
             * Recursively inject the new children into the thread structure
             */
            const injectChildren = (children: IThreadItem[]) => {
                children.forEach((child) => {
                    // If the child is a comment and the commentID matches the location
                    if (location.length > 0) {
                        if (child.type === "comment" && `${child.commentID}` === location[0]) {
                            /**
                             * Using shift so that the first element in the location array
                             * is always the next commentID we're searching for
                             */
                            location.shift();
                            // If there are more comments in the location, keep searching
                            if (location.length > 0) {
                                injectChildren(child.children ?? []);
                            } else {
                                const existingChildComments = child.children?.filter((c) => c.type === "comment") ?? [];
                                child.children = deduplicateThreadItems([
                                    ...existingChildComments,
                                    ...partial.threadStructure,
                                ]);
                            }
                        }
                    }
                });
            };

            injectChildren(newThreadStructure);

            // Make new paths
            // TODO: this probably needs to be refactored, we can pass build the paths from the location
            const newThreadStructureWithPaths = newThreadStructure.map((threadItem) => addHoleMeta(threadItem, null));
            setThreadStructure(newThreadStructureWithPaths);
        } else {
            logDebug("No path provided to updateThread");
        }
    };

    const addLastChildRefID = (commentID: string, ref: React.RefObject<HTMLElement>) => {
        setLastChildRefsByID((prev) => {
            return { ...prev, [commentID]: ref };
        });
    };

    return (
        <CommentThreadContext.Provider
            value={{
                discussion: props.discussion,
                threadStructure,
                commentsByID,
                getComment,
                updateThread,
                updateComment,
                lastChildRefsByID,
                addLastChildRefID,
            }}
        >
            {children}
        </CommentThreadContext.Provider>
    );
}
