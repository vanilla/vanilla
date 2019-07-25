/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import apiv2 from "@library/apiv2";
import UserActions from "@library/features/users/UserActions";
import DropDown from "@library/flyouts/DropDown";
import UserDropDownContents from "@library/headers/mebox/pieces/UserDropDownContents";
import { userDropDownClasses } from "@library/headers/mebox/pieces/userDropDownStyles";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import { titleBarClasses } from "@library/headers/titleBarStyles";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { t } from "@library/utility/appUtils";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import classNames from "classnames";
import React, { useEffect, useMemo, useState } from "react";
import { connect } from "react-redux";
import { MeBoxIcon } from "@library/headers/mebox/pieces/MeBoxIcon";

/**
 * Implements User Drop down for header
 */
function UserDropDown(props: IProps) {
    const ID = useMemo(() => uniqueIDFromPrefix("userDropDown"), []);
    const [isOpen, setOpen] = useState(false);
    const { checkCountData } = props;

    useEffect(() => {
        if (isOpen) {
            checkCountData();
        }
    }, [isOpen, checkCountData]);

    const { userInfo } = props;
    if (!userInfo) {
        return null;
    }

    const classes = userDropDownClasses();
    const classesHeader = titleBarClasses();

    return (
        <DropDown
            id={ID}
            name={t("My Account")}
            buttonClassName={classNames(classesHeader.button)}
            contentsClassName={classNames(classes.contents, classesHeader.dropDownContents)}
            renderLeft={true}
            buttonContents={
                <MeBoxIcon compact={false}>
                    <UserPhoto
                        userInfo={userInfo}
                        open={isOpen}
                        className="headerDropDown-user meBox-user"
                        size={UserPhotoSize.SMALL}
                    />
                </MeBoxIcon>
            }
            selfPadded={true}
            onVisibilityChange={setOpen}
        >
            <UserDropDownContents />
        </DropDown>
    );
}

interface IOwnProps {}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

function mapStateToProps(state: ICoreStoreState) {
    return {
        userInfo: state.users.current.data ? state.users.current.data : null,
        counts: state.users.countInformation.counts,
    };
}

function mapDispatchToProps(dispatch: any) {
    const userActions = new UserActions(dispatch, apiv2);
    const { checkCountData } = userActions;
    return {
        checkCountData,
    };
}

export default connect(
    mapStateToProps,
    mapDispatchToProps,
)(UserDropDown);
