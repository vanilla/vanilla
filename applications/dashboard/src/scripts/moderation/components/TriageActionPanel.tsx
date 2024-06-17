/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { PageBox } from "@library/layout/PageBox";
import PageHeading from "@library/layout/PageHeading";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { t } from "@vanilla/i18n";

interface IProps {
    hasReports: boolean;
    isResolved: boolean;
    onEscalate?: () => void;
    onDismiss?: () => void;
    onResolve?: () => void;
}

const classes = {
    buttonLayout: css({
        display: "grid",
        gridTemplateColumns: "repeat(2, 1fr)",
        gap: 8,
        marginTop: 16,
        "& > button": {
            minWidth: "revert",
        },
    }),
};

export function TriageActionPanel(props: IProps) {
    const { hasReports, isResolved, onEscalate, onDismiss, onResolve } = props;
    return (
        <PageBox options={{ borderType: BorderType.SHADOW }}>
            <PageHeading depth={5} includeBackLink={false} title={"Actions"} />
            <div className={classes.buttonLayout}>
                {hasReports ? (
                    <Button onClick={() => onDismiss && onDismiss()} buttonType={ButtonTypes.STANDARD}>
                        {t("Dismiss")}
                    </Button>
                ) : (
                    <Button onClick={() => onResolve && onResolve()} buttonType={ButtonTypes.STANDARD}>
                        {isResolved ? t("Unresolve") : t("Resolve")}
                    </Button>
                )}
                <Button onClick={() => onEscalate && onEscalate()} buttonType={ButtonTypes.PRIMARY}>
                    {t("Escalate")}
                </Button>
            </div>
        </PageBox>
    );
}
