/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@vanilla/i18n";

interface ISteppedModalFooterProps {
    isFirstStep: boolean;
    isFinalStep: boolean;
    onCancel: () => void;
    onBack: () => void;
    onNext: () => void;
    onFinalize: () => void;
    finalizeLabel?: string;
    disable?: boolean;
    loading?: boolean;
    disableNextReason?: string;
}

export function SteppedModalFooter(props: ISteppedModalFooterProps) {
    const { isFirstStep, isFinalStep, onCancel, onBack, onNext, finalizeLabel, onFinalize, disable, loading } = props;
    let nextButton = (
        <Button buttonType={ButtonTypes.TEXT_PRIMARY} onClick={onNext} disabled={disable || !!props.disableNextReason}>
            {t("Next")}
        </Button>
    );

    if (props.disableNextReason) {
        nextButton = (
            <ToolTip label={props.disableNextReason}>
                <span>{nextButton}</span>
            </ToolTip>
        );
    }
    return (
        <>
            {isFirstStep ? (
                <Button buttonType={ButtonTypes.TEXT} onClick={onCancel} disabled={disable}>
                    {t("Cancel")}
                </Button>
            ) : (
                <Button buttonType={ButtonTypes.TEXT} onClick={onBack} disabled={disable}>
                    {t("Back")}
                </Button>
            )}
            {!isFinalStep ? (
                nextButton
            ) : (
                <Button buttonType={ButtonTypes.TEXT_PRIMARY} onClick={onFinalize} disabled={disable} submit>
                    {loading ? <ButtonLoader /> : finalizeLabel ?? t("Save")}
                </Button>
            )}
        </>
    );
}
