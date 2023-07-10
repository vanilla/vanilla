/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ISectionProps } from "@library/layout/LayoutContext";
import React, { ElementType } from "react";
import { ISectionClasses } from "@library/layout/Section.styles";

export interface ILayoutContainer extends ISectionProps {
    className?: string;
    children?: React.ReactNode;
    tag?: ElementType;
    ariaHidden?: boolean;
    innerRef?: React.RefObject<HTMLDivElement>;
    panelClasses?: ISectionClasses;
}
