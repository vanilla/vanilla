/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import React, { PropsWithChildren, ReactNode } from "react";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";

interface IProps {
    children: React.ReactNode | React.ReactNode[];
}

export default function DropDownItemGrid(props: IProps) {
    const { children } = props;
    const classes = dropDownClasses();

    return <div className={classes.gridItem}>{children}</div>;
}
