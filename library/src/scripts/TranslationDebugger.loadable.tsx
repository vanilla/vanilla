/* eslint-disable no-console */
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { Toast } from "@library/features/toaster/Toast";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import Message from "@library/messages/Message";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { singleBorder } from "@library/styles/styleHelpersBorders";
import { useMissingTranslations } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { useCopier } from "@vanilla/react-utils";
import { useEffect, useState } from "react";

export default function TranslationDebugger() {
    const missingTranslations = useMissingTranslations();
    const [detailIsOpen, setIsDetailOpen] = useState(false);

    useEffect(() => {
        if (missingTranslations.length === 0) {
            return;
        }
        const style =
            "background-color: darkblue; color: white; font-style: italic; border: 5px solid hotpink; font-size: 2em;";
        console.log("%cTranslations Debug: There are missing translations in this page.", style);
        console.table(missingTranslations);
    }, [missingTranslations]);

    if (missingTranslations.length === 0) {
        return <></>;
    }

    return (
        <>
            <Toast visibility={true} portal={true}>
                <span>
                    {missingTranslations.length} missing translations found.{" "}
                    <Button
                        buttonType={ButtonTypes.TEXT_PRIMARY}
                        onClick={() => {
                            setIsDetailOpen(true);
                        }}
                    >
                        See Details
                    </Button>
                </span>
            </Toast>
            {detailIsOpen && (
                <TranslationModal
                    missingTranslations={missingTranslations}
                    onClose={() => {
                        setIsDetailOpen(false);
                    }}
                />
            )}
        </>
    );
}

function TranslationModal(props: { missingTranslations: string[]; onClose: () => void }) {
    const copier = useCopier();

    return (
        <Modal size={ModalSizes.LARGE} isVisible={true} exitHandler={props.onClose}>
            <Frame
                header={<FrameHeader title={"Missing Translations"} closeFrame={props.onClose} />}
                body={
                    <FrameBody>
                        <Message
                            className={classes.message}
                            icon={<Icon icon={"status-warning"} size={"compact"} />}
                            title={"These strings are missing translation definitions."}
                            stringContents={
                                "Developers please add these to the translation files. QA please file a ticket if you see this."
                            }
                        />
                        <h4>Source String</h4>

                        <ul className={classes.rows}>
                            {props.missingTranslations.map((translation, i) => (
                                <Row text={translation} key={i} />
                            ))}
                        </ul>
                    </FrameBody>
                }
                footer={
                    <FrameFooter justifyRight={true}>
                        <Button
                            buttonType={ButtonTypes.TEXT}
                            onClick={() => {
                                const result = props.missingTranslations
                                    .map((translation) => {
                                        const escaped = translation.replace(/"/g, '\\"');
                                        return `$Definition["${escaped}"] = "${escaped}";`;
                                    })
                                    .join("\n");
                                copier.copyValue(result);
                            }}
                        >
                            {copier.wasCopied ? "Copied" : "Copy PHP Definitions"}
                        </Button>
                    </FrameFooter>
                }
            />
        </Modal>
    );
}

function Row(props: { text: string }) {
    const copier = useCopier();
    return (
        <li className={classes.row}>
            <span className={classes.rowText}>{props.text}</span>
            <Button buttonType={ButtonTypes.TEXT} onClick={() => copier.copyValue(props.text)}>
                {copier.wasCopied ? "Copied" : "Copy Text"}
            </Button>
        </li>
    );
}

const classes = {
    message: css({
        marginTop: 16,
        marginBottom: 16,
    }),
    rows: css({
        marginBottom: 16,
        marginTop: 4,
    }),
    rowText: css({
        minWidth: 0,
        flex: 1,
    }),
    row: css({
        borderTop: singleBorder(),
        padding: "6px 0",
        display: "flex",
        alignItems: "center",
        justifyContent: "space-between",
        gap: 16,
        "&:last-child": {
            borderBottom: singleBorder(),
        },
    }),
};
