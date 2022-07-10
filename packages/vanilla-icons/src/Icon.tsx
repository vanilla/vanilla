/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { IconType } from "./IconType";
import { iconRegistry } from "./IconRegistry";

export const IconSize = {
    default: 24,
    compact: 16,
};

interface IProps extends React.SVGAttributes<HTMLOrSVGElement> {
    icon: IconType;
    size?: keyof typeof IconSize;
}

export function Icon(_props: IProps) {
    const { icon, size = "default", ...props } = _props;
    const FoundIcon = iconRegistry.getIcon(icon);
    if (!FoundIcon) {
        return null;
    }
    return <FoundIcon focusable={false} aria-hidden="true" {...props} width={IconSize[size]} height={IconSize[size]} />;
}
