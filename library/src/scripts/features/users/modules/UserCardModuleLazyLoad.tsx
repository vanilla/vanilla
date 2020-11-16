/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect, useState } from "react";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Button from "@library/forms/Button";
import { userCardClasses } from "@library/features/users/ui/popupUserCardStyles";
import classNames from "classnames";
import { IUserCardModule, UserCardModule } from "@library/features/users/modules/UserCardModule";
import { t } from "@vanilla/i18n";
import { useUniqueID } from "@library/utility/idUtils";
import { useLastValue } from "@vanilla/react-utils";
import { hasUserViewPermission } from "@library/features/users/modules/hasUserViewPermission";

export interface IProps extends Omit<IUserCardModule, "fallbackButton" | "ready" | "contentID" | "handleID"> {
    hasImage?: boolean;
    userURL: string; // redirect here if there's an error loading the user data
}

export function UserCardModuleLazyLoad(props: IProps) {
    const { buttonContent, hasImage = false } = props;
    const [ready, setReady] = useState(false);
    const lastReady = useLastValue(ready);

    const ID = useUniqueID("userCard");
    const handleID = ID + "-handle";
    const contentID = ID + "-contents";
    const toggleRef: React.RefObject<HTMLButtonElement> = React.createRef();

    useEffect(() => {
        if (!lastReady && ready) {
            toggleRef.current?.focus();
        }
    }, [ready, toggleRef, lastReady]);

    const fallbackButton = (
        <Button
            buttonRef={toggleRef}
            id={handleID}
            onClick={() => {
                setReady(true);
            }}
            baseClass={props.buttonType ?? (hasImage ? ButtonTypes.CUSTOM : ButtonTypes.TEXT)}
            className={classNames(userCardClasses().link, props.buttonClass, {
                isLoading: ready,
                [userCardClasses().avatar]: hasImage,
            })}
            ariaLabel={t(`Load user information.`)}
            aria-controls={contentID}
            aria-haspopup={true}
            aria-expanded={false} // always false, because when it loads, it's replaced
        >
            {buttonContent}
        </Button>
    );

    if (!hasUserViewPermission()) {
        return fallbackButton;
    }

    if (!ready) {
        return <>{fallbackButton}</>; // rendered while we wait for the user data
    } else {
        return (
            <UserCardModule
                {...props}
                fallbackButton={fallbackButton}
                visible={true}
                handleID={handleID}
                contentID={contentID}
                userURL={props.userURL}
            />
        );
    }
}
