/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import React from "react";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";

interface IProps {
    label: string;
    thumbnail: React.ReactNode;
    onClick?();
}

export default function DropDownItemThumbnail(props: IProps) {
    const { label, thumbnail, onClick } = props;
    const classes = dropDownClasses();

    return (
        <div className={classes.thumbnailItem} onClick={onClick}>
            <span className={classes.thumbnailItemThumbnail}>{thumbnail}</span>
            <span className={classes.thumbnailItemLabel}>{label}</span>
        </div>
    );
}
