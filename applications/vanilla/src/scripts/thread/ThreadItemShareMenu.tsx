/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { t } from "@library/utility/appUtils";
import ThreadItemActionsClasses from "@vanilla/addon-vanilla/thread/ThreadItemActions.classes";
import { Icon } from "@vanilla/icons";
import React from "react";
import { useThreadItemContext } from "@vanilla/addon-vanilla/thread/ThreadItemContext";
import { useToast } from "@library/features/toaster/ToastContext";
import Button from "@library/forms/Button";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";

export default function ThreadItemShareMenu() {
    const classes = ThreadItemActionsClasses();
    const toast = useToast();
    const { recordType, handleCopyUrl, handleNativeShare, emailUrl, shareInMessageUrl } = useThreadItemContext();

    const buttonTitle = t("Share");

    const buttonContents = (
        <>
            <Icon icon="data-share" size="compact" />
            {buttonTitle}
        </>
    );

    const copyButtonText = recordType === "comment" ? t("Copy Link to Comment") : t("Copy Link");

    return handleNativeShare !== undefined ? (
        <Button
            buttonType={ButtonTypes.TEXT}
            title={buttonTitle}
            className={classes.actionButton}
            onClick={async () => {
                await handleNativeShare();
            }}
        >
            {buttonContents}
        </Button>
    ) : (
        <DropDown
            name={buttonTitle}
            flyoutType={FlyoutType.LIST}
            buttonClassName={classes.actionButton}
            buttonType={ButtonTypes.TEXT}
            buttonContents={buttonContents}
        >
            <DropDownItemButton
                onClick={async () => {
                    await handleCopyUrl();
                    toast.addToast({
                        body: <>{t("Link copied to clipboard.")}</>,
                        autoDismiss: true,
                    });
                }}
            >
                {copyButtonText}
            </DropDownItemButton>

            <DropDownItemLink to={emailUrl}>{t("Email Link")}</DropDownItemLink>

            {!!shareInMessageUrl && <DropDownItemLink to={shareInMessageUrl}>{t("Share In Message")}</DropDownItemLink>}
        </DropDown>
    );
}
