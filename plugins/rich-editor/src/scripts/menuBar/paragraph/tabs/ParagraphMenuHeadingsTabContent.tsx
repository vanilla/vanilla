/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import ParagraphMenuBarRadioGroup, {
    IMenuBarRadioButton,
} from "@rich-editor/menuBar/paragraph/items/ParagraphMenuBarRadioGroup";
import { t } from "@library/utility/appUtils";

interface IProps {
    items: IMenuBarRadioButton[];
    handleClick: () => void;
}

/**
 * Implemented tab content for the headings section
 */
export default class ParagraphMenuHeadingsTabContent extends React.Component<IProps> {
    public render() {
        return t("Hello, i'm here");
        /*
            <ParagraphMenuBarRadioGroup
                handleClick={this.props.handleClick}
                label={t("Headings")}
                items={this.props.items}
            />
            */
    }
}
