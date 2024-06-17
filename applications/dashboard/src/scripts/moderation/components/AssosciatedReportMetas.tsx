/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IReason } from "@dashboard/moderation/CommunityManagementTypes";
import { ReportReasons } from "@dashboard/moderation/components/ReportReasons";
import { css } from "@emotion/css";
import { IUserFragment } from "@library/@types/api/users";
import DateTime from "@library/content/DateTime";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import { userPhotoVariables } from "@library/headers/mebox/pieces/userPhotoStyles";
import { MetaItem, Metas } from "@library/metas/Metas";
import { StackedList } from "@library/stackedList/StackedList";
import { stackedListVariables } from "@library/stackedList/StackedList.variables";
import { globalVariables } from "@library/styles/globalStyleVars";

interface IProps {
    reasons: IReason[];
    reportingUsers?: IUserFragment[];
    countReports: number;
    dateLastReport: string;
}

const classes = {
    root: css({
        display: "flex",
        gap: 12,
        alignItems: "center",
        justifyContent: "space-between",
    }),
    userList: css({
        flex: "1 0 auto",
    }),
};

export function AssociatedReportMetas(props: IProps) {
    return (
        <div className={classes.root}>
            <Metas>
                <MetaItem>
                    Last Reported <DateTime timestamp={props.dateLastReport} />
                </MetaItem>
                <ReportReasons reasons={props.reasons} />
            </Metas>
            <span style={{ flex: 1 }}></span>
            {props.reportingUsers && (
                <StackedList
                    className={classes.userList}
                    themingVariables={{
                        ...stackedListVariables("reporters"),
                        sizing: {
                            ...stackedListVariables("reporters").sizing,
                            width: userPhotoVariables().sizing.xsmall,
                            offset: 10,
                        },
                        plus: {
                            ...stackedListVariables("reporters").plus,
                            font: globalVariables().fontSizeAndWeightVars("medium"),
                        },
                    }}
                    data={props.reportingUsers}
                    maxCount={3}
                    extra={props.countReports > 3 ? props.countReports - 3 : undefined}
                    ItemComponent={(user) => <UserPhoto size={UserPhotoSize.XSMALL} userInfo={user} />}
                />
            )}
        </div>
    );
}
