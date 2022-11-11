/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */
import React, { ReactNode, useMemo } from "react";
import { t } from "@vanilla/i18n";
import { getMeta } from "@library/utility/appUtils";
import { hasPermission } from "@library/features/users/Permission";
import StatTable from "@library/stats/StatTable";
import { profileAnalyticsClasses } from "./ProfileAnalyticsWidget.styles";
import DateTime from "@library/content/DateTime";
import SmartLink from "@library/routing/links/SmartLink";
import { Icon } from "@vanilla/icons";

export type IUserAnalytics = {
    points?: number;
    posts?: number;
    visits?: number;
    joinDate?: string;
    lastActive?: string;
};
export interface IUserAnalyticsProps {
    userInfo: IUserAnalytics;
    userID: number;
}

export function ProfileAnalyticsWidget(props: IUserAnalyticsProps) {
    const { userID } = props;
    const classes = profileAnalyticsClasses();

    const { joinDate, lastActive, ...userInfo } = props.userInfo;
    const formattedData = {
        ...userInfo,
        ...(joinDate && { joinDate: <DateTime timestamp={joinDate} /> }),
        ...(lastActive && { lastActive: <DateTime timestamp={lastActive} /> }),
    };

    const isNewAnalyticsEnabled = getMeta("featureFlags.NewAnalytics.Enabled");
    const link: ReactNode = useMemo(() => {
        if (userID && isNewAnalyticsEnabled && hasPermission(["data.view", "dashboards.manage"])) {
            return (
                <div className={classes.link}>
                    <SmartLink to={`/analytics/v2/dashboards/drilldown/user?userID=${userID}`} target={"_blank"}>
                        {t("Check Analytics Data")}
                        <Icon icon="meta-external" />
                    </SmartLink>
                </div>
            );
        }
        return null;
    }, [isNewAnalyticsEnabled, userID, classes]);

    return (
        <div>
            <StatTable title={"Analytics"} data={formattedData} />
            {link}
        </div>
    );
}
