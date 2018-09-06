/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import { t } from "@dashboard/application";

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
            <div className="richEditor-menu insertLink" role="dialog" aria-label={t("Insert Url")}>
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
                        {t("Close")}
                    </span>
                    <span className="sr-only">{t("Close")}</span>
                </button>
            </div>
        );
    }
}

export default InlineToolbarLinkInput;
