/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { paragraphMenuTabsClasses } from "@rich-editor/menuBar/paragraph/paragraphMenuBarStyles";
import { IMenuBarItemTypes } from "@rich-editor/menuBar/paragraph/ParagraphMenusBarToggle";
import { t } from "@library/utility/appUtils";

interface IProps {
    closeMenuAndSetCursor: () => void;
}

/**
 * Implemented tab content for block styles
 */
export default class ParagraphMenuBlockTabContent extends React.Component<IProps> {
    public render() {
        const classes = paragraphMenuTabsClasses();
        return <div className={classes.panel}>{t("Hola 3")}</div>;
    }
}
