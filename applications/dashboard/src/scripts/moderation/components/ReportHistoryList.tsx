/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IReport } from "@dashboard/moderation/CommunityManagementTypes";
import { reportHistoryListClasses } from "@dashboard/moderation/components/ReportHistoryList.classes";
import { cx } from "@emotion/css";
import DateTime from "@library/content/DateTime";
import TruncatedText from "@library/content/TruncatedText";
import UserContent from "@library/content/UserContent";
import { ErrorBoundary } from "@library/errorPages/ErrorBoundary";
import { deletedUserFragment } from "@library/features/users/constants/userFragment";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import { useSection } from "@library/layout/LayoutContext";
import FrameHeader from "@library/layout/frame/FrameHeader";
import { MetaItem } from "@library/metas/Metas";
import { Tag } from "@library/metas/Tags";
import { TagPreset } from "@library/metas/Tags.variables";
import ProfileLink from "@library/navigation/ProfileLink";
import { filterPanelClasses } from "@library/search/panels/filterPanel.styles";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@vanilla/i18n";
import { useMemo } from "react";

interface IProps {
    reports: IReport[];
    onReportSelected?: (report: IReport) => void;
    activeReportIDs: number[];
}

interface IReportGroup {
    date: string;
    reports: IReport[];
}

export function ReportHistoryList(props: IProps) {
    const { reports, activeReportIDs, onReportSelected } = props;
    const frameClasses = filterPanelClasses(useSection().mediaQueries);
    const classes = reportHistoryListClasses();

    const groupedReports = useMemo<IReportGroup[]>(() => {
        return reports.reduce((acc: IReportGroup[], report: IReport) => {
            const date = new Date(report.dateInserted).toLocaleDateString();
            const groupingIndex = acc.findIndex((group) => group.date === date);
            if (groupingIndex < 0) {
                return [
                    ...acc,
                    {
                        date,
                        reports: [report],
                    },
                ];
            } else {
                acc[groupingIndex].reports.push(report);
                return acc;
            }
        }, []);
    }, [reports]);

    return (
        <ErrorBoundary>
            <div className={classes.root}>
                <FrameHeader
                    titleID={"Reports"}
                    title={t("Reports")}
                    className={frameClasses.header}
                    titleClass={frameClasses.title}
                />
                {!!reports?.length &&
                    !!groupedReports.length &&
                    groupedReports?.map((group) => {
                        return (
                            <div key={group.date} className={classes.group}>
                                <span className={classes.title}>
                                    {group.date === new Date().toLocaleDateString() ? (
                                        t("Today")
                                    ) : (
                                        <DateTime mode={"relative"} timestamp={group.date} />
                                    )}
                                </span>
                                <ol className={classes.reportList}>
                                    {group.reports.map((report) => (
                                        <ReportHistoryListItem
                                            key={report.reportID}
                                            report={report}
                                            isActive={activeReportIDs.includes(report.reportID)}
                                            setActive={() => {
                                                onReportSelected?.(report);
                                            }}
                                        />
                                    ))}
                                </ol>
                            </div>
                        );
                    })}
                {reports && reports?.length === 0 && (
                    <div className={classes.emptyState}>
                        <span className={classes.emptyHeadline}>
                            {t("There are currently no reports for this post.")}
                        </span>
                        <span className={classes.emptyByline}>
                            {t("A list of reports associated with this post will appear here.")}
                        </span>
                    </div>
                )}
            </div>
        </ErrorBoundary>
    );
}
function ReportHistoryListItem(props: { report: IReport; isActive: boolean; setActive: () => void }) {
    const { report, isActive, setActive } = props;
    const classes = reportHistoryListClasses();
    return (
        <li className={cx(classes.reportListItem, isActive ? classes.active : "")}>
            <button onClick={() => setActive()} aria-pressed={isActive}>
                <div className={classes.metaItems}>
                    {report.reasons.map((reason) => (
                        <ToolTip key={`${reason.reportID}-${reason.reportReasonID}`} label={reason.description}>
                            <MetaItem>
                                <Tag preset={TagPreset.STANDARD}>{reason.name}</Tag>
                            </MetaItem>
                        </ToolTip>
                    ))}
                </div>
                {report.noteHtml && (report.noteHtml as string) !== "<p></p>" && (
                    <UserContent className={classes.noteContent} vanillaSanitizedHtml={report.noteHtml} />
                )}
                <span className={classes.profileLine}>
                    <ProfileLink className={classes.userLink} userFragment={report.insertUser ?? deletedUserFragment()}>
                        <UserPhoto userInfo={report.insertUser} size={UserPhotoSize.XSMALL} />
                        <TruncatedText>{report.insertUser?.name}</TruncatedText>
                    </ProfileLink>
                    <DateTime className={classes.reportTime} mode={"fixed"} timestamp={report.dateInserted} />
                </span>
            </button>
        </li>
    );
}
