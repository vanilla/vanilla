/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import CoreButton from "@library/forms/Button";
import Container from "@library/layout/components/Container";
import LinkAsButton from "@library/routing/LinkAsButton";
import SmartLink from "@library/routing/links/SmartLink";
import { ComponentProps, forwardRef, useState, type ElementType } from "react";
import { Icon } from "@vanilla/icons";
import Translate from "@library/content/Translate";
import DateTime from "@library/content/DateTime";
import UserContent from "@library/content/UserContent";
import { UserTitle } from "@library/content/UserTitle";
import { UserPhoto } from "@library/headers/mebox/pieces/UserPhoto";
import ProfileLink from "@library/navigation/ProfileLink";
import { Tag as VanillaTag } from "@library/metas/Tags";
import { TagPreset } from "@library/metas/Tags.variables";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import { LayoutWidget } from "@library/layout/LayoutWidget";
import { Gutters } from "@library/layout/components/Gutters";
import { WidgetDefaultImage } from "@library/homeWidget/WidgetDefaultImage";
import { WidgetImageType } from "@library/homeWidget/WidgetItemOptions";
import { createSourceSetValue, type ImageSourceSet } from "@library/utility/appUtils";
import { listItemMediaClasses } from "@library/lists/ListItemMedia.styles";
import { cx } from "@emotion/css";
import { MetaItem, MetaProfile } from "@library/metas/Metas";

export type VanillaButtonProps = {
    buttonType: "standard" | "primary" | "text" | "textPrimary" | "icon" | "iconCompact";
} & React.ButtonHTMLAttributes<HTMLButtonElement>;

/**
 * A standard HTML button with Vanilla styling presets available.
 */
const Button = forwardRef(function VanillaButton(props: VanillaButtonProps, ref: React.RefObject<HTMLButtonElement>) {
    return <CoreButton ref={ref} {...props} />;
});

const LinkButton = (props: ComponentProps<typeof LinkAsButton>) => {
    return <LinkAsButton {...props} />;
};

const Link = (props: ComponentProps<typeof SmartLink>) => {
    return <SmartLink {...props} />;
};

export interface TagProps extends Omit<ComponentProps<typeof VanillaTag>, "preset"> {
    preset?: "standard" | "primary" | "greyscale" | "colored" | "success" | "error";
}

const Tag = (props: TagProps) => {
    const preset = (props?.preset ?? "standard") as TagPreset;
    return <VanillaTag {...props} preset={preset} />;
};

function ResponsiveImage(props: {
    src: string | null;
    srcSet?: ImageSourceSet | null;
    alt: string;
    className?: string;
    imageClassName?: string;
    height?: number | string;
    width?: number | string;
    aspectRatio:
        | {
              height: number;
              width: number;
          }
        | "parent"
        | "none";
    loading?: "eager" | "lazy";
}) {
    const { src, srcSet, alt, className, aspectRatio } = props;
    const [showFallback, setShowFallback] = useState(!props.src);
    const mediaClasses = listItemMediaClasses();

    const ratioClass =
        aspectRatio === "parent"
            ? mediaClasses.fullParent
            : aspectRatio === "none"
            ? undefined
            : mediaClasses.naturalRatioContainer({
                  vertical: aspectRatio?.height ?? 9,
                  horizontal: aspectRatio?.width ?? 16,
              });

    return (
        <div className={cx(mediaClasses.mediaItem, ratioClass, className)}>
            {src && (
                <img
                    loading={props.loading ?? "lazy"}
                    src={src}
                    srcSet={srcSet ? createSourceSetValue(srcSet) : undefined}
                    alt={alt}
                    height={props.height}
                    width={props.width}
                    className={cx(aspectRatio === "none" ? undefined : mediaClasses.coverImage, props.imageClassName)}
                    onError={() => setShowFallback(true)}
                />
            )}
            {showFallback && <WidgetDefaultImage className={props.imageClassName} />}
        </div>
    );
}

const Components = {
    Gutters,
    Button,
    LinkButton,
    Link,
    Icon,
    Translate,
    DateTime,
    UserContent,
    UserPhoto,
    UserTitle,
    ProfileLink,
    Tag,
    ToolTip,
    ToolTipIcon,
    LayoutWidget,
    WidgetDefaultImage,
    WidgetImageType,
    ResponsiveImage,
    MetaItem,
    MetaProfile,
};

export default Components;
