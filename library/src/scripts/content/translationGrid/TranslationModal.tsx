/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { RefObject, useState } from "react";
import ModalConfirm from "@library/modal/ModalConfirm";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import classNames from "classnames";
import { t } from "@library/utility/appUtils";
import LocationPicker from "@knowledge/modules/locationPicker/LocationPicker";
import { TranslationGrid } from "@library/content/translationGrid/TranslationGrid";
import { translationGridData } from "@library/content/translationGrid/translationGrid.storyData";

interface IProps {
    className?: string;
    buttonRef: RefObject<HTMLButtonElement>;
    // setUnsavedModifications: (unsavedModifications: boolean) => void;
    // unsavedModifications: boolean;
}

const saveTranslation = () => {
    alert("Saved!");
};

const exitHandler = () => {};

export default function TranslationModal(props: IProps) {
    const [showConfirmation, setShowConfirmation] = useState(false);
    const { className, buttonRef } = props;

    console.log("translationGridData.i18nLocales", translationGridData.i18nLocales);

    return (
        <Modal
            exitHandler={exitHandler}
            size={ModalSizes.LARGE}
            className={classNames(props.className)}
            label={t("Choose a location for this page.")}
            elementToFocusOnExit={props.buttonRef.current as HTMLButtonElement}
            scrollable={true}
        >
            <TranslationGrid
                data={translationGridData.data}
                inScrollingContainer={true}
                otherLanguages={translationGridData.otherLanguages}
                i18nLocales={translationGridData.i18nLocales}
                dateUpdated={"2019-10-09T20:05:51+00:00"}
            />
        </Modal>
    );
}
