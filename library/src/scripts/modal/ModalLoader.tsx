/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/utility/appUtils";
import ModalSizes from "@library/modal/ModalSizes";
import Loader from "@library/loaders/Loader";
import { RouteComponentProps, withRouter } from "react-router";
import Modal from "@library/modal/Modal";

interface IProps extends RouteComponentProps<{}> {}

/**
 * Page for editing an article.
 */
class ModalLoader extends React.Component<IProps> {
    public render() {
        return (
            <Modal
                label={t("Loading Modal")}
                size={ModalSizes.FULL_SCREEN}
                exitHandler={this.navigateToBacklink}
                elementToFocusOnExit={document.activeElement as HTMLElement}
            >
                <Loader />
            </Modal>
        );
    }

    /**
     * Route back to the previous location if its available.
     */
    private navigateToBacklink = () => {
        this.props.history.push(this.props.location.state.lastLocation || "/kb");
    };
}

export default withRouter<IProps>(ModalLoader);
