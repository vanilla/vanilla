/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { uniqueIDFromPrefix } from "@library/componentIDs";
import { t } from "@library/application";
import { IUserFragment } from "@library/@types/api/users";
import { UserPhoto, UserPhotoSize } from "@library/components/mebox/pieces/UserPhoto";
import { connect } from "react-redux";
import UsersModel, { IInjectableUserState } from "@library/users/UsersModel";
import get from "lodash/get";
import classNames from "classnames";
import Tabs from "@library/components/tabs/Tabs";
import Modal from "@library/components/modal/Modal";
import ModalSizes from "@library/components/modal/ModalSizes";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import CloseButton from "@library/components/CloseButton";
import { user } from "@library/components/icons/header";
import UserDropdownContents from "@library/components/mebox/pieces/UserDropdownContents";
import NotificationsToggle from "@library/components/mebox/pieces/NotificationsToggle";
import MessagesToggle from "@library/components/mebox/pieces/MessagesToggle";
import { IMeBoxProps } from "@library/components/mebox/MeBox";
import NotificationsContents from "@library/components/mebox/pieces/NotificationsContents";
import MessagesContents from "@library/components/mebox/pieces/MessagesContents";

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
export class CompactMeBox extends React.Component<IUserDropDownProps, IState> {
    private id = uniqueIDFromPrefix("compactMeBox");
    private buttonRef: React.RefObject<HTMLButtonElement> = React.createRef();

    public state = {
        open: false,
    };

    public render() {
        const userInfo: IUserFragment = get(this.props, "currentUser.data", {
            name: null,
            userID: null,
            photoUrl: null,
        });

        const { counts } = this.props;
        const countClass = this.props.countsClass;
        const buttonClass = this.props.buttonClass;

        return (
            <div className={classNames("compactMeBox", this.props.className)}>
                <Button
                    title={t("My Account")}
                    className={classNames("compactMeBox-openButton", this.props.buttonClass)}
                    onClick={this.open}
                    buttonRef={this.buttonRef}
                    baseClass={ButtonBaseClass.CUSTOM}
                >
                    <UserPhoto
                        userInfo={userInfo}
                        open={this.state.open}
                        className="meBox-user"
                        size={UserPhotoSize.SMALL}
                        forceIcon={true}
                    />
                </Button>
                {this.state.open && (
                    <Modal
                        size={ModalSizes.PSEUDO_DROP_DOWN}
                        label={t("Article Revisions")}
                        elementToFocusOnExit={this.buttonRef.current!}
                        className="compactMeBox-modal"
                        exitHandler={this.close}
                    >
                        <div className="compactMeBox-contents">
                            <CloseButton onClick={this.close} className="compactMeBox-closeModal" />
                            <Tabs
                                label={t("My Account Tab")}
                                tabListClass="compactMeBox-tabList"
                                buttonClass={classNames(buttonClass, "compactMeBox-tabButton")}
                                tabs={[
                                    {
                                        buttonContent: (
                                            <div className="compactSearch-tabButtonContent">
                                                {user(false, "userPhoto-photo")}
                                            </div>
                                        ),
                                        openButtonContent: (
                                            <div className="compactSearch-tabButtonContent">
                                                {user(true, "userPhoto-photo")}
                                            </div>
                                        ),
                                        panelContent: <UserDropdownContents counts={counts} />,
                                    },
                                    {
                                        buttonContent: (
                                            <NotificationsToggle
                                                open={false}
                                                className="compactSearch-tabButtonContent"
                                                count={this.props.notificationsProps.count}
                                                countClass={this.props.notificationsProps.countClass}
                                            />
                                        ),
                                        openButtonContent: (
                                            <NotificationsToggle
                                                open={true}
                                                className="compactSearch-tabButtonContent"
                                                count={this.props.notificationsProps.count}
                                                countClass={this.props.notificationsProps.countClass}
                                            />
                                        ),
                                        panelContent: (
                                            <NotificationsContents
                                                {...this.props.notificationsProps}
                                                countClass={countClass}
                                            />
                                        ),
                                    },
                                    {
                                        buttonContent: (
                                            <MessagesToggle
                                                open={false}
                                                className="compactSearch-tabButtonContent"
                                                count={this.props.messagesProps.count}
                                                countClass={this.props.messagesProps.countClass}
                                            />
                                        ),
                                        openButtonContent: (
                                            <MessagesToggle
                                                open={true}
                                                className="compactSearch-tabButtonContent"
                                                count={this.props.messagesProps.count}
                                                countClass={this.props.messagesProps.countClass}
                                            />
                                        ),
                                        panelContent: (
                                            <MessagesContents
                                                count={this.props.messagesProps.count}
                                                countClass={this.props.countsClass}
                                                data={this.props.messagesProps.data}
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

const withRedux = connect(UsersModel.mapStateToProps);
export default withRedux(CompactMeBox);
