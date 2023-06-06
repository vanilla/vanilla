/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ILoadable, LoadStatus } from "@library/@types/api/core";
import React, { PropsWithChildren, useContext } from "react";
import { UserProfileFields } from "../types/UserProfiles.types";
import { useProfileFieldsByUserID } from "./UserProfiles.hooks";
import { RecordID } from "@vanilla/utils";

interface IUserProfileFieldsContext {
    userProfileFields: ILoadable<UserProfileFields>;
}

export const UserProfileFieldsContext = React.createContext<IUserProfileFieldsContext>({
    userProfileFields: {
        status: LoadStatus.PENDING,
    },
});

export function UserProfileFieldsContextProvider(props: PropsWithChildren<{ userID: RecordID }>) {
    const userProfileFields = useProfileFieldsByUserID(props.userID);

    return (
        <UserProfileFieldsContext.Provider
            value={{
                userProfileFields,
            }}
        >
            {props.children}
        </UserProfileFieldsContext.Provider>
    );
}

export function useUserProfileFieldsContext() {
    return useContext(UserProfileFieldsContext);
}
