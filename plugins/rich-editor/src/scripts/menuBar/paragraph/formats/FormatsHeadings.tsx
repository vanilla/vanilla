/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { heading2, heading3, heading4, heading5 } from "@library/icons/editorIcons";
import { t } from "@library/utility/appUtils";
import Formatter from "@rich-editor/quill/Formatter";
import { RangeStatic } from "quill/core";
import ParagraphMenuBarRadioGroup, {
    IMenuBarRadioButton,
} from "@rich-editor/menuBar/paragraph/items/ParagraphMenuBarRadioGroup";
import { paragraphFormats } from "@rich-editor/menuBar/paragraph/formats/formatting";

interface IProps {
    formatter: Formatter;
    lastGoodSelection: RangeStatic;
    afterClickHandler?: () => void;
}

interface IState {
    activeIndex: number | null;
}

/**
 * Implemented ParagraphMenuDropDown component, this is for mobile
 */
export default class FormatsHeadings extends React.Component<IProps, IState> {
    public render() {
        const formats = paragraphFormats(
            this.props.formatter,
            this.props.lastGoodSelection,
            this.props.afterClickHandler,
        );
        return (
            <ParagraphMenuBarRadioGroup
                label={t("Headings")}
                activeIndex={this.state.activeIndex}
                handleClick={this.handleClick}
                items={[
                    {
                        checked: false,
                        icon: heading2(),
                        text: t("Headings 2"),
                        formatFunction: formats.formatH2,
                    },
                    {
                        checked: false,
                        icon: heading3(),
                        text: t("Headings 3"),
                        formatFunction: formats.formatH3,
                    },
                    {
                        checked: false,
                        icon: heading4(),
                        text: t("Headings 4"),
                        formatFunction: formats.formatH4,
                    },
                    {
                        checked: false,
                        icon: heading5(),
                        text: t("Headings 5"),
                        formatFunction: formats.formatH5,
                    },
                ]}
            />
        );
    }
    private handleClick = (data: IMenuBarRadioButton, index: number) => {
        data.formatFunction();
        this.setState({
            activeIndex: index,
        });
    };

    /**
     * Implement keyboard shortcuts in accordance with the WAI-ARIA best practices for Submenu.
     *
     * @see https://www.w3.org/TR/wai-aria-practices-1.1/examples/menubar/menubar-2/menubar-2.html
     */
    private handleKeyDown = (event: React.KeyboardEvent<any>) => {
        switch (event.key) {
            // Activates menu item, causing action to be executed, e.g., bold text, change font.
            case "Space":
            case "Enter":
                break;
        }
    };
}
