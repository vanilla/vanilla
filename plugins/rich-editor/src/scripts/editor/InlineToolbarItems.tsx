/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import Quill, { RangeStatic, Sources } from "quill/core";
import Emitter from "quill/core/emitter";
import Keyboard from "quill/modules/keyboard";
import LinkBlot from "quill/formats/link";
import { t, isAllowedUrl } from "@dashboard/application";
import SelectionPositionToolbar from "./SelectionPositionToolbarContainer";
import Toolbar from "./generic/Toolbar";
import { withEditor, IEditorContextProps } from "./ContextProvider";
import { IMenuItemData } from "./generic/MenuItem";
import CodeBlot from "../quill/blots/inline/CodeBlot";
import { rangeContainsBlot, disableAllBlotsInRange } from "../quill/utility";
import CodeBlockBlot from "../quill/blots/blocks/CodeBlockBlot";

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
                formatName: "code-inline",
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
        return <Toolbar restrictedFormats={this.restrictedFormats} menuItems={this.menuItems} />;
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
            "code-inline",
            !menuItemData.active,
            Quill.sources.USER,
        );
    };
}

export default withEditor<IProps>(InlineToolbarItems);
