/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { themeBuilderClasses } from "@library/forms/themeEditor/ThemeBuilder.styles";

interface IProps {
    title: string;
}

export function ThemeBuilderTitle(props: IProps) {
    return <h2 className={themeBuilderClasses().title}>{props.title}</h2>;
}
