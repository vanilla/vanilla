/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { ReactNode, useContext, useState } from "react";
import { IUser } from "@library/@types/api/users";

interface IFollowedContentRegistryItem {
    contentName: string;
    contentRenderer: React.ComponentType;
}
interface IFollowedContentContext {
    userID: number;
    additionalFollowedContent: IFollowedContentRegistryItem[];
}

export const FollowedContentContext = React.createContext<IFollowedContentContext>({
    userID: 1,
    additionalFollowedContent: [],
});

/**
 * This is responsible for adding additional content to Followed Content (e.g. groups)
 */
let additionalFollowedContent: IFollowedContentRegistryItem[] = [];

FollowedContentProvider.addAdditionalContent = (additionalContent: IFollowedContentRegistryItem) => {
    additionalFollowedContent.push(additionalContent);
};

export function FollowedContentProvider(props: { userID: IUser["userID"]; children: ReactNode }) {
    const { userID, children } = props;

    return (
        <FollowedContentContext.Provider
            value={{
                userID,
                additionalFollowedContent,
            }}
        >
            {children}
        </FollowedContentContext.Provider>
    );
}

export function useFollowedContent() {
    return useContext(FollowedContentContext);
}
