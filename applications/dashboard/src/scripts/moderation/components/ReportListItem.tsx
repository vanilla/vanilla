/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IReport } from "@dashboard/moderation/CommunityManagementTypes";
import { isHtmlEmpty } from "@dashboard/moderation/communityManagmentUtils";
import { DismissReportModal } from "@dashboard/moderation/components/DismissReportModal";
import { reportListItemClasses } from "@dashboard/moderation/components/ReportListItem.classes";
import { ReportRecordMeta } from "@dashboard/moderation/components/ReportRecordMeta";
import { cx } from "@emotion/css";
import { CollapsableContent } from "@library/content/CollapsableContent";
import DateTime from "@library/content/DateTime";
import Translate from "@library/content/Translate";
import UserContent from "@library/content/UserContent";
import { deletedUserFragment } from "@library/features/__fixtures__/User.Deleted";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import Heading from "@library/layout/Heading";
import { ListItem, ListItemContext } from "@library/lists/ListItem";
import { ListItemLayout } from "@library/lists/ListItem.variables";
import { MetaIcon, MetaItem } from "@library/metas/Metas";
import Notice from "@library/metas/Notice";
import { Tag } from "@library/metas/Tags";
import { TagPreset } from "@library/metas/Tags.variables";
import ProfileLink from "@library/navigation/ProfileLink";
import SmartLink from "@library/routing/links/SmartLink";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@vanilla/i18n";
import { LocationDescriptor } from "history";
import { useState } from "react";
import BlurContainer from "@dashboard/moderation/components/BlurContainerUserContent";

interface IProps {
    to: LocationDescriptor;
    report: IReport;
    onEscalate(report: IReport): void;
}

export function ReportListItem(props: IProps) {
    const { to, report, onEscalate } = props;
    const [showDismiss, setShowDismiss] = useState(false);
    const classes = reportListItemClasses();

    return (
        <div className={classes.container}>
            <header className={classes.header}>
                <span className={classes.titleGroup}>
                    <SmartLink to={to}>
                        <Heading depth={3}>
                            <Translate source={"Report from <0/>"} c0={report.insertUser?.name} />
                        </Heading>
                    </SmartLink>
                </span>
                <div className={classes.actions}>
                    {report.reasons.map((reason) => (
                        <ToolTip
                            key={`${report.recordName}-${report.recordID}-${reason.reportReasonID}`}
                            label={reason.description}
                        >
                            <span>
                                <Tag preset={TagPreset.STANDARD}>{reason.name}</Tag>
                            </span>
                        </ToolTip>
                    ))}
                </div>
            </header>
            {/* Report */}
            <ListItem
                as={"div"}
                className={cx(classes.reportItem, classes.adminStyleOverrides)}
                descriptionClassName={classes.recordOverrides}
                description={
                    isHtmlEmpty(report.noteHtml) ? (
                        <Translate source={"No report notes were provided by <0/>"} c0={report.insertUser?.name} />
                    ) : (
                        <UserContent content={report.noteHtml} />
                    )
                }
                truncateDescription={false}
                metas={
                    <span style={{ display: "flex", alignItems: "center", marginBottom: 16 }}>
                        <MetaItem className={classes.reporterProfile}>
                            <ProfileLink userFragment={report.insertUser ?? deletedUserFragment()} isUserCard>
                                <UserPhoto size={UserPhotoSize.XSMALL} userInfo={report.insertUser} />
                                {report.insertUser?.name}
                            </ProfileLink>
                        </MetaItem>
                        <MetaItem>
                            <MetaIcon icon="meta-time" style={{ marginLeft: -4 }} />
                            <DateTime timestamp={report.dateInserted}></DateTime>
                        </MetaItem>
                    </span>
                }
            />

            {/* Record */}
            <ListItemContext.Provider value={{ layout: ListItemLayout.TITLE_METAS_DESCRIPTION }}>
                <BlurContainer>
                    <ListItem
                        className={cx(classes.recordItem, classes.adminStyleOverrides)}
                        as={"div"}
                        name={<>{report.recordName}</>}
                        url={report.recordUrl}
                        description={
                            <CollapsableContent maxHeight={50} gradientClasses={classes.gradientOverride}>
                                <UserContent content={report.recordHtml} moderateEmbeds />{" "}
                            </CollapsableContent>
                        }
                        truncateDescription={false}
                        metas={<ReportRecordMeta record={report} />}
                    />
                </BlurContainer>
            </ListItemContext.Provider>
            <div className={classes.reportSummaryContainer}>
                <div className={classes.reportSummary}>
                    {report.status === "new" ? (
                        <>
                            <Button
                                buttonType={ButtonTypes.TEXT}
                                onClick={() => {
                                    setShowDismiss(true);
                                }}
                            >
                                {t("Dismiss")}
                            </Button>
                            <Button
                                buttonType={ButtonTypes.TEXT_PRIMARY}
                                onClick={() => {
                                    onEscalate(report);
                                }}
                            >
                                {t("Escalate")}
                            </Button>
                        </>
                    ) : (
                        <>
                            <Notice>{report.status}</Notice>
                        </>
                    )}
                </div>
            </div>
            <DismissReportModal
                reportIDs={report.reportID}
                isVisible={showDismiss}
                onClose={() => {
                    setShowDismiss(false);
                }}
            />
        </div>
    );
}
