/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { uniqueIDFromPrefix } from "@library/componentIDs";
import DropDown from "@library/components/dropdown/DropDown";
import { t } from "@library/application";
import FrameBody from "@library/components/frame/FrameBody";
import Frame from "@library/components/frame/Frame";
import { IUserFragment } from "@library/@types/api/users";
import { UserPhoto, UserPhotoSize } from "@library/components/mebox/pieces/UserPhoto";
import { logError } from "@library/utility";
import { connect } from "react-redux";
import UsersModel, { IInjectableUserState } from "@library/users/UsersModel";
import get from "lodash/get";
import LinkList from "@library/components/linkLists/LinkList";

export interface ILinkSection {}

export interface IUserDropDownProps extends IInjectableUserState {
    className?: string;
    linkSections: ILinkSection[];
}

interface IState {
    open: boolean;
}

/**
 * Implements User Drop down for header
 */
export class UserDropDown extends React.Component<IUserDropDownProps, IState> {
    private id = uniqueIDFromPrefix("userDropDown");

    public constructor(props) {
        super(props);
        this.state = {
            open: false,
        };
    }

    public render() {
        const userInfo: IUserFragment = get(this.props, "currentUser.data", {
            name: null,
            userID: null,
            photoUrl: null,
        });

        return (
            <DropDown
                id={this.id}
                name={t("My Account")}
                buttonClassName={"vanillaHeader-account meBox-button"}
                renderLeft={true}
                buttonContents={
                    <div className="meBox-buttonContent">
                        <UserPhoto
                            userInfo={userInfo}
                            open={this.state.open}
                            className="headerDropDown-user meBox-user"
                            size={UserPhotoSize.SMALL}
                        />
                    </div>
                }
                onVisibilityChange={this.setOpen}
            >
                <Frame className="userDropDown">
                    <FrameBody className="isSelfPadded">
                        <div className="userDropDown-userCard">
                            <UserPhoto
                                className="userDropDown-userCardPhoto"
                                userInfo={this.props.currentUser.data!}
                                size={UserPhotoSize.LARGE}
                            />
                            <div className="userDropDown-userCardName">{this.props.currentUser.data!.name}</div>
                        </div>
                        <LinkList data={} />
                    </FrameBody>
                </Frame>
            </DropDown>
        );
    }

    private setOpen = open => {
        this.setState({
            open,
        });
    };

    /**
     * @inheritdoc
     */
    public componentDidCatch(error, info) {
        logError(error, info);
    }
}

const withRedux = connect(UsersModel.mapStateToProps);
export default withRedux(UserDropDown);
