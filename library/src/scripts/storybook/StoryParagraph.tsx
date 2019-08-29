/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { globalVariables } from "@library/styles/globalStyleVars";
import { margins, singleBorder, unit } from "@library/styles/styleHelpers";
import Paragraph, { IParagraphProps } from "@library/layout/Paragraph";
import classNames from "classnames";
import { storyBookClasses } from "@library/storybook/StoryBookStyles";

interface IProps extends IParagraphProps {}

/**
 * Separator, for react storybook.
 */
export function StoryParagraph(props: IProps) {
    const classes = storyBookClasses();
    return <Paragraph {...props} className={classNames(props.className, classes.paragraph)} />;
}
