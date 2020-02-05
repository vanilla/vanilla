/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Permission from "@library/features/users/Permission";
import { hamburgerClasses } from "@library/flyouts/hamburgerStyles";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import DropDownSection from "@library/flyouts/items/DropDownSection";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { navigationVariables } from "@library/headers/navigationVariables";
import { CloseTinyIcon, HamburgerIcon } from "@library/icons/common";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { t } from "@library/utility/appUtils";
import classNames from "classnames";
import React, { useState } from "react";

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
