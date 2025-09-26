/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { cx } from "@emotion/css";
import { useRegisterLink, useSignInLink } from "@library/contexts/EntryLinkContext";
import { useCurrentUser, useCurrentUserSignedIn } from "@library/features/users/userHooks";
import MeBox from "@library/headers/mebox/MeBox";
import { TitleBarNavItem } from "@library/headers/mebox/pieces/TitleBarNavItem";
import { titleBarVariables } from "@library/headers/TitleBar.variables";
import { titleBarClasses } from "@library/headers/TitleBar.classes";
import { t } from "@vanilla/i18n";

interface IProps {}

export function MeBoxDesktop(props: IProps) {
    const vars = titleBarVariables.useAsHook();
    const classes = titleBarClasses.useAsHook();
    const currentUser = useCurrentUser();
    const currentUserIsSignedIn = useCurrentUserSignedIn();
    const isGuest = !currentUserIsSignedIn;
    const registerLink = useRegisterLink();
    const signinLink = useSignInLink();
    const guestVars = vars.guest;
    const meboxVars = vars.meBox;

    if (isGuest) {
        return (
            <div className={cx("titleBar-nav titleBar-guestNav", classes.nav)}>
                <TitleBarNavItem
                    buttonType={guestVars.signInButtonType}
                    linkClassName={cx(classes.signIn, classes.guestButton)}
                    to={signinLink}
                >
                    {t("Sign In")}
                </TitleBarNavItem>
                {registerLink && (
                    <TitleBarNavItem
                        buttonType={guestVars.registerButtonType}
                        linkClassName={cx(classes.register, classes.guestButton)}
                        to={registerLink}
                    >
                        {t("Register")}
                    </TitleBarNavItem>
                )}
            </div>
        );
    } else {
        return (
            <span className={cx(classes.meBox)}>
                <MeBox
                    currentUser={currentUser}
                    className={cx("titleBar-meBox")}
                    buttonClassName={classes.button}
                    contentClassName={cx("titleBar-dropDownContents", classes.dropDownContents)}
                    withSeparator={meboxVars.withSeparator}
                    withLabel={meboxVars.withLabel}
                />
            </span>
        );
    }
}
