/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import MessagesContents from "@library/headers/mebox/pieces/MessagesContents";
import { compactMeBoxClasses } from "@library/headers/mebox/pieces/compactMeBoxStyles";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import CloseButton from "@library/navigation/CloseButton";
import { inheritHeightClass } from "@library/styles/styleHelpers";
import NotificationsContents from "@library/headers/mebox/pieces/NotificationsContents";
import { t } from "@library/utility/appUtils";
import NotificationsCount from "@library/headers/mebox/pieces/NotificationsCount";
import MessagesCount from "@library/headers/mebox/pieces/MessagesCount";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { IMeBoxProps } from "@library/headers/mebox/MeBox";
import Tabs from "@library/navigation/tabs/Tabs";
import { IInjectableUserState } from "@library/features/users/userModel";
import UserDropDownContents from "@library/headers/mebox/pieces/UserDropDownContents";
import classNames from "classnames";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { titleBarClasses } from "@library/headers/titleBarStyles";
import { MeBoxIcon } from "@library/headers/mebox/pieces/MeBoxIcon";
import { user } from "@library/icons/header";
import { TouchScrollable } from "react-scrolllock";

interface IProps extends IInjectableUserState, IMeBoxProps {}

interface IState {
    open: boolean;
}

/**
 * Implements User Drop down for header
 */
export default class CompactMeBox extends React.Component<IProps, IState> {
    private buttonRef: React.RefObject<HTMLButtonElement> = React.createRef();

    public state = {
        open: false,
    };

    public render() {
        const userInfo = this.props.currentUser.data;
        if (!userInfo) {
            return null;
        }

        const classes = compactMeBoxClasses();
        const titleBarVars = titleBarClasses();
        const panelBodyClass = classNames("compactMeBox-body", classes.body);

        return (
            <div className={classNames("compactMeBox", this.props.className, classes.root)}>
                <Button
                    title={t("My Account")}
                    className={classNames(classes.openButton, titleBarVars.centeredButtonClass, titleBarVars.button)}
                    onClick={this.open}
                    buttonRef={this.buttonRef}
                    baseClass={ButtonTypes.CUSTOM}
                >
                    <UserPhoto
                        userInfo={userInfo}
                        open={this.state.open}
                        className="meBox-user"
                        size={UserPhotoSize.SMALL}
                    />
                </Button>
                {this.state.open && (
                    <Modal
                        size={ModalSizes.MODAL_AS_SIDE_PANEL}
                        label={t("Article Revisions")}
                        elementToFocusOnExit={this.buttonRef.current!}
                        exitHandler={this.close}
                    >
                        <Tabs
                            label={t("My Account Tab")}
                            tabListClass={classNames(classes.tabList)}
                            tabPanelsClass={classNames(classes.tabPanels, inheritHeightClass())}
                            tabPanelClass={classNames(inheritHeightClass(), classes.panel)}
                            buttonClass={classNames(classes.tabButton)}
                            extraTabContent={
                                <CloseButton onClick={this.close} className={classNames(classes.closeModal)} />
                            }
                            tabs={[
                                {
                                    buttonContent: <MeBoxIcon compact={true}>{user(false)}</MeBoxIcon>,
                                    openButtonContent: <MeBoxIcon compact={true}>{user(true)}</MeBoxIcon>,
                                    panelContent: (
                                        <TouchScrollable>
                                            <UserDropDownContents className={classes.scrollContainer} />
                                        </TouchScrollable>
                                    ),
                                },
                                {
                                    buttonContent: <NotificationsCount open={false} compact={true} />,
                                    openButtonContent: <NotificationsCount open={true} compact={true} />,
                                    panelContent: (
                                        <NotificationsContents
                                            panelBodyClass={panelBodyClass}
                                            userSlug={userInfo.name}
                                        />
                                    ),
                                },
                                {
                                    buttonContent: <MessagesCount open={false} compact={true} />,
                                    openButtonContent: <MessagesCount open={true} compact={true} />,
                                    panelContent: <MessagesContents className={panelBodyClass} />,
                                },
                            ]}
                        />
                    </Modal>
                )}
            </div>
        );
    }

    private open = () => {
        this.setState({
            open: true,
        });
    };
    private close = () => {
        this.setState({
            open: false,
        });
    };
}
