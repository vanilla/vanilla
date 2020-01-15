/**
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useRef, useState } from "react";
import { themeCardClasses } from "./themeCardStyles";
import Button from "@library/forms/Button";
import { t } from "@library/utility/appUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { colorOut } from "@library/styles/styleHelpersColors";
import { titleBarVariables } from "@library/headers/titleBarStyles";
import Loader from "@library/loaders/Loader";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { useFocusWatcher } from "@vanilla/react-utils";
import classNames from "classnames";
import DropDown, { FlyoutType, DropDownOpenDirection } from "@library/flyouts/DropDown";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";

interface IProps {
    name?: string;
    previewImage?: string;
    globalBg?: string;
    globalFg?: string;
    globalPrimary?: string;
    titleBarBg?: string;
    titleBarFg?: string;
    headerImg?: string;
    onApply?: () => void;
    isApplyLoading?: boolean;
    onPreview?: () => void;
    onCopy?: () => void;
    onEdit?: () => void;
    onDelete?: () => void;
    isActiveTheme: boolean;
    noActions?: boolean;
    isThemeDb?: boolean;
}

export default function ThemePreviewCard(props: IProps) {
    const tiles = [1, 2, 3, 4];
    const vars = globalVariables();
    const titleVars = titleBarVariables();
    const {
        globalBg = colorOut(vars.mainColors.bg),
        globalPrimary = colorOut(vars.mainColors.primary),
        globalFg = colorOut(vars.mainColors.fg),
        titleBarBg = colorOut(titleVars.colors.bg),
        titleBarFg = colorOut(titleVars.colors.fg),
    } = props;
    const classes = themeCardClasses();
    const titlebarStyle = {
        backgroundColor: titleBarBg,
    };

    const titleBarLinks = {
        backgroundColor: titleBarFg,
    };

    const containerStyle = {
        backgroundColor: `${globalBg}`,
    };

    const headerStyle = {
        backgroundColor: globalPrimary,
    };

    const [hasFocus, setHasFocus] = useState(false);
    const containerRef = useRef<HTMLDivElement | null>(null);
    useFocusWatcher(containerRef, setHasFocus);

    return (
        <div
            ref={containerRef}
            style={containerStyle}
            className={classNames(
                hasFocus && classes.isFocused,
                props.noActions ? classes.noActions : classes.container,
            )}
            tabIndex={0}
            title={props.name}
        >
            <div className={classes.menuBar}>
                {[0, 1, 2].map(key => (
                    <span key={key} className={classes.dots}></span>
                ))}
            </div>
            {props.previewImage ? (
                <img className={classes.previewImage} src={props.previewImage} />
            ) : (
                <div className={classes.wrapper}>
                    <div style={titlebarStyle} className={classes.titlebar}>
                        <ul className={classes.titleBarNav}>
                            {[0, 1, 2].map(key => (
                                <li key={key} style={titleBarLinks} className={classes.titleBarLinks}></li>
                            ))}
                        </ul>
                    </div>
                    <div style={headerStyle} className={classes.header}>
                        <div className={classes.title}></div>
                        <div className={classes.search}>
                            <span className={classes.bar}></span>
                            <span className={classes.search_btn}>
                                <span className={classes.searchText}></span>
                            </span>
                        </div>
                    </div>
                    <div className={classes.content}>
                        <ul className={classes.contentList}>
                            {tiles.map((val, key) => (
                                <li key={key} className={classes.contentListItem}>
                                    <div className={classes.contentTile}>
                                        <div className={classes.tileImg} style={{ borderColor: globalPrimary }}></div>
                                        <div className={classes.tileHeader}></div>
                                        <div className={classes.tileContent}>
                                            <p className={classes.text1}></p>
                                            <p className={classes.text2}></p>
                                            <p className={classes.text3}></p>
                                        </div>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>
            )}
            {!props.noActions && (
                <div className={props.noActions ? classes.noOverlay : classes.overlay}>
                    {props.isThemeDb && (
                        <div className={classes.actionDropdown}>
                            <DropDown flyoutType={FlyoutType.LIST} renderLeft={true}>
                                <DropDownItemButton name={t("Edit")} onClick={props.onEdit} />
                                <DropDownItemButton name={t("Copy")} onClick={props.onCopy} />
                                <DropDownItemSeparator />
                                <DropDownItemButton name={t("Delete")} onClick={props.onDelete} />
                            </DropDown>
                        </div>
                    )}
                    <div className={classes.actionButtons}>
                        <Button
                            className={classes.buttons}
                            onClick={() => {
                                containerRef.current?.focus();
                                props.onApply?.();
                            }}
                        >
                            {props.isApplyLoading ? <ButtonLoader /> : t("Apply")}
                        </Button>
                        <Button className={classes.buttons} onClick={props.onPreview}>
                            {t("Preview")}
                        </Button>
                        {!props.isThemeDb && (
                            <Button className={classes.buttons} onClick={props.onCopy}>
                                {t("Copy")}
                            </Button>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
