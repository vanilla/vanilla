/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardFormSubheading } from "@dashboard/forms/DashboardFormSubheading";
import { isPostDiscussion, type IPostRevisionOption } from "@dashboard/moderation/CommunityManagement.hooks";
import { communityManagementPageClasses } from "@dashboard/moderation/CommunityManagementPage.classes";
import BlurContainer from "@dashboard/moderation/components/BlurContainerUserContent";
import { detailPageClasses } from "@dashboard/moderation/DetailPage.classes";
import { CollapsableContent } from "@library/content/CollapsableContent";
import DateTime from "@library/content/DateTime";
import Translate from "@library/content/Translate";
import UserContent from "@library/content/UserContent";
import { ReadableIntegrationContextProvider } from "@library/features/discussions/integrations/Integrations.context";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { PageBoxContextProvider } from "@library/layout/PageBox.context";
import { ListItem } from "@library/lists/ListItem";
import { listItemClasses } from "@library/lists/ListItem.styles";
import { MetaIcon, MetaItem, Metas } from "@library/metas/Metas";
import { metasClasses } from "@library/metas/Metas.styles";
import { Tag } from "@library/metas/Tags";
import { TagPreset } from "@library/metas/Tags.variables";
import ProfileLink from "@library/navigation/ProfileLink";
import SmartLink from "@library/routing/links/SmartLink";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { ToolTip } from "@library/toolTip/ToolTip";
import { ContentItemAttachment } from "@vanilla/addon-vanilla/contentItem/ContentItemAttachment";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";

interface IProps {
    activeRevisionOption: IPostRevisionOption;
}

export function PostDetail(props: IProps) {
    const { activeRevisionOption } = props;
    const { livePost } = activeRevisionOption;
    const classes = detailPageClasses();
    const listClasses = listItemClasses();

    const filteredAttachments = (livePost?.attachments ?? []).filter(
        ({ attachmentType }) => attachmentType !== "vanilla-escalation",
    );

    return (
        <>
            <DashboardFormSubheading>
                {livePost ? t("Live Post") : t("Post Revision")}
                {!livePost && (
                    <Tag className={classes.tag} preset={TagPreset.GREYSCALE}>
                        <Icon icon="meta-time" />
                        <DateTime mode={"fixed"} timestamp={activeRevisionOption.recordRevisionDate} />
                    </Tag>
                )}
            </DashboardFormSubheading>

            <PageBoxContextProvider options={{ borderType: BorderType.SHADOW }}>
                <ListItem
                    as={"div"}
                    nameClassName={communityManagementPageClasses().listItemLink}
                    name={
                        <div className={classes.headerIconLayout}>
                            {activeRevisionOption.recordName}
                            {livePost && (
                                <span>
                                    <ToolTip label={t("View post in community")}>
                                        <span>
                                            <Icon icon="meta-external" size={"compact"} />
                                        </span>
                                    </ToolTip>
                                </span>
                            )}
                        </div>
                    }
                    url={livePost?.url}
                    description={
                        <BlurContainer>
                            <UserContent
                                className={listClasses.description}
                                content={activeRevisionOption.recordHtml}
                                moderateEmbeds
                            />
                        </BlurContainer>
                    }
                    truncateDescription={false}
                    icon={
                        <ProfileLink userFragment={activeRevisionOption.recordUser}>
                            <UserPhoto size={UserPhotoSize.MEDIUM} userInfo={activeRevisionOption.recordUser} />
                        </ProfileLink>
                    }
                    metas={
                        <>
                            <Metas>
                                {isPostDiscussion(livePost) && (
                                    <MetaIcon
                                        icon={livePost.resolved ? "resolved" : "unresolved"}
                                        aria-label={livePost.resolved ? t("Resolved") : t("Unresolved")}
                                    />
                                )}
                                <MetaItem>
                                    <Translate
                                        source="Posted by <0/> in <1/>"
                                        c0={
                                            <ProfileLink
                                                className={metasClasses().metaLink}
                                                userFragment={activeRevisionOption.recordUser}
                                            />
                                        }
                                        c1={
                                            <SmartLink
                                                to={activeRevisionOption.placeRecordUrl}
                                                className={metasClasses().metaLink}
                                            >
                                                {activeRevisionOption.placeRecordName}
                                            </SmartLink>
                                        }
                                    />
                                </MetaItem>
                                <MetaItem>
                                    <MetaIcon icon="meta-time" style={{ marginLeft: -4 }} />
                                    <DateTime timestamp={activeRevisionOption.recordRevisionDate}></DateTime>
                                </MetaItem>
                            </Metas>
                        </>
                    }
                />
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
                                        <ContentItemAttachment key={attachment.attachmentID} attachment={attachment} />
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
