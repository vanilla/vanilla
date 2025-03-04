/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { coreIconsData, IconType } from "./IconType";
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
    const coreIcon = (window as any).__VANILLA_ICON_ATTRS__?.[icon] ?? coreIconsData[icon] ?? null;
    // Return null for FoundIcon when in test mode
    if (!FoundIcon) {
        if (coreIcon) {
            return (
                <svg
                    fill="none"
                    focusable="false"
                    {...(coreIcon as any)}
                    width={IconSize[size]}
                    height={IconSize[size]}
                    {...props}
                >
                    <use xlinkHref={`#${icon}`} />
                </svg>
            );
        } else {
            return <></>;
        }
    }
    return <FoundIcon focusable={false} {...props} width={IconSize[size]} height={IconSize[size]} />;
}
