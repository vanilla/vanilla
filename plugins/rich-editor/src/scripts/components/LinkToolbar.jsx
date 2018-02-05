/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import * as PropTypes from "prop-types";
import { t } from "@core/utility";

export default class LinkToolbar extends React.Component {

    static propTypes = {
        closeButtonHandler: PropTypes.func,
        keyDownHandler: PropTypes.func,
    };

    /**
     * @type {Object}
     * @property {function} closeButtonHandler
     * @property {function} keyDownHandler
     */
    props;

    /**
     * @inheritDoc
     */
    render() {
        return <div className="richEditor-menu FlyoutMenu insertLink" role="dialog" aria-label={t("Insert Url")}>
            <input onKeyDown={this.props.keyDownHandler} className="InputBox insertLink-input" placeholder={t("Paste or type a link…")} />
            <a href="#" aria-label={t("Close")} className="Close richEditor-close" role="button" onClick={this.props.closeButtonHandler}>
                <span>×</span>
            </a>
        </div>;
    }
}
