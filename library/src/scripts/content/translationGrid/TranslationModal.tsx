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
    checkForUnsavedModifications;
    setUnsavedModifications: (unsavedModifications: boolean) => {};
    unsavedModifications: boolean;
}

const saveTranslation = () => {
    alert("Saved!");
};

export default function TranslationModal(props: IProps) {
    const [showConfirmation, setShowConfirmation] = useState(false);

    return (
        <Modal
            exitHandler={checkForUnsavedChanges}
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
                // Use setHasUnsavedModifications to set if we have unsaved changes.
            />
        </Modal>
    );
}
