/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import { t } from "@core/utility";
import * as PropTypes from "prop-types";
import classNames from 'classnames';
import { withEditor, editorContextTypes } from "./EditorProvider";
import InsertPopover from "./InsertPopover";

export class EmbedPopover extends React.PureComponent {

    static propTypes = {
        ...editorContextTypes,
    };

    render() {
        const title = t("Insert Media");
        const description = t("Insert an embedded web page, or video into your message.");

        const body = <div>
            <p id="tempId-insertMediaMenu-p" className="insertMedia-description">
                {t('Paste the URL of the media you want.')}
            </p>
            <input className="InputBox" placeholder="http://" />
        </div>;

        const footer = <div>
            <a href="#" className="insertMedia-help" aria-label={t('Get Help on Inserting Media')}>
                {t('Help')}
            </a>

            <input
                type="submit"
                className="Button Primary insertMedia-insert"
                value={('Insert')}
                aria-label={('Insert Media')}
            />
        </div>;

        return <InsertPopover
            body={body}
            footer={footer}
            title={title}
            accessibleDescription={description}
            className="insertEmbed"
        />;
    }
}

export default withEditor(EmbedPopover);
