/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { useRef } from "react";

import { FormToggle } from "@library/forms/FormToggle";
import { addonClasses } from "@library/addons/Addons.styles";
import { cx } from "@emotion/css";
import { useUniqueID } from "@library/utility/idUtils";
import { ListItem } from "@library/lists/ListItem";
import { useMeasure } from "@vanilla/react-utils";
import Heading from "@library/layout/Heading";
import { ToolTip } from "@library/toolTip/ToolTip";

export interface IAddon {
    name: string;
    isLoading?: boolean;
    enabled: boolean;
    onEnabledChange: (val: boolean) => void;
    imageUrl: string;
    description?: React.ReactNode;
    notes?: React.ReactNode;
    disabled?: boolean;
    disabledNote?: React.ReactNode;
}

interface IProps extends IAddon {}

const Addon = function (props: IProps) {
    const {
        root,
        column,
        previewAndTextContainer,
        previewContainer,
        previewContainerMobile,
        previewImage,
        textContainer,
        title,
        description,
        notes,
        optionsContainer,
    } = addonClasses();

    const titleID = useUniqueID("labTitle");

    const rootRef = useRef<HTMLDivElement | null>(null);
    const measure = useMeasure(rootRef);

    const forceWrap = measure.width > 0 && measure.width < 600;

    const image = <img className={previewImage} src={props.imageUrl} loading="lazy" />;

    let toggle = (
        <FormToggle
            disabled={props.disabled}
            labelID={titleID}
            indeterminate={props.isLoading}
            enabled={props.enabled}
            onChange={props.onEnabledChange}
        />
    );

    if (props.disabled && props.disabledNote) {
        toggle = (
            <ToolTip label={props.disabledNote}>
                <span>{toggle}</span>
            </ToolTip>
        );
    }

    return (
        <div className={root} ref={rootRef}>
            <div className={cx(column, previewAndTextContainer)}>
                {!forceWrap && <div className={previewContainer}>{image}</div>}
                <div className={textContainer}>
                    <Heading depth={3} id={titleID} className={title}>
                        {props.name}
                    </Heading>
                    {forceWrap && <div className={previewContainerMobile}>{image}</div>}
                    <p className={description}>{props.description}</p>
                    <p className={notes}>{props.notes}</p>
                </div>
            </div>
            <div className={cx(column, optionsContainer)}>{toggle}</div>
        </div>
    );
};

export default Addon;
