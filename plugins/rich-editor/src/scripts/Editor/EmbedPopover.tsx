/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import { t } from "@core/application";
import { withEditor, IEditorContextProps } from "./ContextProvider";
import Popover from "./Generic/Popover";
import EmbedInsertionModule from "../Quill/EmbedInsertionModule";

interface IProps extends IEditorContextProps {
    isVisible: boolean;
    closeMenuHandler: React.MouseEventHandler<any>;
    blurHandler?: React.FocusEventHandler<any>;
    popoverTitleID: string;
    popoverDescriptionID: string;
    targetTitleOnOpen: string;
    id: string;
}

interface IState {
    url: string;
}

class EmbedPopover extends React.PureComponent<IProps, IState> {

    public state = {
        url: "",
    };

    private embedModule: EmbedInsertionModule;

    public constructor(props) {
        super(props);
        this.embedModule = props.quill.getModule("embed/insertion");
    }

    public render() {
        const title = t("Insert Media");
        const description = t("Insert an embedded web page, or video into your message.");

        const body = <React.Fragment>
            <p id="tempId-insertMediaMenu-p" className="insertMedia-description">
                {t('Paste the URL of the media you want.')}
            </p>
            <input className="InputBox" placeholder="http://" value={this.state.url} onChange={this.inputChangeHandler}/>
        </React.Fragment>;

        const footer = <React.Fragment>
            <a href="#" className="insertMedia-help" aria-label={t('Get Help on Inserting Media')}>
                {t('Help')}
            </a>

            <input
                type="button"
                className="Button Primary insertMedia-insert"
                value={('Insert')}
                aria-label={('Insert Media')}
                onBlur={this.props.blurHandler}
                onClick={this.buttonClickHandler}
            />
        </React.Fragment>;

        return <Popover
            id={this.props.id}
            title={title}
            accessibleDescription={description}
            body={body}
            footer={footer}
            additionalClassRoot="insertMedia"
            closeMenuHandler={this.props.closeMenuHandler}
            isVisible={this.props.isVisible}
            popoverTitleID={this.props.popoverTitleID}
            popoverDescriptionID={this.props.popoverDescriptionID}
            targetTitleOnOpen={this.props.targetTitleOnOpen}
        />;
    }

    private clearInput() {
        this.setState({
            url: "",
        });
    }

    /**
     * Handle a submit button click.
     *
     * @param {React.SyntheticEvent} event - The button press event.
     */
    private buttonClickHandler = (event) => {
        event.preventDefault();
        this.clearInput();
        this.embedModule.scrapeMedia(this.state.url);
    }

    /**
     * Control the inputs value.
     *
     * @param {React.ChangeEvent} event - The change event.
     */
    private inputChangeHandler = (event) => {
        this.setState({url: event.target.value});
    }
}

export default withEditor(EmbedPopover);
