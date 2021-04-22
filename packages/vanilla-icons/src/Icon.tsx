/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { css, cx } from "@emotion/css";
import { IconType } from "./IconType";
import { iconRegistry } from "./IconRegistry";

interface IProps extends React.SVGAttributes<HTMLOrSVGElement> {
    icon: IconType;
    size?: "normal" | "compact";
}

export const IconSize = {
    default: 24,
    compact: 16,
};

const iconCompactClass = css({
    height: IconSize.compact,
    width: IconSize.compact,
});

const iconClass = css({
    height: IconSize.default,
    width: IconSize.default,
});

export function Icon(_props: IProps) {
    const { icon, size = "normal", ...props } = _props;
    const FoundIcon = iconRegistry.getIcon(icon);

    if (FoundIcon) {
        return (
            <FoundIcon
                focusable={false}
                aria-hidden="true"
                {...props}
                className={cx(size === "compact" ? iconCompactClass : iconClass)}
            />
        );
    } else {
        return <></>;
    }
}
