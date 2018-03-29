/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import { t } from "@core/utility";
import * as PropTypes from "prop-types";
import { withEditor, editorContextTypes } from "./ContextProvider";
import Popover from "./Generic/Popover";

export class EmbedPopover extends React.PureComponent {

    static propTypes = {
        ...editorContextTypes,
        isVisible: PropTypes.bool.isRequired,
        closeMenu: PropTypes.func.isRequired,
        blurHandler: PropTypes.func.isRequired,
    };

    render() {
        const title = t("Insert Media");
        const description = t("Insert an embedded web page, or video into your message.");

        const body = <React.Fragment>
            <p id="tempId-insertMediaMenu-p" className="insertMedia-description">
                {t('Paste the URL of the media you want.')}
            </p>
            <input className="InputBox" placeholder="http://" />
        </React.Fragment>;

        const footer = <React.Fragment>
            <a href="#" className="insertMedia-help" aria-label={t('Get Help on Inserting Media')}>
                {t('Help')}
            </a>

            <input
                type="submit"
                className="Button Primary insertMedia-insert"
                value={('Insert')}
                aria-label={('Insert Media')}
                onBlur={this.props.blurHandler}
            />
        </React.Fragment>;

        return <Popover
            id={this.props.id}
            title={title}
            accessibleDescription={description}
            body={body}
            footer={footer}
            additionalClassRoot="insertMedia"
            closeMenu={this.props.closeMenu}
            isVisible={this.props.isVisible}
            popoverTitleID={this.props.popoverTitleID}
            popoverDescriptionID={this.props.popoverDescriptionID}
            targetTitleOnOpen={this.props.targetTitleOnOpen}
        />;
    }
}

export default withEditor(EmbedPopover);
