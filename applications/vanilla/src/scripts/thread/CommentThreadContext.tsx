/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IComment } from "@dashboard/@types/api/comment";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import apiv2 from "@library/apiv2";
import { IThreadItem, IThreadResponse } from "@vanilla/addon-vanilla/thread/@types/CommentThreadTypes";
import React, { useContext, useEffect, useState } from "react";

interface ICommentThreadContext {
    discussion: IDiscussion;
    threadStructure: IThreadItem[];
    commentsByID: Record<IComment["commentID"], IComment>;
    getComment: (commentID: IComment["commentID"]) => IComment | undefined;
    updateThread: (apiUrl: string, path: string) => void;
}

export const CommentThreadContext = React.createContext<ICommentThreadContext>({
    discussion: {} as IDiscussion,
    threadStructure: [],
    commentsByID: {},
    getComment: () => undefined,
    updateThread: () => {},
});

export function useCommentThread() {
    return useContext(CommentThreadContext);
}

type CommentThreadProviderProps = IThreadResponse &
    React.PropsWithChildren<{
        discussion: IDiscussion;
    }>;

export function CommentThreadProvider(props: CommentThreadProviderProps) {
    const { children } = props;

    const [threadStructure, setThreadStructure] = useState<IThreadResponse["threadStructure"]>(props.threadStructure);
    const [commentsByID, setCommentsByID] = useState<IThreadResponse["commentsByID"]>(props.commentsByID);

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

    const fetchPartialThread = async (apiUrl: string) => {
        const response = await apiv2(apiUrl);
        return response.data;
    };

    const updateThread = async (apiUrl: string, path: string) => {
        if (path) {
            // Get the partial thread
            const partial = await fetchPartialThread(apiUrl);
            // Update the comment store
            setCommentsByID((prev) => ({ ...prev, ...partial.commentsByID }));

            // Update the thread structure
            let newThreadStructure = [...threadStructure];
            let location = `${path}`.split(".");

            const injectChildren = (children: IThreadItem[]) => {
                children.forEach((child) => {
                    // If the child is a comment and the commentID matches the location
                    if (location.length > 0) {
                        if (child.type === "comment" && `${child.commentID}` === location[0]) {
                            // Remove the first element from the location array
                            location.shift();
                            if (location.length > 0) {
                                // TODO: Need a typeguard here
                                injectChildren(child?.["children"] ?? []);
                            } else {
                                // Replace the children with the new children
                                // TODO: Added deduping here
                                const newKids = child.children?.filter((c) => c.type === "comment") ?? [];
                                child.children = [...newKids, ...partial.threadStructure];
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
        }
    };

    return (
        <CommentThreadContext.Provider
            value={{
                discussion: props.discussion,
                threadStructure,
                commentsByID,
                getComment,
                updateThread,
            }}
        >
            {children}
        </CommentThreadContext.Provider>
    );
}
