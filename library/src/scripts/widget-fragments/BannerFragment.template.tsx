import Components from "@vanilla/injectables/Components";
import Banner from "@vanilla/injectables/BannerFragment";
import Utils from "@vanilla/injectables/Utils";
import React, { useRef } from "react";

export default function BannerFragment(props: Banner.Props) {
    const { title, titleType, description, descriptionType, background, textColor, alignment, showSearch } = props;

    const showTitle = titleType !== "none";
    const showDescription = descriptionType !== "none";
    const { color, imageUrlSrcSet, imageSource, useOverlay } = background || {};

    const hasBackgroundImage = imageUrlSrcSet || imageSource;

    const rootRef = useRef<HTMLElement>(null);
    const measure = Utils.useMeasure(rootRef);
    const isDesktop = measure.clientWidth > 806;

    return (
        <Components.LayoutWidget
            as="section"
            // Banner's typically have their own padding so we can use `interWidgetSpacing` to prevent extra padding between the banner and other widgets with no interWidgetSpacing.
            interWidgetSpacing={"none"}
            ref={rootRef}
            className={`bannerFragment__root ${useOverlay ? "hasOverlay" : ""}`}
            style={
                {
                    ...(color && { "--background-color": color }),
                    ...(textColor && { "--text-color": textColor }),
                    ...(alignment && { "--alignment": alignment }),
                } as React.CSSProperties
            }
        >
            <div className={"bannerFragment__image_container"}>
                {useOverlay && <span className={"bannerFragment__overlay_container"} />}
                {hasBackgroundImage && (
                    <picture>
                        {imageSource !== "styleGuide" ? (
                            <img
                                role="presentation"
                                src={imageSource}
                                {...(imageUrlSrcSet && { srcSet: Utils.createSourceSetValue(imageUrlSrcSet) })}
                            />
                        ) : (
                            <Banner.DefaultBannerImage backgroundColor={color} />
                        )}
                    </picture>
                )}
            </div>
            <Components.Gutters className={"bannerFragment__gutters"}>
                <div className={"bannerFragment_container"}>
                    {(showTitle || showDescription) && (
                        <div className={"bannerFragment__copy_lockup"}>
                            {showTitle && <h1 className={"bannerFragment__title"}>{title}</h1>}{" "}
                            {showDescription && <div className={"bannerFragment__description"}>{description}</div>}
                        </div>
                    )}
                    {showSearch && (
                        <div className={"bannerFragment__search_container"}>
                            <Banner.SearchInput
                                buttonClass={"bannerFragment__search_button"}
                                buttonType={"primary"}
                                hideSearchButton={!isDesktop}
                            />
                        </div>
                    )}
                </div>
            </Components.Gutters>
        </Components.LayoutWidget>
    );
}
