/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import React from "react";
import { frameClasses } from "@library/layout/frame/frameStyles";
import Button from "@library/forms/Button";
import { CloseTinyIcon } from "@library/icons/common";
import { frameHeaderClasses } from "@library/layout/frame/frameHeaderStyles";
import classNames from "classnames";
import { ButtonTypes } from "@library/forms/buttonStyles";

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
                <Button baseClass={ButtonTypes.ICON_COMPACT} onClick={props.onClose} className={classes.closeMinimal}>
                    <CloseTinyIcon />
                </Button>
            )}
        </header>
    );
}
