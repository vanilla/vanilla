/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { cx } from "@emotion/css";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { CloseTinyIcon } from "@library/icons/common";
import { TokenItemClasses } from "@library/metas/TokenItem.styles";
import { Icon } from "@vanilla/icons";
import React, { ReactNode } from "react";

interface IProps extends React.HTMLAttributes<HTMLSpanElement> {
    classNames?: string;
    children: ReactNode;
    onRemove?: () => void;
}

export const TokenItem = React.forwardRef(function TokenItemImpl(props: IProps, ref: React.RefObject<HTMLDivElement>) {
    const { onRemove, ...rest } = props;
    const classes = TokenItemClasses();
    return (
        <span {...rest} ref={ref} className={cx(classes.root, props.className, "token")}>
            <span className={cx(classes.textContent, "tokenText")}>{props.children}</span>
            {onRemove && (
                <Button buttonType={ButtonTypes.ICON_COMPACT} className={classes.button} onClick={onRemove}>
                    <CloseTinyIcon className={classes.icon} />
                </Button>
            )}
        </span>
    );
});
