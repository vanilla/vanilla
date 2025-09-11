import PostItem from "@vanilla/injectables/PostItemFragment";
import Components from "@vanilla/injectables/Components";
import Utils from "@vanilla/injectables/Utils";
import React from "react";

export default function PostItemFragment(props: PostItem.Props) {
    const { discussion, options, isChecked } = props;

    const currentUserSignedIn = Utils.useCurrentUserSignedIn();
    const hasUnread = discussion.unread || (discussion.countUnread !== undefined && discussion.countUnread > 0);

    /**
     * This is actually rendered into 3 different places, depending on the configuration of the widget and the available space to render the widget.
     *
     * 1. When there is no feature image, the photo is rendered into the desktop image container on desktop and the mobile profile photo container on mobile.
     * 2. When there is a featured image the photo is rendered into the featured image container (overlayed on top of the featured image) on desktop and the mobile profile photo container on mobile.
     *
     * Essentially desktop shares the image container between the featured image and the profile photo.
     */
    const profilePhotoLink = (
        <Components.ProfileLink userFragment={discussion.insertUser!} className={"postItem__profilePhotoLink"}>
            <Components.UserPhoto userInfo={discussion.insertUser} size={"medium"} />
        </Components.ProfileLink>
    );

    return (
        <div data-checked={isChecked} data-read={!hasUnread && currentUserSignedIn} className={"postItem__root"}>
            <div className="postItem__layout">
                <PostItem.BulkActionCheckbox className={"postItem__bulkActionCheckbox"} />
                <div className={"postItem__imageArea"}>
                    {options.featuredImage?.display ? (
                        <div className={"postItem__featuredImageContainer"}>
                            <Components.ResponsiveImage
                                className={"postItem__featuredImage"}
                                src={discussion.image?.url ?? options.featuredImage?.fallbackImage ?? null}
                                srcSet={discussion.image?.urlSrcSet}
                                alt={discussion.image?.alt ?? `Image from post "${discussion.name}"`}
                                aspectRatio={{ height: 9, width: 16 }}
                            />
                            {profilePhotoLink}
                        </div>
                    ) : (
                        profilePhotoLink
                    )}
                </div>
                <Components.ProfileLink
                    userFragment={discussion.insertUser!}
                    className={"postItem__mobileProfilePhotoLink"}
                >
                    <Components.UserPhoto userInfo={discussion.insertUser} size={"medium"} />
                </Components.ProfileLink>
                <div className={"postItem__content"}>
                    <Components.Link className={"postItem__title"} to={discussion.url}>
                        {discussion.name}
                    </Components.Link>
                    {options.excerpt?.display && <div className={"postItem__excerpt"}>{discussion.excerpt}</div>}
                    <PostItem.Meta
                        className={"postItem__meta"}
                        extraBefore={
                            discussion.type === "idea" && (
                                <Components.MetaItem>
                                    <PostItem.VoteCounter direction="horizontal" className={"postItem__voteCounter"} />
                                </Components.MetaItem>
                            )
                        }
                    />
                </div>
                {currentUserSignedIn && (
                    <div className={"postItem__actions"}>
                        <div className={"postItem__actionContainer"}>
                            <PostItem.BookmarkToggle className={"postItem__bookmarkToggle"} />
                            <PostItem.OptionsMenu className={"postItem__optionsMenu"} />
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
