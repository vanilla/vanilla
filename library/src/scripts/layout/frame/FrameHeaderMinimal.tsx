/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { CloseTinyIcon } from "@library/icons/common";
import { frameHeaderClasses } from "@library/layout/frame/frameHeaderStyles";
import classNames from "classnames";
import React from "react";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { t } from "@vanilla/i18n/src";

interface IProps {
    children?: React.ReactNode;
    onClose?: () => void;
}

export function FrameHeaderMinimal(props: IProps) {
    const classes = frameHeaderClasses();
    return (
        <header className={classNames(classes.root, classes.rootMinimal)}>
            <h2 className={classNames(classes.centred, classes.headingMinimal)}>{props.children}</h2>
            {props.onClose && (
                <Button buttonType={ButtonTypes.ICON_COMPACT} onClick={props.onClose} className={classes.closeMinimal}>
                    <ScreenReaderContent>{t("Close")}</ScreenReaderContent>
                    <CloseTinyIcon aria-hidden="true" />
                </Button>
            )}
        </header>
    );
}
