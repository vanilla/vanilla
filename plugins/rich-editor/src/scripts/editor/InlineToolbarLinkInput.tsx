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
import { rangeContainsBlot, CLOSE_FLYOUT_EVENT, disableAllBlotsInRange } from "../quill/utility";
import CodeBlockBlot from "../quill/blots/blocks/CodeBlockBlot";

interface IProps {
    inputRef: React.RefObject<HTMLInputElement>;
    inputValue: string;
    onInputKeyDown: React.KeyboardEventHandler<any>;
    onInputChange: React.ChangeEventHandler<any>;
    onCloseClick: React.MouseEventHandler<any>;
}

export class InlineToolbarLinkInput extends React.Component<IProps, {}> {
    constructor(props) {
        super(props);

        this.state = {
            linkValue: "",
            cachedRange: {
                index: 0,
                length: 0,
            },
            showFormatMenu: false,
            showLinkMenu: false,
        };
    }

    public render() {
        return (
            <div className="richEditor-menu FlyoutMenu insertLink" role="dialog" aria-label={t("Insert Url")}>
                <input
                    value={this.props.inputValue}
                    onChange={this.props.onInputChange}
                    ref={this.props.inputRef}
                    onKeyDown={this.props.onInputKeyDown}
                    className="InputBox insertLink-input"
                    placeholder={t("Paste or type a link…")}
                />
                <button type="button" onClick={this.props.onCloseClick} className="Close richEditor-close">
                    <span className="Close-x" aria-hidden="true">
                        {t("×")}
                    </span>
                    <span className="sr-only">{t("Close")}</span>
                </button>
            </div>
        );
    }
}

export default InlineToolbarLinkInput;
