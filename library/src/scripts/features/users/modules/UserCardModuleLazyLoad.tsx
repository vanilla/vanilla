/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Button from "@library/forms/Button";
import { userCardClasses } from "@library/features/users/ui/popupUserCardStyles";
import classNames from "classnames";
import { IUserCardModule, UserCardModule } from "@library/features/users/modules/UserCardModule";
import { t } from "@vanilla/i18n";

export interface IProps extends Omit<IUserCardModule, "fallbackButton" | "ready"> {}

export function UserCardModuleLazyLoad(props: IProps) {
    const { buttonContent } = props;
    const [ready, setReady] = useState(false);

    const fallbackButton = (
        <Button
            onClick={() => {
                setReady(true);
            }}
            baseClass={props.buttonType ?? ButtonTypes.TEXT}
            className={classNames(userCardClasses().link, props.buttonClass, {
                isLoading: ready,
            })}
            ariaLabel={t(`Load user information.`)}
        >
            {buttonContent}
        </Button>
    );

    if (!ready) {
        return <>{fallbackButton}</>;
    } else {
        return <UserCardModule {...props} fallbackButton={fallbackButton} visible={true} />;
    }
}
