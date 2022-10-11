/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    fetchProfileField,
    fetchProfileFields,
    patchProfileField,
    postProfileField,
} from "@dashboard/userProfiles/state/UserProfiles.actions";
import { useUserProfilesDispatch, useUserProfilesSelector } from "@dashboard/userProfiles/state/UserProfiles.slice";
import {
    PatchProfileFieldParams,
    PostProfileFieldParams,
    ProfileField,
} from "@dashboard/userProfiles/types/UserProfiles.types";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { stableObjectHash } from "@vanilla/utils";
import { useEffect, useMemo } from "react";

export function useProfileFields(params = {}): ILoadable<ProfileField[]> {
    const dispatch = useUserProfilesDispatch();

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
        return Object.values(profileFieldsByApiName);
    }, [profileFieldApiNames, profileFieldsByApiName]);

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
