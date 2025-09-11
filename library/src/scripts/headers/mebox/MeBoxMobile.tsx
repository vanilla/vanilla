/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { cx } from "@emotion/css";
import { useSignInLink } from "@library/contexts/EntryLinkContext";
import { useCurrentUser, useCurrentUserSignedIn } from "@library/features/users/userHooks";
import CompactMeBox from "@library/headers/mebox/pieces/CompactMeBox";
import { titleBarClasses } from "@library/headers/TitleBar.classes";
import SmartLink from "@library/routing/links/SmartLink";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";

export function MeBoxMobile() {
    const currentUser = useCurrentUser();
    const currentUserIsSignedIn = useCurrentUserSignedIn();
    const isGuest = !currentUserIsSignedIn;
    const classes = titleBarClasses.useAsHook();
    const signinLink = useSignInLink();
    if (isGuest) {
        return (
            <SmartLink
                className={cx(classes.centeredButton, classes.button, classes.signInIconOffset)}
                title={t("Sign In")}
                to={signinLink}
            >
                <Icon icon="me-sign-in" />
            </SmartLink>
        );
    } else {
        return <CompactMeBox className={cx("titleBar-button", classes.button)} currentUser={currentUser} />;
    }
}
