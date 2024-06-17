/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IReport } from "@dashboard/moderation/CommunityManagementTypes";
import { isHtmlEmpty } from "@dashboard/moderation/communityManagmentUtils";
import { ReportSnapshotModal } from "@dashboard/moderation/components/ReportSnapshotModal";
import { css } from "@emotion/css";
import DateTime from "@library/content/DateTime";
import Translate from "@library/content/Translate";
import UserContent from "@library/content/UserContent";
import { deletedUserFragment } from "@library/features/__fixtures__/User.Deleted";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { RevisionStatusPendingIcon } from "@library/icons/revision";
import { List } from "@library/lists/List";
import { ListItem } from "@library/lists/ListItem";
import { MetaItem, MetaIcon } from "@library/metas/Metas";
import { metasClasses } from "@library/metas/Metas.styles";
import { Tag } from "@library/metas/Tags";
import { TagPreset } from "@library/metas/Tags.variables";
import ProfileLink from "@library/navigation/ProfileLink";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@vanilla/i18n";
import { useState } from "react";

interface IProps {
    reports: IReport[];
}

const classes = {
    actionIconOpacity: css({
        "&": {
            "&:hover": {
                "& .snapshot": {
                    opacity: 0.4,
                },
                "& li:hover .snapshot": {
                    opacity: 1,
                },
            },
            "& .snapshot": {
                transition: `opacity 230ms ease-in-out`,
                opacity: 0.1,
            },
        },
    }),
};

export function CompactReportList(props: IProps) {
    const { reports } = props;
    const [reportSnapshot, setReportSnapshot] = useState<IReport | null>(null);
    return (
        <section>
            <List
                options={{
                    box: {
                        borderType: BorderType.SHADOW,
                    },
                    itemBox: { borderType: BorderType.SEPARATOR_BETWEEN },
                }}
                className={classes.actionIconOpacity}
            >
                {reports.map((report) => (
                    <CompactReportItem key={report.reportID} report={report} onReportView={setReportSnapshot} />
                ))}
            </List>
            <ReportSnapshotModal
                report={reportSnapshot}
                isVisible={!!reportSnapshot}
                onClose={() => setReportSnapshot(null)}
            />
        </section>
    );
}

function CompactReportItem(props: { report: IReport; onReportView: (report: IReport) => void }) {
    const { report, onReportView } = props;

    return (
        <ListItem
            description={
                isHtmlEmpty(report.noteHtml) ? (
                    <Translate source={"No report notes were provided by <0/>"} c0={report.insertUser?.name} />
                ) : (
                    <UserContent content={report.noteHtml} />
                )
            }
            truncateDescription={false}
            metas={
                <>
                    <MetaItem>
                        <Translate
                            source="Reported by <0/>"
                            c0={
                                <ProfileLink
                                    userFragment={report.insertUser ?? deletedUserFragment()}
                                    className={metasClasses().metaLink}
                                />
                            }
                        />
                    </MetaItem>
                    <MetaItem>
                        <MetaIcon icon="meta-time" style={{ marginLeft: -4 }} />
                        <DateTime timestamp={report.dateInserted}></DateTime>
                    </MetaItem>
                    {report.reasons.map((reason) => (
                        <ToolTip key={reason.reportID} label={reason.description}>
                            <MetaItem>
                                <Tag preset={TagPreset.STANDARD}>{reason.name}</Tag>
                            </MetaItem>
                        </ToolTip>
                    ))}
                </>
            }
            actions={
                <ToolTip label={t("View post as reported")}>
                    <Button buttonType={ButtonTypes.ICON} className={"snapshot"} onClick={() => onReportView(report)}>
                        <RevisionStatusPendingIcon />
                    </Button>
                </ToolTip>
            }
        />
    );
}
