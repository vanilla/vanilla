/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { ReactNode, useContext, useState } from "react";
import { IUser } from "@library/@types/api/users";

interface IFollowedContentContext {
    userID: number;
    additionalFollowedContent: Array<{ contentName: string; contentRenderer: React.ComponentType }>;
}

export const FollowedContentContext = React.createContext<IFollowedContentContext>({
    userID: 1,
    additionalFollowedContent: [],
});

/**
 * This is responsible for adding additional content to Followed Content (e.g. groups)
 */
let additionalFollowedContent: Array<{ contentName: string; contentRenderer: React.ComponentType }> = [];
FollowedContentProvider.addAdditionalContent = (additionalContent) => {
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
