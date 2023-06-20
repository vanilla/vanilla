/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */
import { layoutThumbnailsClasses } from "@dashboard/layout/editor/thumbnails/LayoutThumbnails.classes";
import { ILayoutCatalog } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { cx } from "@emotion/css";
import Translate from "@library/content/Translate";
import { userContentClasses } from "@library/content/UserContent.styles";
import { searchBarClasses } from "@library/features/search/SearchBar.styles";
import { ClearButton } from "@library/forms/select/ClearButton";
import SmartLink from "@library/routing/links/SmartLink";
import { useUniqueID } from "@library/utility/idUtils";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { useIsMounted } from "@vanilla/react-utils";
import { CustomRadioGroup, CustomRadioInput } from "@vanilla/ui";
import { spaceshipCompare } from "@vanilla/utils";
import debounce from "lodash/debounce";
import * as React from "react";
import { useCallback, useRef, useState } from "react";

interface IProps {
    labelID: string;
    value?: string;
    onChange?: (section: string) => any;
    widgets: ILayoutCatalog["widgets"];
}

export default function LayoutWidgetsThumbnails(props: IProps) {
    const { widgets, labelID } = props;
    const inputRef = useRef<HTMLInputElement | null>(null);
    const classes = layoutThumbnailsClasses();
    const searchClasses = searchBarClasses();

    //sort widgetIDs by name and exclude disabled widgets
    const [visibleWidgetIDs, updateVisibleWidgetIDs] = useState(Object.keys(widgets));

    const [ownValue, ownOnChange] = useState(Object.keys(widgets)[0] as string);
    const [currentSearchInputValue, setCurrentSearchInputValue] = useState("");
    const value = props.value ?? ownValue;
    const onChange = props.onChange ?? ownOnChange;
    const descriptionID = useUniqueID("widgetDescription");
    const isMounted = useIsMounted();

    const search = (searchInputValue: string) => {
        if (!isMounted()) {
            return;
        }
        let newWidgetIDs: string[] = [];
        Object.keys(widgets).forEach((widgetID: string) => {
            if (widgets[widgetID].name.toLocaleLowerCase().includes(searchInputValue.toLocaleLowerCase())) {
                newWidgetIDs.push(widgetID);
            }
        });
        updateVisibleWidgetIDs(newWidgetIDs);

        const newFirstWidgetID = newWidgetIDs[0] ?? null;
        if (!newWidgetIDs.includes(value) && newFirstWidgetID !== null) {
            // Our existing selected item isn't there.
            onChange(newFirstWidgetID);
        }
    };

    const searchMethodRef = useRef(search);
    searchMethodRef.current = search;
    const debouncedSearch = useCallback(
        debounce((searchInputValue: string) => {
            searchMethodRef.current(searchInputValue);
        }, 1000 / 60),
        [],
    );

    const handleSearchInputChange = (searchInputValue: string) => {
        setCurrentSearchInputValue(searchInputValue);
        debouncedSearch(searchInputValue);
    };

    return (
        <div className={classes.container}>
            <div className={cx(userContentClasses().root, classes.description)} id={descriptionID}>
                <Translate
                    source="Get started selecting the best widget for your Homepage. Find out more in the <1>documentation.</1>"
                    c1={(text) => <SmartLink to="https://success.vanillaforums.com/kb/articles/548">{text}</SmartLink>}
                />
            </div>
            <div className={cx(searchClasses.content, classes.searchContent)}>
                <span className={searchClasses.iconContainer(false)}>
                    <Icon size="compact" icon="search-search" />
                </span>
                <label htmlFor={"searchInput"} className={classes.searchLabel}>
                    <input
                        ref={inputRef}
                        className={classes.searchInput}
                        autoCapitalize="none"
                        autoComplete="off"
                        autoCorrect="off"
                        id="searchInput"
                        spellCheck="false"
                        tabIndex={0}
                        value={currentSearchInputValue}
                        type="text"
                        aria-label="Search Text"
                        placeholder={t("Search")}
                        onChange={(event) => {
                            handleSearchInputChange(event.target.value);
                        }}
                    />
                </label>
                {currentSearchInputValue && (
                    <ClearButton
                        onClick={(e) => {
                            e.preventDefault();
                            e.stopPropagation();
                            e.nativeEvent.stopImmediatePropagation();
                            handleSearchInputChange("");
                            inputRef.current?.focus();
                        }}
                        className={cx(searchClasses.clear, classes.clearButton)}
                    />
                )}
            </div>
            <CustomRadioGroup
                aria-labelledby={labelID}
                aria-describedby={descriptionID}
                name="widget"
                onChange={(selected: string) => onChange(selected)}
                value={value}
                className={cx(classes.thumbnails, classes.smallerThumbnails)}
            >
                {visibleWidgetIDs
                    .sort((widgetIDA, widgetIDB) => {
                        const widgetAName = widgets[widgetIDA]?.name ?? "";
                        const widgetBName = widgets[widgetIDB]?.name ?? "";
                        return spaceshipCompare(widgetAName, widgetBName);
                    })
                    .map((widgetID: string) => {
                        const label = t(widgets[widgetID].name);
                        return (
                            <CustomRadioInput value={widgetID} key={widgetID} className={classes.thumbnailWrapper}>
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
                                                width="120"
                                                height="100"
                                                className={classes.thumbnailImageSmall}
                                                alt={label}
                                                src={widgets[widgetID].iconUrl}
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
