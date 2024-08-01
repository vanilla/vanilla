/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import AdminEditTitleBar from "@dashboard/components/AdminEditTitleBar";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { t } from "@vanilla/i18n";
import React, { ComponentProps } from "react";

interface IProps extends ComponentProps<typeof AdminEditTitleBar> {
    isSaving?: boolean;
}

export function LayoutEditorTitleBar(props: IProps) {
    const { isSaving, actions, disableSave, ...rest } = props;
    return (
        <AdminEditTitleBar
            {...rest}
            // We're making our own save button.
            noSaveButton
            actions={
                <>
                    {actions}
                    <Button
                        buttonType={ButtonTypes.TEXT_PRIMARY}
                        onClick={props.onSave}
                        disabled={isSaving || disableSave}
                    >
                        {isSaving ? <ButtonLoader /> : t("Save")}
                    </Button>
                </>
            }
        />
    );
}
