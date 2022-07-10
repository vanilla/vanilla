/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import React from "react";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import { cx } from "@emotion/css";
interface IProps {
    label: string;
    thumbnail: React.ReactNode;
    onClick?();
    isCompact?: boolean;
}

export default function DropDownItemThumbnail(props: IProps) {
    const { label, thumbnail, onClick, isCompact } = props;
    const classes = dropDownClasses();

    return (
        <div
            className={cx(classes.thumbnailItem, isCompact ? classes.thumbnailItemSmall : undefined)}
            onClick={onClick}
            tabIndex={0}
        >
            <span className={classes.thumbnailItemThumbnail}>{thumbnail}</span>
            <span className={classes.thumbnailItemLabel}>{label}</span>
        </div>
    );
}
