/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/application";
import { ILegacyMode } from "@rich-editor/components/editor/editor";
import CloseButton from "@library/components/CloseButton";

interface IProps extends ILegacyMode {
    inputRef: React.RefObject<HTMLInputElement>;
    inputValue: string;
    onInputKeyDown: React.KeyboardEventHandler<any>;
    onInputChange: React.ChangeEventHandler<any>;
    onCloseClick: React.MouseEventHandler<any>;
}

export class InlineToolbarLinkInput extends React.Component<IProps, {}> {
    public static defaultProps = {
        legacyMode: false,
    };

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
        const close = `x`;
        return (
            <div className="richEditor-menu insertLink" role="dialog" aria-label={t("Insert Url")}>
                <input
                    value={this.props.inputValue}
                    onChange={this.props.onInputChange}
                    ref={this.props.inputRef}
                    onKeyDown={this.props.onInputKeyDown}
                    className="InputBox insertLink-input"
                    placeholder={t("Paste or type a link…")}
                />
                <CloseButton
                    legacyMode={this.props.legacyMode}
                    className="richEditor-close"
                    onClick={this.props.onCloseClick}
                />
            </div>
        );
    }
}

export default InlineToolbarLinkInput;
