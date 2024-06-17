import { ITriageRecord } from "@dashboard/moderation/CommunityManagementTypes";
import { reportGroupClasses } from "@dashboard/moderation/components/ReportGroup.classes";
import DateTime from "@library/content/DateTime";
import Translate from "@library/content/Translate";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import { userPhotoVariables } from "@library/headers/mebox/pieces/userPhotoStyles";
import { RightChevronSmallIcon } from "@library/icons/common";
import Heading from "@library/layout/Heading";
import { ListItem } from "@library/lists/ListItem";
import { MetaItem } from "@library/metas/Metas";
import { metasClasses } from "@library/metas/Metas.styles";
import { Tag } from "@library/metas/Tags";
import { TagPreset } from "@library/metas/Tags.variables";
import ProfileLink from "@library/navigation/ProfileLink";
import SmartLink from "@library/routing/links/SmartLink";
import { StackedList } from "@library/stackedList/StackedList";
import { stackedListVariables } from "@library/stackedList/StackedList.variables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";

import { LocationDescriptor } from "history";

interface IProps {
    to: LocationDescriptor;
    reportGroup: ITriageRecord;
}

export function ReportGroup(props: IProps) {
    const { reportGroup, to } = props;

    const classes = reportGroupClasses();

    const reportMeta = (
        <>
            <MetaItem>
                <Translate
                    source="Posted by <0/> in <1/>"
                    c0={
                        <SmartLink to={`${reportGroup.recordUser}`} className={metasClasses().metaLink}>
                            {reportGroup.recordUser?.name}
                        </SmartLink>
                    }
                    c1={
                        <SmartLink to={`${reportGroup.placeRecordUrl}`} className={metasClasses().metaLink}>
                            {reportGroup.placeRecordName}
                        </SmartLink>
                    }
                />
            </MetaItem>
            {reportGroup.recordWasEdited && (
                <MetaItem>
                    <Tag preset={TagPreset.COLORED}>{t("Edited")}</Tag>
                </MetaItem>
            )}
            {reportGroup.recordIsLive && (
                <MetaItem>
                    <Tag preset={TagPreset.COLORED}>{t("Visible")}</Tag>
                </MetaItem>
            )}
        </>
    );

    return (
        <div className={classes.container}>
            <header className={classes.header}>
                <span className={classes.titleGroup}>
                    <SmartLink to={to}>
                        <Heading depth={2}>{reportGroup.recordName}</Heading>
                    </SmartLink>
                    <ToolTip label={t("Go to post")}>
                        <SmartLink to={reportGroup.recordUrl}>
                            <Icon icon={"meta-external"} />
                        </SmartLink>
                    </ToolTip>
                    <MetaItem>
                        <Tag preset={TagPreset.GREYSCALE}>
                            <Translate source={"<0/> Reports"} c0={reportGroup.countReports} />
                        </Tag>
                    </MetaItem>
                </span>
                <div className={classes.actions}>
                    <Button>{t("Dismiss")}</Button>
                    <Button buttonType={ButtonTypes.PRIMARY}>{t("Escalate")}</Button>
                </div>
            </header>
            <ListItem
                as={"div"}
                descriptionClassName={classes.recordOverrides}
                url={reportGroup.recordUrl}
                icon={
                    <>
                        {reportGroup?.recordUser ? (
                            <ProfileLink userFragment={reportGroup.recordUser} isUserCard>
                                <UserPhoto size={UserPhotoSize.SMALL} userInfo={reportGroup.recordUser} />
                            </ProfileLink>
                        ) : (
                            <> </>
                        )}
                    </>
                }
                description={reportGroup.recordExcerpt}
                truncateDescription={false}
                metas={reportMeta}
            />
            <div className={classes.reportSummaryContainer}>
                <div className={classes.reportSummary}>
                    <div>
                        <span>
                            <Translate
                                source="Last reported <0/>"
                                c0={<DateTime timestamp={reportGroup.dateLastReport} />}
                            />
                        </span>
                        <span>
                            {reportGroup.reportReasons.map((reason) => (
                                <ToolTip
                                    key={`${reportGroup.recordID}-${reason.reportReasonID}`}
                                    label={reason.description}
                                >
                                    <span>
                                        <Tag preset={TagPreset.STANDARD}>{reason.name}</Tag>
                                    </span>
                                </ToolTip>
                            ))}
                        </span>
                    </div>
                    <div className={classes.reporterBlock}>
                        <span>
                            <StackedList
                                themingVariables={{
                                    ...stackedListVariables("reporters"),
                                    sizing: {
                                        ...stackedListVariables("reporters").sizing,
                                        width: userPhotoVariables().sizing.small,
                                        offset: 10,
                                    },
                                    plus: {
                                        ...stackedListVariables("reporters").plus,
                                        font: globalVariables().fontSizeAndWeightVars("medium"),
                                    },
                                }}
                                data={reportGroup.reportUsers}
                                maxCount={3}
                                extra={reportGroup.countReports - 3}
                                ItemComponent={(user) => <UserPhoto size={UserPhotoSize.SMALL} userInfo={user} />}
                            />
                        </span>
                        <SmartLink to={to}>
                            <Translate source={"View all <0/> reports"} c0={reportGroup.countReports} />
                            <RightChevronSmallIcon />
                        </SmartLink>
                    </div>
                </div>
            </div>
        </div>
    );
}
