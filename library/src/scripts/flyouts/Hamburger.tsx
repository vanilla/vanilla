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
import { INavigationTreeItem } from "@library/@types/api/core";
import { notEmpty } from "@vanilla/utils";
import { DropDownPanelNav } from "@library/flyouts/panelNav/DropDownPanelNav";
import { IPanelNavItemsProps } from "@library/flyouts/panelNav/PanelNavItems";
import MobileOnlyNavigation from "@library/headers/MobileOnlyNavigation";
import { useHamburgerMenuContext } from "@library/contexts/HamburgerMenuContext";

interface IProps {
    className?: string;
    extraNavTop?: React.ReactNode;
    extraNavBottom?: React.ReactNode;
    showCloseIcon?: boolean;
    navigationItems?: INavigationVariableItem[];
    forceHamburgerOpen?: boolean;
}

const extraNavGroups: React.ComponentType[] = [];

export function addHamburgerNavGroup(node: React.ComponentType) {
    extraNavGroups.push(node);
}

/**
 * Creates a hamburger menu.
 */
export default function Hamburger(props: IProps) {
    const [isOpen, setIsOpen] = useState(props.forceHamburgerOpen || false);
    const classes = hamburgerClasses();

    const closeDrawer = () => {
        setIsOpen(false);
    };

    const toggleDrawer = () => {
        setIsOpen(!isOpen);
    };

    const { showCloseIcon = true } = props;

    // Get all the widget components
    const { dynamicComponents } = useHamburgerMenuContext();

    // Create a single fragment containing all the widget components
    const widgetComponents = useMemo(() => {
        if (dynamicComponents) {
            return (
                <>
                    {Object.values(dynamicComponents).map(({ component }, key) => (
                        <React.Fragment key={key}>{component}</React.Fragment>
                    ))}
                </>
            );
        }
        return <></>;
    }, [dynamicComponents]);

    return (
        <>
            <Button
                buttonType={ButtonTypes.ICON}
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
                        buttonType={ButtonTypes.ICON_COMPACT}
                        onClick={() => {
                            setIsOpen(false);
                        }}
                    >
                        <ScreenReaderContent>{t("Close")}</ScreenReaderContent>
                        <CloseTinyIcon />
                    </Button>
                )}
                <div className={classes.container}>
                    <SiteNavigation onClose={() => setIsOpen(false)} navigationItems={props.navigationItems} />
                    <MobileOnlyNavigation />
                    {props.extraNavTop}
                    {widgetComponents}
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
    navigationItems?: INavigationVariableItem[];
}

export function varItemToNavTreeItem(
    variableItem: INavigationVariableItem,
    parentID: string = "root",
): INavigationTreeItem | null {
    const { permission, name, url, id, children, isHidden, badge } = variableItem;

    if (permission && !hasPermission(permission)) {
        return null;
    }

    if (isHidden) {
        return null;
    }

    const navTreeItem = {
        name,
        url,
        recordID: id,
        recordType: "customLink",
        parentID: parentID,
        children: children?.map((child) => varItemToNavTreeItem(child, id)).filter(notEmpty) ?? [],
        sort: 0,
    };

    if (badge) {
        navTreeItem["badge"] = badge;
    }

    return navTreeItem;
}

export function getActiveRecord(navTreeItems: INavigationTreeItem[]): IPanelNavItemsProps["activeRecord"] {
    let currentItemID: string | null = null;

    for (const item of navTreeItems) {
        if (window.location.href.includes(item && item.url ? item.url.replace("~", "") : "")) {
            currentItemID = `${item.recordID}`;
        }
    }
    return {
        recordID: currentItemID ?? "notspecified",
        recordType: "customLink",
    };
}

function SiteNavigation(props: ISiteNavigationProps) {
    const navigationItems =
        props.navigationItems && props.navigationItems.length
            ? props.navigationItems
            : navigationVariables().navigationItems;

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
