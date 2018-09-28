/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import { withRouter, RouteComponentProps } from "react-router-dom";
import Modal from "./Modal";
import FullPageLoader from "@library/components/FullPageLoader";

interface IProps extends RouteComponentProps<{}> {}

/**
 * Page for editing an article.
 */
class ModalLoader extends React.Component<IProps> {
    public render() {
        return (
            <Modal
                exitHandler={this.navigateToBacklink}
                appContainer={document.getElementById("app")!}
                container={document.getElementById("modals")!}
            >
                <FullPageLoader />
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
