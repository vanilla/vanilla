/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IComment } from "@dashboard/@types/api/comment";
import { DashboardFormSubheading } from "@dashboard/forms/DashboardFormSubheading";
import { IEscalation, IReport } from "@dashboard/moderation/CommunityManagementTypes";
import { CompactReportList } from "@dashboard/moderation/components/CompactReportList";
import { EscalationMetas } from "@dashboard/moderation/components/EscalationMetas";
import { ReportRecordMeta } from "@dashboard/moderation/components/ReportRecordMeta";
import { css } from "@emotion/css";
import { CollapsableContent } from "@library/content/CollapsableContent";
import UserContent from "@library/content/UserContent";
import { ReadableIntegrationContextProvider } from "@library/features/discussions/integrations/Integrations.context";
import { PageBoxContextProvider } from "@library/layout/PageBox.context";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import { List } from "@library/lists/List";
import { ListItem, ListItemContext } from "@library/lists/ListItem";
import { ListItemLayout } from "@library/lists/ListItem.variables";
import { MetaItem, Metas } from "@library/metas/Metas";
import Notice from "@library/metas/Notice";
import BackLink from "@library/routing/links/BackLink";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { CommentThreadItem } from "@vanilla/addon-vanilla/thread/CommentThreadItem";
import { DiscussionAttachment } from "@vanilla/addon-vanilla/thread/DiscussionAttachmentsAsset";
import { DiscussionFixture } from "@vanilla/addon-vanilla/thread/__fixtures__/Discussion.Fixture";
import { t } from "@vanilla/i18n";

interface IProps {
    reports: IReport[];
    escalation: IEscalation;
    comments: IComment[];
}

const classes = {
    section: css({
        marginBottom: 24,
    }),
    title: css({
        gap: 6,
        display: "flex",
        alignItems: "center",
    }),
};

export function EscalationDetails(props: IProps) {
    const { escalation, comments, reports } = props;
    return (
        <div>
            <PageHeadingBox
                title={<span className={classes.title}>{escalation.name}</span>}
                description={
                    <Metas>
                        <MetaItem>
                            <Notice>{escalation.status}</Notice>
                        </MetaItem>
                        <EscalationMetas escalation={escalation} />
                    </Metas>
                }
            />
            <section className={classes.section}>
                <DashboardFormSubheading>{t("Post")}</DashboardFormSubheading>
                <ListItemContext.Provider value={{ layout: ListItemLayout.TITLE_METAS_DESCRIPTION }}>
                    <PageBoxContextProvider options={{ borderType: BorderType.SHADOW }}>
                        <ListItem
                            name={escalation.recordName}
                            url={escalation.recordUrl}
                            truncateDescription={false}
                            description={
                                <CollapsableContent maxHeight={80}>
                                    <UserContent content={escalation.recordHtml} />
                                </CollapsableContent>
                            }
                            metas={<ReportRecordMeta record={escalation} />}
                        />
                    </PageBoxContextProvider>
                </ListItemContext.Provider>
            </section>
            {(escalation.attachments ?? []).length > 0 && (
                <section className={classes.section}>
                    <DashboardFormSubheading>{t("Attachments")}</DashboardFormSubheading>

                    {escalation.attachments?.map((attachment) => (
                        <ReadableIntegrationContextProvider
                            key={attachment.attachmentID}
                            attachmentType={attachment.attachmentType}
                        >
                            <DiscussionAttachment key={attachment.attachmentID} attachment={attachment} />
                        </ReadableIntegrationContextProvider>
                    ))}
                </section>
            )}
            <section className={classes.section}>
                <DashboardFormSubheading>{t("Reports")}</DashboardFormSubheading>
                <CompactReportList reports={reports} />
            </section>
            <section className={classes.section}>
                <DashboardFormSubheading>{t("Internal Comments")}</DashboardFormSubheading>
                <List
                    options={{
                        box: {
                            borderType: BorderType.SHADOW,
                        },
                        itemBox: { borderType: BorderType.SEPARATOR_BETWEEN },
                    }}
                >
                    {comments.map((comment, i) => {
                        return (
                            <CommentThreadItem
                                key={i}
                                comment={comment}
                                discussion={DiscussionFixture.mockDiscussion}
                            />
                        );
                    })}
                </List>
            </section>
        </div>
    );
}
