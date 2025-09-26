/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css, cx } from "@emotion/css";

interface IProps {
    children?: React.ReactNode;
    iconSize?: number | string;
    className?: string;
}

export function IconHexGrid(props: IProps) {
    return (
        <div className={cx(classes.root, props.className)} style={{ height: props.iconSize, width: props.iconSize }}>
            {props.children}
        </div>
    );
}

const gridColorBase = "#d2d2d2";
const gridColor = `var(--grid-color, ${gridColorBase})`;
const lineSize = "0.5px";
const gridSize = "12px";

const classes = {
    root: css({
        "--grid-color": "#e0e0e0",
        backgroundSize: `${gridSize} ${gridSize}`,
        backgroundRepeat: "repeat",
        backgroundImage: `conic-gradient(${gridColor} 90deg, transparent 90deg 180deg, ${gridColor} 180deg 270deg, transparent 270deg)`,
        outline: `${lineSize} solid ${gridColor}`,
        borderBottom: `${lineSize} solid ${gridColor}`,
        borderRight: `${lineSize} solid ${gridColor}`,
    }),
};
