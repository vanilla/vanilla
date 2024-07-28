import { DashboardFormSubheading } from "@dashboard/forms/DashboardFormSubheading";

import { CollapsableContent } from "@library/content/CollapsableContent";
import UserContent from "@library/content/UserContent";
import { deletedUserFragment } from "@library/features/__fixtures__/User.Deleted";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { PageBoxContextProvider } from "@library/layout/PageBox.context";
import { ListItem } from "@library/lists/ListItem";
import ProfileLink from "@library/navigation/ProfileLink";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { t } from "@vanilla/i18n";
import { ReadableIntegrationContextProvider } from "@library/features/discussions/integrations/Integrations.context";
import { DiscussionAttachment } from "@vanilla/addon-vanilla/thread/DiscussionAttachmentsAsset";

import DateTime from "@library/content/DateTime";
import { Tag } from "@library/metas/Tags";
import { TagPreset } from "@library/metas/Tags.variables";
import { Icon } from "@vanilla/icons";
import { detailPageClasses } from "@dashboard/moderation/DetailPage.classes";
import Translate from "@library/content/Translate";
import { Metas, MetaIcon, MetaItem } from "@library/metas/Metas";
import { metasClasses } from "@library/metas/Metas.styles";
import SmartLink from "@library/routing/links/SmartLink";
import { listItemClasses } from "@library/lists/ListItem.styles";
import { ToolTip } from "@library/toolTip/ToolTip";
import { ButtonTypes } from "@library/forms/buttonTypes";
import LinkAsButton from "@library/routing/LinkAsButton";
import { usePostRevision } from "@dashboard/moderation/PostRevisionContext";
import { communityManagementPageClasses } from "@dashboard/moderation/CommunityManagementPage.classes";

interface IProps {
    truncatePost?: boolean;
}

export function PostDetail(props: IProps) {
    const { activeRevision, activeReport } = usePostRevision();
    const classes = detailPageClasses();
    const listClasses = listItemClasses();

    const filteredAttachments = (activeRevision?.attachments ?? []).filter(
        ({ attachmentType }) => attachmentType !== "vanilla-escalation",
    );

    return (
        <>
            <DashboardFormSubheading>
                {!activeReport ? t("Latest Post Revision") : t("Post Revision")}
                {activeReport && (
                    <Tag className={classes.tag} preset={TagPreset.GREYSCALE}>
                        <Icon icon="meta-time" />
                        <DateTime mode={"fixed"} timestamp={activeReport.dateInserted} />
                    </Tag>
                )}
            </DashboardFormSubheading>

            <PageBoxContextProvider options={{ borderType: BorderType.SHADOW }}>
                {activeRevision && (
                    <ListItem
                        as={"div"}
                        nameClassName={communityManagementPageClasses().listItemLink}
                        name={
                            <div className={classes.headerIconLayout}>
                                {activeRevision.name}
                                <span>
                                    <ToolTip label={t("View post in community")}>
                                        <span>
                                            <Icon icon="meta-external" size={"compact"} />
                                        </span>
                                    </ToolTip>
                                </span>
                            </div>
                        }
                        url={activeRevision.url}
                        description={
                            <>
                                <ConditionalWrap
                                    condition={!!props.truncatePost}
                                    component={CollapsableContent}
                                    componentProps={{ maxHeight: 80 }}
                                >
                                    <UserContent
                                        className={listClasses.description}
                                        content={activeRevision.body ?? ""}
                                    />
                                </ConditionalWrap>
                            </>
                        }
                        truncateDescription={false}
                        icon={
                            <ProfileLink userFragment={activeRevision.insertUser ?? deletedUserFragment()}>
                                <UserPhoto size={UserPhotoSize.MEDIUM} userInfo={activeRevision.insertUser} />
                            </ProfileLink>
                        }
                        metas={
                            <>
                                <Metas>
                                    <MetaIcon
                                        icon={activeRevision.resolved ? "cmd-approve" : "cmd-alert"}
                                        aria-label={activeRevision.resolved ? t("Resolved") : t("Unresolved")}
                                    />
                                    <MetaItem>
                                        <Translate
                                            source="Posted by <0/> in <1/>"
                                            c0={
                                                <SmartLink
                                                    to={activeRevision.insertUser?.url ?? ""}
                                                    className={metasClasses().metaLink}
                                                >
                                                    {activeRevision.insertUser?.name}
                                                </SmartLink>
                                            }
                                            c1={
                                                <SmartLink
                                                    to={activeRevision.category?.url ?? ""}
                                                    className={metasClasses().metaLink}
                                                >
                                                    {activeRevision.category?.name}
                                                </SmartLink>
                                            }
                                        />
                                    </MetaItem>
                                    <MetaItem>
                                        <MetaIcon icon="meta-time" style={{ marginLeft: -4 }} />
                                        <DateTime timestamp={activeRevision.dateInserted}></DateTime>
                                    </MetaItem>
                                </Metas>
                            </>
                        }
                    />
                )}
                {filteredAttachments.length > 0 && (
                    <div className={classes.postAttachment}>
                        <CollapsableContent>
                            <>
                                <DashboardFormSubheading>{t("Post Attachments")}</DashboardFormSubheading>
                                {filteredAttachments.map((attachment) => (
                                    <ReadableIntegrationContextProvider
                                        key={attachment.attachmentID}
                                        attachmentType={attachment.attachmentType}
                                    >
                                        <DiscussionAttachment key={attachment.attachmentID} attachment={attachment} />
                                    </ReadableIntegrationContextProvider>
                                ))}
                            </>
                        </CollapsableContent>
                    </div>
                )}
            </PageBoxContextProvider>
        </>
    );
}
