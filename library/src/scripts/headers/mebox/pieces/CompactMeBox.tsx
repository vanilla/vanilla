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
import { accessibleLabel, t } from "@library/utility/appUtils";
import NotificationsCount from "@library/headers/mebox/pieces/NotificationsCount";
import MessagesCount from "@library/headers/mebox/pieces/MessagesCount";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { IMeBoxProps } from "@library/headers/mebox/MeBox";
import Tabs from "@library/navigation/tabs/Tabs";
import { IInjectableUserState } from "@library/features/users/userTypes";
import UserDropDownContents from "@library/headers/mebox/pieces/UserDropDownContents";
import classNames from "classnames";
import LazyModal from "@library/modal/LazyModal";
import ModalSizes from "@library/modal/ModalSizes";
import { titleBarClasses } from "@library/headers/titleBarStyles";
import { MeBoxIcon } from "@library/headers/mebox/pieces/MeBoxIcon";
import { TouchScrollable } from "react-scrolllock";
import { UserIcon, UserIconTypes } from "@library/icons/titleBar";

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

        const titleText = t("Me");
        const altText = accessibleLabel(t(`User: "%s"`), [t(`Me`)]);

        return (
            <div className={classNames("compactMeBox", this.props.className, classes.root)}>
                <Button
                    title={t("My Account")}
                    className={classNames(classes.openButton, titleBarVars.centeredButton, titleBarVars.button)}
                    onClick={this.open}
                    buttonRef={this.buttonRef}
                    buttonType={ButtonTypes.CUSTOM}
                >
                    <UserPhoto userInfo={userInfo} className="meBox-user" size={UserPhotoSize.SMALL} />
                </Button>
                <LazyModal
                    isVisible={this.state.open}
                    size={ModalSizes.MODAL_AS_SIDE_PANEL_RIGHT}
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
                            {
                                buttonContent: <MessagesCount open={false} compact={true} />,
                                openButtonContent: <MessagesCount open={true} compact={true} />,
                                panelContent: <MessagesContents className={panelBodyClass} />,
                            },
                        ]}
                    />
                </LazyModal>
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
