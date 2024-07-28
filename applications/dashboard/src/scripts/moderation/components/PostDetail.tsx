/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IComment } from "@dashboard/@types/api/comment";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { DashboardFormSubheading } from "@dashboard/forms/DashboardFormSubheading";
import { CollapsableContent } from "@library/content/CollapsableContent";
import UserContent from "@library/content/UserContent";
import { deletedUserFragment } from "@library/features/__fixtures__/User.Deleted";
import { DiscussionListItemMeta } from "@library/features/discussions/DiscussionListItemMeta";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { PageBoxContextProvider } from "@library/layout/PageBox.context";
import { ListItemContext, ListItem } from "@library/lists/ListItem";
import { ListItemLayout } from "@library/lists/ListItem.variables";
import ProfileLink from "@library/navigation/ProfileLink";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { t } from "@vanilla/i18n";
import { ReadableIntegrationContextProvider } from "@library/features/discussions/integrations/Integrations.context";
import { DiscussionAttachment } from "@vanilla/addon-vanilla/thread/DiscussionAttachmentsAsset";

interface IProps {
    discussion?: IDiscussion;
    comment?: IComment;
    truncatePost?: boolean;
}

export function PostDetail(props: IProps) {
    const { discussion } = props;
    return (
        <>
            <DashboardFormSubheading>{t("Post")}</DashboardFormSubheading>
            <ListItemContext.Provider value={{ layout: ListItemLayout.TITLE_METAS_DESCRIPTION }}>
                <PageBoxContextProvider options={{ borderType: BorderType.SHADOW }}>
                    {discussion && (
                        <ListItem
                            name={discussion.name}
                            url={discussion.url}
                            truncateDescription={false}
                            description={
                                <>
                                    <ConditionalWrap
                                        condition={!!props.truncatePost}
                                        component={CollapsableContent}
                                        componentProps={{ maxHeight: 80 }}
                                    >
                                        <UserContent content={discussion.body ?? ""} />
                                    </ConditionalWrap>
                                    {(discussion.attachments ?? []).length > 0 &&
                                        discussion.attachments?.map((attachment) => (
                                            <ReadableIntegrationContextProvider
                                                key={attachment.attachmentID}
                                                attachmentType={attachment.attachmentType}
                                            >
                                                <DiscussionAttachment
                                                    key={attachment.attachmentID}
                                                    attachment={attachment}
                                                />
                                            </ReadableIntegrationContextProvider>
                                        ))}
                                </>
                            }
                            metas={
                                <DiscussionListItemMeta
                                    {...discussion}
                                    inTile={false}
                                    discussionOptions={{
                                        featuredImage: {
                                            display: false,
                                        },
                                        metas: {
                                            asIcons: false,
                                            display: {
                                                category: true,
                                                startedByUser: false,
                                                lastUser: false,
                                                viewCount: true,
                                                lastCommentDate: false,
                                                commentCount: false,
                                                score: false,
                                                userTags: true,
                                                unreadCount: false,
                                            },
                                        },
                                    }}
                                />
                            }
                            icon={
                                <ProfileLink userFragment={discussion.insertUser ?? deletedUserFragment()} isUserCard>
                                    <UserPhoto
                                        size={UserPhotoSize.MEDIUM}
                                        userInfo={discussion.insertUser ?? deletedUserFragment()}
                                    />
                                </ProfileLink>
                            }
                        />
                    )}
                </PageBoxContextProvider>
            </ListItemContext.Provider>
        </>
    );
}
