/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import React, { PropsWithChildren, ReactNode } from "react";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import { cx } from "@emotion/css";
interface IProps {
    children: React.ReactNode | React.ReactNode[];
    isCompact?: boolean;
}

export default function DropDownItemGrid(props: IProps) {
    const { children, isCompact } = props;
    const classes = dropDownClasses();

    return <div className={cx(classes.gridItem, isCompact ? classes.gridItemSmall : undefined)}>{children}</div>;
}
