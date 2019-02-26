/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IMe } from "@library/@types/api/users";
import { t } from "@library/application";
import CloseButton from "@library/components/CloseButton";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import { IMeBoxProps } from "@library/components/mebox/MeBox";
import MessagesContents from "@library/components/mebox/pieces/MessagesContents";
import MessagesCount from "@library/components/mebox/pieces/MessagesCount";
import NotificationsContents from "@library/components/mebox/pieces/NotificationsContents";
import NotificationsCounter from "@library/components/mebox/pieces/NotificationsCounter";
import UserDropdownContents from "@library/components/mebox/pieces/UserDropdownContents";
import { UserPhoto, UserPhotoSize } from "@library/components/mebox/pieces/UserPhoto";
import Modal from "@library/components/modal/Modal";
import ModalSizes from "@library/components/modal/ModalSizes";
import Tabs from "@library/components/tabs/Tabs";
import { IInjectableUserState } from "@library/users/UsersModel";
import classNames from "classnames";
import get from "lodash/get";
import * as React from "react";
import { compactMeBoxClasses } from "@library/styles/compactMeBoxStyles";

export interface IUserDropDownProps extends IInjectableUserState, IMeBoxProps {
    buttonClass?: string;
    userPhotoClass?: string;
}

interface IState {
    open: boolean;
}

/**
 * Implements User Drop down for header
 */
export default class CompactMeBox extends React.Component<IUserDropDownProps, IState> {
    private buttonRef: React.RefObject<HTMLButtonElement> = React.createRef();

    public state = {
        open: false,
    };

    public render() {
        const userInfo: IMe = get(this.props, "currentUser.data", {
            name: null,
            userID: null,
            photoUrl: null,
            countUnreadNotifications: 0,
        });

        const classes = compactMeBoxClasses();
        const countClass = this.props.countsClass;
        const buttonClass = this.props.buttonClass;
        const panelContentClass = classNames("compactMeBox-panel", classes.panel);
        const panelBodyClass = classNames("compactMeBox-body", classes.body);

        return (
            <div className={classNames("compactMeBox", this.props.className, classes.root)}>
                <Button
                    title={t("My Account")}
                    className={classNames("compactMeBox-openButton", this.props.buttonClass, classes.openButton)}
                    onClick={this.open}
                    buttonRef={this.buttonRef}
                    baseClass={ButtonBaseClass.CUSTOM}
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
                        className="compactMeBox-modal"
                        exitHandler={this.close}
                    >
                        <div className={classNames("compactMeBox-contents", classes.contents)}>
                            <CloseButton
                                onClick={this.close}
                                className={classNames("compactMeBox-closeModal", classes.closeModal)}
                                baseClass={ButtonBaseClass.CUSTOM}
                            />
                            <Tabs
                                label={t("My Account Tab")}
                                className={classNames("compactMeBox-tabs", "inheritHeight")}
                                tabListClass={classNames("compactMeBox-tabList", classes.tabList)}
                                tabPanelsClass={classNames(
                                    "compactMeBox-tabPanels",
                                    "inheritHeight",
                                    classes.tabPanels,
                                )}
                                tabPanelClass={classNames("compactMeBox-tabPanel", "inheritHeight", classes.panel)}
                                buttonClass={classNames(buttonClass, "compactMeBox-tabButton", classes.tabButton)}
                                tabs={[
                                    {
                                        buttonContent: (
                                            <div
                                                className={classNames(
                                                    "compactMeBox-tabButtonContent",
                                                    classes.tabButtonContent,
                                                )}
                                            >
                                                <UserPhoto
                                                    userInfo={userInfo}
                                                    open={this.state.open}
                                                    className="compactMeBox-tabButtonContent"
                                                    size={UserPhotoSize.SMALL}
                                                />
                                            </div>
                                        ),
                                        openButtonContent: (
                                            <div
                                                className={classNames(
                                                    "compactMeBox-tabButtonContent",
                                                    classes.tabButtonContent,
                                                )}
                                            >
                                                <UserPhoto
                                                    userInfo={userInfo}
                                                    open={this.state.open}
                                                    className="compactMeBox-tabButtonContent"
                                                    size={UserPhotoSize.SMALL}
                                                />
                                            </div>
                                        ),
                                        panelContent: (
                                            <UserDropdownContents
                                                className={panelContentClass}
                                                panelBodyClass={panelBodyClass}
                                            />
                                        ),
                                    },
                                    {
                                        buttonContent: (
                                            <NotificationsCounter
                                                open={false}
                                                className={classNames(
                                                    "compactMeBox-tabButtonContent",
                                                    classes.tabButtonContent,
                                                )}
                                                countClass="vanillaHeader-count vanillaHeader-notificationsCount"
                                            />
                                        ),
                                        openButtonContent: (
                                            <NotificationsCounter
                                                open={true}
                                                className={classNames(
                                                    "compactMeBox-tabButtonContent",
                                                    classes.tabButtonContent,
                                                )}
                                                countClass="vanillaHeader-count vanillaHeader-notificationsCount"
                                            />
                                        ),
                                        panelContent: (
                                            <NotificationsContents
                                                countClass={countClass}
                                                className={panelContentClass}
                                                panelBodyClass={panelBodyClass}
                                                userSlug={userInfo.name}
                                            />
                                        ),
                                    },
                                    {
                                        buttonContent: (
                                            <MessagesCount
                                                open={false}
                                                className={classNames(
                                                    "compactMeBox-tabButtonContent",
                                                    classes.tabButtonContent,
                                                )}
                                                countClass={this.props.countClass}
                                            />
                                        ),
                                        openButtonContent: (
                                            <MessagesCount
                                                open={true}
                                                className={classNames(
                                                    "compactMeBox-tabButtonContent",
                                                    classes.tabButtonContent,
                                                )}
                                                countClass={this.props.countClass}
                                            />
                                        ),
                                        panelContent: (
                                            <MessagesContents
                                                countClass={this.props.countsClass}
                                                className={panelContentClass}
                                                panelBodyClass={panelBodyClass}
                                            />
                                        ),
                                    },
                                ]}
                            />
                        </div>
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
