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
import { DropDownUserCard } from "@library/components/dropdown/items/DropDownUserCard";
import DropDownItemSeparator from "@library/components/dropdown/items/DropDownItemSeparator";
import DropDownItemLink from "@library/components/dropdown/items/DropDownItemLink";
import DropDownSection from "@library/components/dropdown/items/DropDownSection";
import DropDownItemLinkWithCount from "@library/components/dropdown/items/DropDownItemLinkWithCount";
import Permission from "@library/users/Permission";
import classNames from "classnames";

export interface IUserDropDownProps extends IInjectableUserState {
    className?: string;
    counts: any;
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

        const counts = this.props.counts;

        return (
            <DropDown
                id={this.id}
                name={t("My Account")}
                className={classNames("userDropDown", this.props.className)}
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
                        <DropDownUserCard currentUser={this.props.currentUser!} className="userDropDown-userCard" />
                        <DropDownItemSeparator />
                        <DropDownItemLink to="/profile/edit" name={t("Edit Profile")} />
                        <DropDownSection title={t("Discussions")}>
                            <DropDownItemLinkWithCount
                                to="/discussions/bookmarked"
                                name={t("Bookmarks")}
                                count={counts.bookmarkCount}
                            />
                            <DropDownItemLinkWithCount to="/drafts" name={t("Drafts")} count={counts.draftsCount} />
                            <DropDownItemLink to="/discussions/mine" name={t("My Discussions")} />
                            <DropDownItemLink to="/activity" name={t("Participated")} />
                        </DropDownSection>
                        <Permission permission={["community.moderate"]}>
                            <DropDownSection title={t("Moderation")}>
                                <DropDownItemLinkWithCount
                                    to="/dashboard/user/applicants"
                                    name={t("Applicants")}
                                    count={counts.applicantsCount}
                                />
                                <DropDownItemLinkWithCount
                                    to="/dashboard/log/spam"
                                    name={t("Spam Queue")}
                                    count={counts.spamQueueCount}
                                />
                                <DropDownItemLinkWithCount
                                    to="/dashboard/log/moderation"
                                    name={t("Moderation Queue")}
                                    count={counts.moderationQueueCount}
                                />
                                <DropDownItemLinkWithCount
                                    to="/badge/requests"
                                    name={t("Badge Requests")}
                                    count={counts.badgeRequestCount}
                                />
                            </DropDownSection>
                        </Permission>
                        <DropDownItemSeparator />
                        <DropDownItemLink to="/dashboard/settings" name={t("Dashboard")} />
                        <DropDownItemLink to="/entry/signout" name={t("Sign Out")} />
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
