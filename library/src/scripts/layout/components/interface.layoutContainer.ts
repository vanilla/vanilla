/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ILayoutProps } from "@library/layout/LayoutContext";
import React from "react";
import { IPanelLayoutClasses } from "@library/layout/panelLayoutStyles";
import { IPanelLayoutProps } from "@library/layout/PanelLayout";

export interface ILayoutContainer extends ILayoutProps {
    className?: string;
    children?: React.ReactNode;
    tag?: keyof JSX.IntrinsicElements;
    ariaHidden?: boolean;
    innerRef?: React.RefObject<HTMLDivElement>;
    panelClasses?: IPanelLayoutClasses;
}
