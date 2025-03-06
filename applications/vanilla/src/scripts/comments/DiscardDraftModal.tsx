/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import ModalConfirm from "@library/modal/ModalConfirm";
import { nestCommentListClasses } from "@vanilla/addon-vanilla/comments/NestedComments.classes";
import { t } from "@vanilla/i18n";

interface IProps {
    isVisible: boolean;
    setVisibility: (isVisible: boolean) => void;
    onConfirm: () => void;
    onCancel: () => void;
}

export function DiscardDraftModal(props: IProps) {
    const classes = nestCommentListClasses();
    return (
        <ModalConfirm
            isVisible={props.isVisible}
            title={t("Discard Draft")}
            onCancel={() => {
                props.onCancel();
                props.setVisibility(false);
            }}
            onConfirm={() => {
                props.onConfirm();
                props.setVisibility(false);
            }}
            confirmTitle={t("Discard")}
            confirmClasses={classes.warningModalConfirm}
        >
            <div className={classes.warningModalContent}>
                <p>{t("You have an unposted draft, replying to this comment will discard your draft.")}</p>
                <p className={classes.warningEmphasis}>{t("Are you sure you want to discard it?")}</p>
            </div>
        </ModalConfirm>
    );
}
