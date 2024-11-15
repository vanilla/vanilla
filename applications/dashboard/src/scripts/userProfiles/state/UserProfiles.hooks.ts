/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    deleteProfileField,
    fetchProfileField,
    fetchProfileFields,
    fetchUserProfileFields,
    patchUserProfileFields,
    patchProfileField,
    postProfileField,
    putProfileFieldsSorts,
} from "@dashboard/userProfiles/state/UserProfiles.actions";
import {
    useUserProfilesDispatch,
    useUserProfilesSelector,
    useUserProfilesSelectorByID,
} from "@dashboard/userProfiles/state/UserProfiles.slice";
import {
    FetchProfileFieldsParams,
    PatchProfileFieldParams,
    PostProfileFieldParams,
    ProfileField,
    PatchUserProfileFieldsParams,
    PutUserProfileFieldsParams,
    UserProfileFields,
    ProfileFieldVisibility,
} from "@dashboard/userProfiles/types/UserProfiles.types";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { RecordID, stableObjectHash } from "@vanilla/utils";
import { useEffect, useMemo } from "react";

export function useProfileFields(
    params: FetchProfileFieldsParams = {},
    options?: {
        filterPermissions?: true; //if we only want the profile fields whose corresponding data the session user has permission to view
        isOwnProfile?: boolean; //whether we're getting fields for a user's own profile.
    },
): ILoadable<ProfileField[]> {
    const dispatch = useUserProfilesDispatch();

    const { hasPermission } = usePermissionsContext();
    const filterPermissions = options?.filterPermissions ?? false;
    const isOwnProfile = options?.isOwnProfile ?? false;

    const paramsAreEmpty = Object.keys(params).length === 0;
    const paramHash = stableObjectHash(params ?? {});

    const profileFieldApiNamesByParamHash = useUserProfilesSelector(
        ({ userProfiles: { profileFieldApiNamesByParamHash } }) => profileFieldApiNamesByParamHash,
    );

    const profileFieldsByApiName = useUserProfilesSelector(
        ({ userProfiles: { profileFieldsByApiName } }) => profileFieldsByApiName,
    );

    const profileFieldApiNames = profileFieldApiNamesByParamHash[paramHash];

    const profileFields = useMemo<ProfileField[] | undefined>(() => {
        if (!profileFieldApiNames || profileFieldApiNames?.status !== LoadStatus.SUCCESS) {
            return undefined;
        }
        return (
            Object.values(profileFieldsByApiName)
                // if params are empty, that means we want to retrieve all the profile fields, including any that might be added while this hook is being used.
                .filter(paramsAreEmpty ? () => true : ({ apiName }) => profileFieldApiNames.data!.includes(apiName))
                .filter(
                    filterPermissions
                        ? (profileField) => {
                              switch (profileField.visibility) {
                                  case ProfileFieldVisibility.PUBLIC:
                                      return true;
                                  case ProfileFieldVisibility.PRIVATE:
                                      return hasPermission("personalInfo.view") || isOwnProfile;
                                  case ProfileFieldVisibility.INTERNAL:
                                      return hasPermission("internalInfo.view");
                              }
                          }
                        : () => true,
                )
        );
    }, [profileFieldApiNames, profileFieldsByApiName, filterPermissions]);

    useEffect(() => {
        if (params && !profileFieldApiNames) {
            dispatch(fetchProfileFields(params));
        }
    }, [profileFieldApiNames, params]);

    return profileFieldApiNames?.error
        ? {
              status: LoadStatus.ERROR,
          }
        : profileFields
        ? {
              status: profileFieldApiNames!.status,
              data: profileFields,
          }
        : {
              status: LoadStatus.LOADING,
          };
}

export function useProfileField(profileFieldApiName?: ProfileField["apiName"]): ProfileField | undefined {
    const dispatch = useUserProfilesDispatch();

    const profileFieldsByApiName = useUserProfilesSelector(
        ({ userProfiles: { profileFieldsByApiName } }) => profileFieldsByApiName,
    );

    const profileField = profileFieldApiName ? profileFieldsByApiName[profileFieldApiName] : undefined;

    useEffect(() => {
        if (profileFieldApiName && !profileField) {
            dispatch(fetchProfileField(profileFieldApiName));
        }
    }, [profileField, profileFieldApiName]);

    return profileField;
}

export function usePostProfileField() {
    const dispatch = useUserProfilesDispatch();
    return async function (params: PostProfileFieldParams) {
        return await dispatch(postProfileField(params)).unwrap();
    };
}

export function usePatchProfileField() {
    const dispatch = useUserProfilesDispatch();

    return async function (params: PatchProfileFieldParams) {
        return await dispatch(patchProfileField({ ...params })).unwrap();
    };
}

/**
 * Get the profile field values for a given UserID
 */
export function useProfileFieldsByUserID(userID: RecordID): ILoadable<UserProfileFields> {
    const dispatch = useUserProfilesDispatch();

    const profileFieldsByUserIDs = useUserProfilesSelectorByID(
        ({ userProfiles: { profileFieldsByUserID } }) => profileFieldsByUserID,
    );

    const profileFieldStatus = profileFieldsByUserIDs[userID]?.status;

    const userProfileFields = useMemo(() => {
        if (profileFieldsByUserIDs[userID] && profileFieldsByUserIDs[userID]?.data) {
            return profileFieldsByUserIDs[userID].data;
        }
        return undefined;
    }, [profileFieldsByUserIDs, userID]);

    useEffect(() => {
        if (userID && !userProfileFields) {
            dispatch(fetchUserProfileFields({ userID }));
        }
    }, [userProfileFields, userID]);

    return {
        status: profileFieldStatus,
        ...(profileFieldStatus === LoadStatus.SUCCESS && { data: userProfileFields }),
    };
}

/**
 * Delete the selected profile field by apiName
 */
export function useDeleteProfileField() {
    const dispatch = useUserProfilesDispatch();

    return async function (apiName: ProfileField["apiName"]) {
        return await dispatch(deleteProfileField(apiName)).unwrap();
    };
}

export function usePatchProfileFieldByUserID(userID: RecordID) {
    const dispatch = useUserProfilesDispatch();

    return async function (params: PatchUserProfileFieldsParams) {
        return await dispatch(patchUserProfileFields({ userID, ...params })).unwrap();
    };
}

export function usePutProfileFieldsSorts() {
    const dispatch = useUserProfilesDispatch();

    return async function (params: PutUserProfileFieldsParams) {
        return await dispatch(putProfileFieldsSorts(params)).unwrap();
    };
}
