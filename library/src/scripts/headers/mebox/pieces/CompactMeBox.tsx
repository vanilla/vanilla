/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useRef, useState } from "react";
import MessagesContents from "@library/headers/mebox/pieces/MessagesContents";
import { compactMeBoxClasses } from "@library/headers/mebox/pieces/compactMeBoxStyles";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import CloseButton from "@library/navigation/CloseButton";
import { inheritHeightClass } from "@library/styles/styleHelpers";
import NotificationsContents from "@library/headers/mebox/pieces/NotificationsContents";
import { getMeta, accessibleLabel, t } from "@library/utility/appUtils";
import NotificationsCount from "@library/headers/mebox/pieces/NotificationsCount";
import MessagesCount from "@library/headers/mebox/pieces/MessagesCount";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { IMeBoxProps } from "@library/headers/mebox/MeBox";
import Tabs from "@library/navigation/tabs/Tabs";
import UserDropDownContents from "@library/headers/mebox/pieces/UserDropDownContents";
import classNames from "classnames";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { titleBarClasses } from "@library/headers/TitleBar.classes";
import { MeBoxIcon } from "@library/headers/mebox/pieces/MeBoxIcon";
import { TouchScrollable } from "react-scrolllock";
import { UserIcon, UserIconTypes } from "@library/icons/titleBar";

interface IProps extends IMeBoxProps {}

/**
 * Implements User Drop down for header
 */
export default function CompactMeBox(props: IProps) {
    const userInfo = props.currentUser;
    const buttonRef = useRef<HTMLButtonElement | null>(null);
    const [open, setOpen] = useState(false);

    const classes = compactMeBoxClasses.useAsHook();
    const titleBarVars = titleBarClasses.useAsHook();
    if (!userInfo) {
        return null;
    }

    const panelBodyClass = classNames("compactMeBox-body", classes.body);

    const titleText = t("Me");
    const altText = accessibleLabel(t(`User: "%s"`), [t(`Me`)]);

    const isConversationsEnabled = getMeta("context.conversationsEnabled", false);

    return (
        <div className={classNames("compactMeBox", props.className, classes.root)}>
            <Button
                title={t("My Account")}
                className={classNames(classes.openButton, titleBarVars.centeredButton, titleBarVars.button)}
                onClick={() => {
                    setOpen(true);
                }}
                buttonRef={buttonRef}
                buttonType={ButtonTypes.CUSTOM}
            >
                <UserPhoto userInfo={userInfo} className="meBox-user" size={UserPhotoSize.SMALL} />
            </Button>
            <Modal
                isVisible={open}
                size={ModalSizes.MODAL_AS_SIDE_PANEL_RIGHT}
                elementToFocusOnExit={buttonRef.current!}
                exitHandler={() => {
                    setOpen(false);
                }}
            >
                <Tabs
                    label={t("My Account Tab")}
                    tabListClass={classNames(classes.tabList)}
                    tabPanelsClass={classNames(classes.tabPanels, inheritHeightClass())}
                    tabPanelClass={classNames(inheritHeightClass(), classes.panel)}
                    buttonClass={classNames(classes.tabButton)}
                    extraTabContent={
                        <CloseButton
                            onClick={() => {
                                setOpen(false);
                            }}
                            className={classNames(classes.closeModal)}
                        />
                    }
                    tabs={[
                        ...[
                            {
                                buttonContent: (
                                    <MeBoxIcon compact={true}>
                                        <UserIcon
                                            styleType={UserIconTypes.SELECTED_INACTIVE}
                                            title={titleText}
                                            alt={altText}
                                        />
                                    </MeBoxIcon>
                                ),
                                openButtonContent: (
                                    <MeBoxIcon compact={true}>
                                        <UserIcon
                                            styleType={UserIconTypes.SELECTED_ACTIVE}
                                            title={titleText}
                                            alt={altText}
                                        />
                                    </MeBoxIcon>
                                ),
                                panelContent: (
                                    <TouchScrollable>
                                        <div className={classes.scrollContainer}>
                                            <UserDropDownContents />
                                        </div>
                                    </TouchScrollable>
                                ),
                            },
                            {
                                buttonContent: <NotificationsCount open={false} compact={true} />,
                                openButtonContent: <NotificationsCount open={true} compact={true} />,
                                panelContent: (
                                    <NotificationsContents panelBodyClass={panelBodyClass} userSlug={userInfo.name} />
                                ),
                            },
                        ],
                        ...(isConversationsEnabled
                            ? [
                                  {
                                      buttonContent: <MessagesCount open={false} compact={true} />,
                                      openButtonContent: <MessagesCount open={true} compact={true} />,
                                      panelContent: <MessagesContents className={panelBodyClass} />,
                                  },
                              ]
                            : []),
                    ]}
                />
            </Modal>
        </div>
    );
    // }
}
