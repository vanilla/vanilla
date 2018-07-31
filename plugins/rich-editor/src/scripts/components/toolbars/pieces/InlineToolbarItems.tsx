/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import Quill, { RangeStatic } from "quill/core";
import MenuItems from "./MenuItems";
import { withEditor, IEditorContextProps } from "@rich-editor/components/context";
import { IMenuItemData } from "./MenuItem";
import CodeBlot from "@rich-editor/quill/blots/inline/CodeBlot";
import { rangeContainsBlot } from "@rich-editor/quill/utility";

interface IProps extends IEditorContextProps {
    currentSelection: RangeStatic | null;
    linkFormatter?: (menuItemData: IMenuItemData) => void;
}

export class InlineToolbarItems extends React.Component<IProps, {}> {
    private linkInput: HTMLElement;
    private quill: Quill;

    private get menuItems() {
        return {
            bold: {
                active: false,
            },
            italic: {
                active: false,
            },
            strike: {
                active: false,
            },
            code: {
                formatName: "codeInline",
                active: false,
                formatter: this.codeFormatter,
            },
            link: {
                active: false,
                value: "",
                formatter: this.props.linkFormatter,
            },
        };
    }

    /**
     * @inheritDoc
     */
    constructor(props) {
        super(props);

        // Quill can directly on the class as it won't ever change in a single instance.
        this.quill = props.quill;
    }

    public render() {
        return <MenuItems restrictedFormats={this.restrictedFormats} menuItems={this.menuItems} />;
    }

    /**
     * Get the restricted formats for the format toolbar.
     *
     * Should exclude everything else if inline code is selected.
     */
    private get restrictedFormats(): string[] | null {
        if (rangeContainsBlot(this.quill, CodeBlot)) {
            return Object.keys(this.menuItems).filter(key => key !== "code");
        } else {
            return null;
        }
    }

    /**
     * Be sure to strip out all other formats before formatting as code.
     */
    private codeFormatter = (menuItemData: IMenuItemData) => {
        if (!this.props.currentSelection) {
            return;
        }
        this.quill.removeFormat(
            this.props.currentSelection.index,
            this.props.currentSelection.length,
            Quill.sources.API,
        );
        this.quill.formatText(
            this.props.currentSelection.index,
            this.props.currentSelection.length,
            "codeInline",
            !menuItemData.active,
            Quill.sources.USER,
        );
    };
}

export default withEditor<IProps>(InlineToolbarItems);
