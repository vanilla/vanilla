/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import { t } from "@library/utility/appUtils";
import Frame from "@library/layout/frame/Frame";
import classNames from "classnames";
import FrameBody from "@library/layout/frame/FrameBody";
import { HamburgerIcon, CloseTinyIcon } from "@library/icons/common";
import { hamburgerClasses } from "@library/flyouts/hamburgerStyles";
import TitleBarMobileNav from "@library/headers/TitleBarMobileNav";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { FrameHeaderMinimal } from "@library/layout/frame/FrameHeaderMinimal";
import DropDownSection from "@library/flyouts/items/DropDownSection";
import { siteNavVariables } from "@library/navigation/siteNavStyles";
import { navigationVariables } from "@library/headers/navigationVariables";
import { getCurrentLocale } from "@vanilla/i18n";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import Permission from "@library/features/users/Permission";
import { Navigation } from "@knowledge/navigation/Navigation";

interface IProps {
    className?: string;
    extraNavTop?: React.ReactNode;
    extraNavBottom?: React.ReactNode;
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

    const navItems = navigationVariables().getNavItemsForLocale();

    return (
        <>
            <Button
                baseClass={ButtonTypes.ICON}
                className={classNames(classes.root, props.className)}
                onClick={toggleDrawer}
            >
                <HamburgerIcon />
            </Button>
            <Modal scrollable isVisible={isOpen} size={ModalSizes.MODAL_AS_SIDE_PANEL_LEFT} exitHandler={closeDrawer}>
                <Button
                    className={classes.closeButton}
                    baseClass={ButtonTypes.ICON_COMPACT}
                    onClick={() => setIsOpen(false)}
                >
                    <CloseTinyIcon />
                </Button>
                <div className={classes.container}>
                    <DropDownSection title={t("Site Navigation")}>
                        {navItems.map((item, i) => {
                            return (
                                <Permission key={i} permission={item.permission}>
                                    <DropDownItemLink to={item.to}>{item.children}</DropDownItemLink>
                                </Permission>
                            );
                        })}
                    </DropDownSection>
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
