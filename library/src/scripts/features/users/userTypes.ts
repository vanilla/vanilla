import { ILoadable } from "@library/@types/api/core";
import { IMe, IMeCounts, IUser, IInvitees } from "@library/@types/api/users";
import { IUserSuggestionState } from "@library/features/users/suggestion/UserSuggestionModel";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import { RecordID } from "@vanilla/utils";

export interface IInjectableUserState {
    currentUser: ILoadable<IMe>;
}

export interface IPermission {
    type: string;
    id: number | null;
    permissions: Record<string, boolean>;
}

type JunctionType = string;
type JunctionID = number;
type JunctionAliasID = string; // Generally a stringified number.

export interface IPermissions {
    isAdmin?: boolean;
    isSysAdmin?: boolean;
    permissions: IPermission[];
    junctions?: Record<JunctionType, JunctionID[]>;
    junctionAliases?: Record<JunctionType, Record<JunctionAliasID, JunctionID>>;
}
export interface IUsersState {
    current: ILoadable<IMe>;
    permissions: ILoadable<IPermissions>;
    countInformation: {
        counts: IMeCounts;
        lastRequested: number | null; // A timestamp of the last time we received this count data.
    };
    suggestions: IUserSuggestionState;
    usersByID: Record<number, ILoadable<IUser>>;
    usersInvitationsByID: Record<number, IInvitationState>;
    postFormSubmit: ILoadable<{}>;
    patchStatusByUserID: Record<number, ILoadable<{}>>;
    patchStatusByPatchID: Record<RecordID, ILoadable<{}>>;
}

export interface IInvitationState {
    userIDs: number[];
    emails: string[];
    emailsString: string;
    invitees: IComboBoxOption[];
    results: ILoadable<IInvitees[]>;
}

export interface IUsersStoreState {
    users: IUsersState;
}
