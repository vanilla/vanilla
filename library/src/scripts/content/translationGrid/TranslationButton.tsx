/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo, useRef, useState } from "react";
import TranslationModal from "@library/content/translationGrid/TranslationModal";
import Button from "@library/forms/Button";
import { TranslateIcon } from "@library/icons/common";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { t } from "@vanilla/i18n/src";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import ModalConfirm from "@library/modal/ModalConfirm";

export interface ITranslationData {
    id: number;
    resource: string;
    recordType: string;
}

interface IProps {
    translationData: ITranslationData;
}

export default function TranslationButton(props: IProps) {
    const { translationData } = props;
    const [showTranslationModal, setShowTranslationModal] = useState(false);
    const [showConfirmationModal, setConfirmationModal] = useState(false);
    const [exiting, setExiting] = useState(false);
    const buttonRef = useRef<HTMLButtonElement>(null);
    const [unsavedModifications, setUnsavedModifications] = useState(false);

    // For Accessibility
    const id = useMemo(() => {
        return uniqueIDFromPrefix("translation");
    }, []);

    return (
        <>
            <Button
                title={t("Translate")}
                ariaLabel={t("Translate Icon")}
                baseClass={ButtonTypes.ICON_COMPACT}
                controls={showTranslationModal ? id : undefined}
                onClick={() => {
                    setShowTranslationModal(true);
                }}
                buttonRef={buttonRef}
            >
                <TranslateIcon />
            </Button>
            {showTranslationModal && (
                <TranslationModal
                    id={id}
                    unsavedModifications={unsavedModifications}
                    setUnsavedModifications={setUnsavedModifications}
                    buttonRef={buttonRef}
                    {...translationData}
                />
            )}
            {unsavedModifications && exiting && <ModalConfirm />}
        </>
    );
}
