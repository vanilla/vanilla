/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import CoreButton from "@library/forms/Button";
import Container from "@library/layout/components/Container";
import LinkAsButton from "@library/routing/LinkAsButton";
import SmartLink from "@library/routing/links/SmartLink";
import { ComponentProps, forwardRef, type ElementType } from "react";
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
};

export default Components;
