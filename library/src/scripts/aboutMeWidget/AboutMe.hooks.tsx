/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useProfileFieldByUserID, useProfileFields } from "@dashboard/userProfiles/state/UserProfiles.hooks";
import { ProfileField, ProfileFieldVisibility } from "@dashboard/userProfiles/types/UserProfiles.types";
import { LoadStatus } from "@library/@types/api/core";
import { IDataListNode } from "@library/dataLists/DataList";
import { ProfileFieldVisibilityIcon } from "@dashboard/userProfiles/components/ProfileFieldVisibilityIcon";
import { RecordID } from "@vanilla/utils";
import React, { ReactNode, useMemo } from "react";
import sortBy from "lodash/sortBy";
import DateTime from "@library/content/DateTime";
import { formatDateStringIgnoringTimezone } from "@library/editProfileFields/utils";

export function useUserProfileFields(userID: RecordID) {
    const userProfileFields = useProfileFieldByUserID(userID);
    const profileFieldConfigs = useProfileFields();

    const isLoading = useMemo<boolean>(() => {
        return [userProfileFields.status, profileFieldConfigs.status].some((status) =>
            [LoadStatus.PENDING, LoadStatus.LOADING].includes(status),
        );
    }, [userProfileFields, profileFieldConfigs]);

    // Let's make the configured profile fields easier to lookup
    const profileConfigsByApiName = useMemo<Record<ProfileField["apiName"], ProfileField>>(() => {
        if (profileFieldConfigs.data) {
            return Object.fromEntries(
                profileFieldConfigs.data.map((profileFieldConfig) => [profileFieldConfig.apiName, profileFieldConfig]),
            );
        }
        return {};
    }, [profileFieldConfigs]);

    const createListLabel = (labelText: string, visibility: ProfileFieldVisibility): ReactNode => {
        return (
            <span>
                {labelText}
                <ProfileFieldVisibilityIcon visibility={visibility} />
            </span>
        );
    };

    const profileFields = useMemo<IDataListNode[] | null>(() => {
        // Ensure both are loaded to do the look up
        const dependenciesLoaded = [
            userProfileFields.status === LoadStatus.SUCCESS,
            profileFieldConfigs.status === LoadStatus.SUCCESS,
        ].every((status) => status === true);

        if (dependenciesLoaded) {
            const sortedUserProfileFields = sortBy(
                Object.keys(userProfileFields.data!),
                (apiName) => profileConfigsByApiName[apiName].sort,
            );

            return sortedUserProfileFields.map((apiName: ProfileField["apiName"]) => {
                const value = userProfileFields.data![apiName];
                const profileField = profileConfigsByApiName[apiName];

                return {
                    key: createListLabel(profileField.label ?? apiName, profileConfigsByApiName[apiName].visibility),
                    value:
                        profileField.dataType === "date" ? (
                            <DateTime timestamp={formatDateStringIgnoringTimezone(value)} />
                        ) : (
                            value
                        ),
                };
            });
        }

        return null;
    }, [userProfileFields, profileFieldConfigs]);

    return {
        isLoading,
        profileFields,
    };
}
