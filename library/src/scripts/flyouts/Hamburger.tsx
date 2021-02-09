/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { hasPermission } from "@library/features/users/Permission";
import { hamburgerClasses } from "@library/flyouts/hamburgerStyles";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { INavigationVariableItem, navigationVariables } from "@library/headers/navigationVariables";
import { CloseTinyIcon, HamburgerIcon } from "@library/icons/common";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { t } from "@library/utility/appUtils";
import classNames from "classnames";
import React, { useMemo, useState } from "react";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { INavigationTreeItem } from "@vanilla/library/src/scripts/@types/api/core";
import { notEmpty } from "@vanilla/utils";
import { DropDownPanelNav } from "@library/flyouts/panelNav/DropDownPanelNav";
import { IPanelNavItemsProps } from "@library/flyouts/panelNav/PanelNavItems";
import MobileOnlyNavigation from "@library/headers/MobileOnlyNavigation";

interface IProps {
    className?: string;
    extraNavTop?: React.ReactNode;
    extraNavBottom?: React.ReactNode;
    showCloseIcon?: boolean;
}

const extraNavGroups: React.ComponentType[] = [];

export function addHamburgerNavGroup(node: React.ComponentType) {
    extraNavGroups.push(node);
}

/**
 * Creates a hamburger menu.
 */
export default function Hamburger(props: IProps) {
    const [isOpen, setIsOpen] = useState(false);
    const classes = hamburgerClasses();

    const closeDrawer = () => {
        setIsOpen(false);
    };

    const toggleDrawer = () => {
        setIsOpen(!isOpen);
    };

    const { showCloseIcon = true } = props;

    return (
        <>
            <Button
                baseClass={ButtonTypes.ICON}
                className={classNames(classes.root, props.className)}
                onClick={toggleDrawer}
            >
                <>
                    <HamburgerIcon />
                    <ScreenReaderContent>{t("Menu")}</ScreenReaderContent>
                </>
            </Button>
            <Modal scrollable isVisible={isOpen} size={ModalSizes.MODAL_AS_SIDE_PANEL_LEFT} exitHandler={closeDrawer}>
                {showCloseIcon && (
                    <Button
                        className={classes.closeButton}
                        baseClass={ButtonTypes.ICON_COMPACT}
                        onClick={() => {
                            setIsOpen(false);
                        }}
                    >
                        <ScreenReaderContent>{t("Close")}</ScreenReaderContent>
                        <CloseTinyIcon />
                    </Button>
                )}
                <div className={classes.container}>
                    <SiteNavigation onClose={() => setIsOpen(false)} />
                    <MobileOnlyNavigation />
                    {props.extraNavTop}
                    {props.extraNavBottom}
                    {extraNavGroups.map((GroupComponent, i) => (
                        <GroupComponent key={i} />
                    ))}
                </div>
            </Modal>
        </>
    );
}

interface ISiteNavigationProps {
    onClose: () => void;
}

export function varItemToNavTreeItem(
    variableItem: INavigationVariableItem,
    parentID: string = "root",
): INavigationTreeItem | null {
    const { permission, name, url, id, children, isHidden } = variableItem;

    if (permission && !hasPermission(permission)) {
        return null;
    }

    if (isHidden) {
        return null;
    }

    return {
        name,
        url,
        recordID: id,
        recordType: "customLink",
        parentID: parentID,
        children: children?.map((child) => varItemToNavTreeItem(child, id)).filter(notEmpty) ?? [],
        sort: 0,
    };
}

export function getActiveRecord(navTreeItems: INavigationTreeItem[]): IPanelNavItemsProps["activeRecord"] {
    let currentItemID: string | null = null;

    for (const item of navTreeItems) {
        if (window.location.href.includes(item.url.replace("~", ""))) {
            currentItemID = `${item.recordID}`;
        }
    }
    return {
        recordID: currentItemID ?? "notspecified",
        recordType: "customLink",
    };
}

function SiteNavigation(props: ISiteNavigationProps) {
    const { navigationItems } = navigationVariables();

    const [treeItems, activeRecord] = useMemo(() => {
        const treeItems = navigationItems.map((item) => varItemToNavTreeItem(item)).filter(notEmpty);
        const activeRecord = getActiveRecord(treeItems);
        return [treeItems, activeRecord];
    }, [navigationItems]);

    return (
        <DropDownPanelNav
            onClose={props.onClose}
            navItems={treeItems}
            title={t("Site Navigation")}
            isNestable
            activeRecord={activeRecord}
        />
    );
}
