/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/utility/appUtils";
import ModalSizes from "@library/modal/ModalSizes";
import Loader from "@library/loaders/Loader";
import { useHistory, useLocation } from "react-router";
import Modal from "@library/modal/Modal";

interface IProps {}

/**
 * Page for editing an article.
 */
function ModalLoader(props: IProps) {
    const history = useHistory();
    const location = useLocation<{ lastLocation?: string }>();

    /**
     * Route back to the previous location if its available.
     */
    const navigateToBacklink = () => {
        history.push(location.state.lastLocation || "/kb");
    };
    return (
        <Modal
            isVisible={true}
            label={t("Loading Modal")}
            size={ModalSizes.FULL_SCREEN}
            exitHandler={navigateToBacklink}
            elementToFocusOnExit={document.activeElement as HTMLElement}
        >
            <Loader />
        </Modal>
    );
}

export default ModalLoader;
