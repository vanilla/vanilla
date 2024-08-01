import SelectBox, { ISelectBoxItem, ISelectBoxProps } from "@library/forms/select/SelectBox";
import React, { useState } from "react";
import { ISearchBarOverwrites, searchBarClasses } from "@library/features/search/SearchBar.styles";
import { t } from "@vanilla/i18n/src";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { useUniqueID } from "@library/utility/idUtils";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { useDevice, Devices } from "@library/layout/DeviceContext";
import { Icon } from "@vanilla/icons";
import { cx } from "@emotion/css";

export function SearchScope(props: {
    className?: string;
    selectBoxProps: Omit<ISelectBoxProps, "describedBy">;
    separator?: React.ReactNode; // add a separator between drop down and right side
    compact?: boolean; // render compact version
    overwriteSearchBar?: ISearchBarOverwrites;
}) {
    const { className, selectBoxProps, separator, overwriteSearchBar } = props;
    const device = useDevice();
    const compact = props.compact ?? [Devices.XS, Devices.MOBILE].includes(device);
    const classes = searchBarClasses(overwriteSearchBar);
    const labelID = useUniqueID("searchIn");

    return (
        <div
            className={cx(classes.scope, className, {
                isCompact: compact,
            })}
        >
            <ScreenReaderContent>
                <span id={labelID}>{t("Search In")}</span>
            </ScreenReaderContent>
            <SelectBox
                {...selectBoxProps}
                className={classes.scopeSelect}
                verticalPadding={false}
                buttonType={ButtonTypes.CUSTOM}
                buttonClassName={classes.scopeToggle}
                describedBy={labelID}
                renderLeft={false}
                labelWrap={classes.scopeLabelWrap}
                horizontalOffset={false}
                afterButton={separator}
                // The intention here is by default, we'll get the compact value from the layout context if undefined, or a value from props.
                overwriteButtonContents={compact ? <Icon size="compact" icon="search-search" /> : undefined}
            />
        </div>
    );
}
