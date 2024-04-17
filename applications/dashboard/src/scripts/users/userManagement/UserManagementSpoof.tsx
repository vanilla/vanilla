/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState } from "react";
import { ButtonTypes } from "@library/forms/buttonTypes";
import userManagementClasses from "@dashboard/users/userManagement/UserManagement.classes";
import { IUser } from "@library/@types/api/users";
import { getMeta, siteUrl, t } from "@library/utility/appUtils";
import { Icon } from "@vanilla/icons";
import { useUserManagement } from "@dashboard/users/userManagement/UserManagementContext";
import ModalConfirm from "@library/modal/ModalConfirm";
import Button from "@library/forms/Button";
import Translate from "@library/content/Translate";

interface IProps {
    userID: IUser["userID"];
    name: IUser["name"];
    isSysAdmin: IUser["isSysAdmin"];
}
export default function UserManagementSpoof(props: IProps) {
    const { currentUserID } = useUserManagement();
    const classes = userManagementClasses();
    const transientKey = getMeta("TransientKey");
    const isViewingSelf = currentUserID == props.userID;
    const showSpoofButton = !isViewingSelf && !props.isSysAdmin;
    const [visible, setVisible] = useState<boolean>(false);

    return (
        <>
            {showSpoofButton && (
                <Button
                    buttonType={ButtonTypes.ICON}
                    className={classes.spoofIcon}
                    title={t("spoof")}
                    onClick={() => {
                        setVisible(!visible);
                    }}
                >
                    <Icon icon="user-spoof" />
                </Button>
            )}
            <ModalConfirm
                isVisible={visible}
                title={t("Confirm")}
                onCancel={() => {
                    setVisible(false);
                }}
                onConfirm={() => {
                    setVisible(false);
                    const rootUrl = siteUrl("");
                    const newUrl = `${rootUrl}/user/autospoof/${props.userID}/${transientKey}`;
                    window.location.href = newUrl;
                }}
                fullWidthContent
            >
                <Translate
                    source="This action will log you in as <0/>."
                    c0={<span style={{ fontWeight: 700 }}>{props.name}</span>}
                />
                <br />
                {t("Are you sure you want to do that?")}
            </ModalConfirm>
        </>
    );
}
