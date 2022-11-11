/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */
import * as React from "react";
import { t } from "@vanilla/i18n";
import { ILayoutCatalog, LayoutSectionID } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { ToolTip } from "@library/toolTip/ToolTip";
import { InformationIcon } from "@library/icons/common";
import Translate from "@library/content/Translate";
import SmartLink from "@library/routing/links/SmartLink";
import { useState } from "react";
import { cx } from "@emotion/css";
import { userContentClasses } from "@library/content/UserContent.styles";
import { useUniqueID } from "@library/utility/idUtils";
import { CustomRadioGroup, CustomRadioInput } from "@vanilla/ui";
import { RecordID } from "@vanilla/utils";
import { layoutThumbnailsClasses } from "@dashboard/layout/editor/thumbnails/LayoutThumbnails.classes";
interface IProps {
    labelID?: string;
    label?: string;
    value?: string;
    onSectionClick?: (section: LayoutSectionID) => void;
    onChange?: (section: LayoutSectionID) => void;
    sections: ILayoutCatalog["sections"];
}

export default function LayoutSectionsThumbnails(props: IProps) {
    const { sections } = props;
    const classes = layoutThumbnailsClasses();
    const [ownValue, ownOnChange] = useState(Object.keys(sections)[0] as RecordID);
    const value = props.value ?? ownValue;
    const onChange = props.onChange ?? ownOnChange;

    const descriptionID = useUniqueID("layoutSection");
    const labelID = props.labelID ?? descriptionID + "-label";

    return (
        <div className={classes.container}>
            <div className={cx(userContentClasses().root, classes.description)} id={descriptionID}>
                {props.label && (
                    <p id={labelID}>
                        <strong>{props.label}</strong>
                    </p>
                )}
                <p>
                    <Translate
                        source="Select a section you want to add for your page. Find out more in the <1>documentation.</1>"
                        c1={(text) => (
                            <SmartLink to="https://success.vanillaforums.com/kb/articles/544-create-a-custom-layout#sections">
                                {text}
                            </SmartLink>
                        )}
                    />
                </p>
            </div>
            <CustomRadioGroup
                aria-labelledby={labelID}
                aria-describedby={descriptionID}
                name="layoutSection"
                onChange={onChange}
                value={value}
                className={classes.thumbnails}
            >
                {Object.keys(sections)
                    .reverse()
                    .map((sectionID: LayoutSectionID) => {
                        const label = t(sections[sectionID].name);
                        const accessibleDescription = t(sections[sectionID].name);

                        return (
                            <CustomRadioInput
                                onClick={(e) => {
                                    // Browser fire a click event even if an item is selected with keyboard.
                                    // https://github.com/facebook/react/issues/7407
                                    if (e.type === "click" && e.clientX !== 0 && e.clientY !== 0) {
                                        // This is a real click. Do something here
                                        props.onSectionClick?.(sectionID);
                                    }
                                }}
                                value={sectionID}
                                accessibleDescription={accessibleDescription}
                                key={sectionID}
                                className={classes.thumbnailWrapper}
                            >
                                {({ isSelected, isFocused }) => (
                                    <>
                                        <span
                                            role="decoration"
                                            className={cx(classes.thumbnail, {
                                                isSelected,
                                                "focus-visible": isFocused,
                                            })}
                                        >
                                            <img
                                                width="188"
                                                height="138"
                                                className={classes.thumbnailImage}
                                                alt={label}
                                                src={sections[sectionID].iconUrl}
                                            />
                                        </span>

                                        <div className={classes.labelContainer}>{t(label)}</div>
                                    </>
                                )}
                            </CustomRadioInput>
                        );
                    })}
            </CustomRadioGroup>
        </div>
    );
}
