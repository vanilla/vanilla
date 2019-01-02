/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/application";
import CloseButton from "@library/components/CloseButton";
import { withEditor, IWithEditorProps } from "@rich-editor/components/context";

interface IProps extends IWithEditorProps {
    inputRef: React.RefObject<HTMLInputElement>;
    inputValue: string;
    onInputKeyDown: React.KeyboardEventHandler<any>;
    onInputChange: React.ChangeEventHandler<any>;
    onCloseClick: React.MouseEventHandler<any>;
}

export class InlineToolbarLinkInput extends React.PureComponent<IProps, {}> {
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
            <div className="richEditor-menu insertLink likeDropDownContent" role="dialog" aria-label={t("Insert Url")}>
                <input
                    value={this.props.inputValue}
                    onChange={this.props.onInputChange}
                    ref={this.props.inputRef}
                    onKeyDown={this.props.onInputKeyDown}
                    className="InputBox insertLink-input"
                    placeholder={t("Paste or type a link…")}
                />
                <CloseButton
                    className="richEditor-close"
                    onClick={this.props.onCloseClick}
                    legacyMode={this.props.legacyMode}
                />
            </div>
        );
    }
}

export default withEditor<IProps>(InlineToolbarLinkInput);
