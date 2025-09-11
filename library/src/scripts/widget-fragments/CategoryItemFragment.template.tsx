import CategoryItem from "@vanilla/injectables/CategoryItemFragment";
import Components from "@vanilla/injectables/Components";
import Utils from "@vanilla/injectables/Utils";
import React from "react";

export default function CategoryItemFragment(props: CategoryItem.Props) {
    const { categoryItem, options, imageType } = props;

    const isCurrentUserSignedIn = Utils.useCurrentUserSignedIn();
    const showFollowMenu = isCurrentUserSignedIn && (options.followButton?.display ?? true);
    const showDescription = options.description?.display ?? true;

    const title = (
        <h3 className={"categoryItem__title"}>
            <Components.Link to={categoryItem.url}>{categoryItem.name}</Components.Link>
        </h3>
    );

    const description = showDescription && (
        <div className={"categoryItem__description"}>{categoryItem.description}</div>
    );

    /**
     * The meta component is configurable in the widget settings. Keep in mind that if you replace it altogether with your own meta display, that the widget settings won't "automatically" work with your changes.
     */
    const meta = <CategoryItem.Meta className={"categoryItem__metas"} />;

    let content: React.ReactNode;
    if (imageType === Components.WidgetImageType.Background) {
        return (
            <div className={"categoryItem__backgroundImageRoot"}>
                <div className={"categoryItem__backgroundImageContainer"}>
                    <Components.ResponsiveImage
                        className={"categoryItem__backgroundImage"}
                        aspectRatio={"parent"}
                        src={categoryItem.bannerImageUrl ?? null}
                        srcSet={categoryItem.bannerImageUrlSrcSet}
                        alt={categoryItem.name}
                    />
                    <div className={"categoryItem__textContent categoryItem__absoluteTextContent"}>
                        {title}
                        {description}
                    </div>
                    {showFollowMenu && (
                        <CategoryItem.FollowMenu displayMode={"icon"} className={"categoryItem__absoluteFollowMenu"} />
                    )}
                </div>
                {meta}
            </div>
        );
    } else {
        return (
            <div className={"categoryItem__root"}>
                {imageType === Components.WidgetImageType.Icon ? (
                    <Components.ResponsiveImage
                        src={categoryItem.iconUrl ?? null}
                        srcSet={categoryItem.iconUrlSrcSet}
                        alt={categoryItem.name}
                        className={"categoryItem__icon"}
                        aspectRatio={{ height: 1, width: 1 }}
                    />
                ) : imageType === Components.WidgetImageType.Image ? (
                    <Components.ResponsiveImage
                        src={categoryItem.bannerImageUrl ?? null}
                        srcSet={categoryItem.bannerImageUrlSrcSet}
                        alt={categoryItem.name}
                        className={"categoryItem__image"}
                        aspectRatio={{ height: 16, width: 9 }}
                    />
                ) : null}
                <div className={"categoryItem__textContent"}>
                    {title}
                    {description}
                    {meta}
                </div>
                {showFollowMenu && (
                    <CategoryItem.FollowMenu displayMode={"icon-and-label"} className={"categoryItem__followMenu"} />
                )}
            </div>
        );
    }
}
