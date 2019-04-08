/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import apiv2 from "@library/apiv2";
import Permission from "@library/features/users/Permission";
import UserActions from "@library/features/users/UserActions";
import DropDown from "@library/flyouts/DropDown";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import DropDownItemLinkWithCount from "@library/flyouts/items/DropDownItemLinkWithCount";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";
import DropDownSection from "@library/flyouts/items/DropDownSection";
import DropDownUserCard from "@library/flyouts/items/DropDownUserCard";
import { userDropDownClasses } from "@library/headers/mebox/pieces/userDropDownStyles";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import { vanillaHeaderClasses } from "@library/headers/vanillaHeaderStyles";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import { frameBodyClasses } from "@library/layout/frame/frameStyles";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { t } from "@library/utility/appUtils";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import classNames from "classnames";
import React, { useEffect, useMemo, useState } from "react";
import { connect } from "react-redux";
import UserDropDownContents from "@library/headers/mebox/pieces/UserDropDownContents";

/**
 * Implements User Drop down for header
 */
function UserDropDown(props: IProps) {
    const ID = useMemo(() => uniqueIDFromPrefix("userDropDown"), []);
    const [isOpen, setOpen] = useState(false);

    useEffect(() => {
        if (isOpen) {
            props.checkCountData();
        }
    }, [isOpen, props.checkCountData]);

    const { userInfo } = props;
    if (!userInfo) {
        return null;
    }

    const classes = userDropDownClasses();
    const classesHeader = vanillaHeaderClasses();

    return (
        <DropDown
            id={ID}
            name={t("My Account")}
            className={classNames("userDropDown", props.className)}
            buttonClassName={classNames("vanillaHeader-account", props.buttonClassName)}
            contentsClassName={classNames(
                "userDropDown-contents",
                props.contentsClassName,
                classes.contents,
                classesHeader.dropDownContents,
            )}
            renderLeft={true}
            buttonContents={
                <div className={classNames("meBox-buttonContent", props.toggleContentClassName)}>
                    <UserPhoto
                        userInfo={userInfo}
                        open={isOpen}
                        className="headerDropDown-user meBox-user"
                        size={UserPhotoSize.SMALL}
                    />
                </div>
            }
            onVisibilityChange={setOpen}
        >
            <UserDropDownContents />
        </DropDown>
    );
}

interface IOwnProps {
    className?: string;
    countsClass?: string;
    buttonClassName?: string;
    contentsClassName?: string;
    toggleContentClassName?: string;
}

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
