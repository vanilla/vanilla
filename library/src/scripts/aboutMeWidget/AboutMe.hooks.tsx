/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useProfileFieldByUserID, useProfileFields } from "@dashboard/userProfiles/state/UserProfiles.hooks";
import { ProfileField, ProfileFieldVisibility } from "@dashboard/userProfiles/types/UserProfiles.types";
import { css } from "@emotion/css";
import { LoadStatus } from "@library/@types/api/core";
import { IDataListNode } from "@library/dataLists/DataList";
import { globalVariables } from "@library/styles/globalStyleVars";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { labelize, RecordID } from "@vanilla/utils";
import React, { ReactNode, useMemo } from "react";

export function useUserProfileFields(userID: RecordID) {
    const userProfileFields = useProfileFieldByUserID(userID);
    const profileFieldConfigs = useProfileFields();

    const isLoading = useMemo<boolean>(() => {
        return [userProfileFields.status, profileFieldConfigs.status].some((status) =>
            [LoadStatus.PENDING, LoadStatus.LOADING].includes(status),
        );
    }, [userProfileFields, profileFieldConfigs]);

    // Lets make the configured profile fields easier to lookup
    const profileConfigsByAPIName = useMemo<Record<ProfileField["apiName"], ProfileField>>(() => {
        if (profileFieldConfigs.data) {
            return Object.fromEntries(
                profileFieldConfigs.data.map((profileFieldConfig) => [profileFieldConfig.apiName, profileFieldConfig]),
            );
        }
        return {};
    }, [profileFieldConfigs]);

    const createListLabel = (labelText: string, visibility: ProfileFieldVisibility): ReactNode => {
        const iconClasses = css({
            height: "100%",
            width: "auto",
            verticalAlign: "bottom",
            lineHeight: globalVariables().lineHeights.base,
            marginLeft: 4,
            marginBottom: -2,
        });

        const icon = () => {
            switch (visibility) {
                case ProfileFieldVisibility.INTERNAL: {
                    return (
                        <ToolTip label={t("Internal Field - Only some users can see this")}>
                            <span>
                                <Icon className={iconClasses} icon="profile-crown" />
                            </span>
                        </ToolTip>
                    );
                }
                case ProfileFieldVisibility.PRIVATE: {
                    return (
                        <ToolTip label={t("Private Field - Only you and some users can see this")}>
                            <span>
                                <Icon className={iconClasses} icon="profile-lock" />
                            </span>
                        </ToolTip>
                    );
                }
                default: {
                    return <></>;
                }
            }
        };

        return (
            <span>
                {labelize(labelText)}
                {icon()}
            </span>
        );
    };

    const profileFields = useMemo<IDataListNode[] | null>(() => {
        // Ensure both are loaded to do the look up
        const isBothLoaded = [
            userProfileFields.status === LoadStatus.SUCCESS,
            profileFieldConfigs.status === LoadStatus.SUCCESS,
        ].every((status) => status === true);

        if (isBothLoaded) {
            return Object.keys(userProfileFields?.data ?? {}).map((apiName: ProfileField["apiName"]) => {
                return {
                    key: createListLabel(
                        profileConfigsByAPIName[apiName].label ?? apiName,
                        profileConfigsByAPIName[apiName].visibility,
                    ),
                    value: userProfileFields?.data?.[apiName],
                };
            });
        }

        return null;
    }, [userProfileFields, profileConfigsByAPIName]);

    return {
        isLoading,
        profileFields,
    };
}
