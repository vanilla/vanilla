/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */
import * as React from "react";
import classNames from "classnames";
import { DashboardTitleBarClasses } from "@library/headers/DashboardTitleBar.classes";
import { VanillaWhiteIcon } from "@vanillaanalytics/components/VanillaWhiteIcon";
import SmartLink from "@library/routing/links/SmartLink";
import { useUsersState } from "@library/features/users/userModel";
import DashboardMeBox from "@library/headers/mebox/pieces/DashboardMeBox";
import { Icon } from "@vanilla/icons";
import { TitleBarDevices, useTitleBarDevice } from "@library/layout/TitleBarContext";
import { useCollisionDetector } from "@vanilla/react-utils";
import Hamburger from "@library/flyouts/Hamburger";
import { titleBarClasses } from "@library/headers/titleBarStyles";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import { t } from "@vanilla/i18n";
import Container from "@library/layout/components/Container";

//Some props are for storybook, to force opening dropdowns, sidenavs and modals
interface IProps {
    forceMeBoxOpen?: boolean;
    forceMeBoxOpenAsModal?: boolean;
    isCompact?: boolean;
    forceHamburgerOpen?: boolean;
    hamburgerContent?: React.ReactNode;
}

export default function DashboardTitleBar(props: IProps) {
    const classes = DashboardTitleBarClasses();
    const dropdownClasses = dropDownClasses();
    const { currentUser } = useUsersState();
    const device = useTitleBarDevice();

    const isCompact = props.isCompact || device === TitleBarDevices.COMPACT;

    const defaultDashboardSections = [
        {
            children: [],
            id: "builtin-dashboard-home",
            name: "Dashboard",
            url: "/dashboard/settings/home",
        },
        {
            children: [],
            id: "builtin-dashboard-moderation",
            name: "Moderation",
            url: "/dashboard/user",
        },
        {
            children: [],
            id: "builtin-dashboard-settings",
            name: "Settings",
            url: "/dashboard/settings/branding",
        },
        {
            children: [],
            id: "builtin-dashboard-analytics",
            name: "Analytics",
            url: "/analytics/v2/dashboards",
        },
    ];

    return (
        <header className={classes.container}>
            <Container fullGutter className={classes.flexContainer}>
                {isCompact && (
                    <Hamburger
                        className={titleBarClasses().hamburger}
                        extraNavTop={props.hamburgerContent}
                        extraNavBottom={
                            <>
                                <hr className={dropdownClasses.separator} />
                                <SmartLink className={dropdownClasses.action} to={"/"}>
                                    <div className={classes.iconWrapper}>
                                        <Icon icon="meta-external" />
                                    </div>
                                    {t("Visit Site")}
                                </SmartLink>
                            </>
                        }
                        showCloseIcon={false}
                        navigationItems={defaultDashboardSections}
                        forceHamburgerOpen={props.forceHamburgerOpen}
                    />
                )}
                {!isCompact && (
                    <>
                        <div className={classes.brand}>
                            <div className={classes.logo}>
                                <VanillaWhiteIcon />
                            </div>
                            <a href="/" className={classes.backBtn}>
                                Visit Site
                                <Icon icon="external-link" size="compact" />
                            </a>
                        </div>
                        <nav className={classes.nav}>
                            <SmartLink className={classes.link} to="/dashboard/settings/home">
                                <div className={classes.linkLabel}>Dashboard</div>
                            </SmartLink>
                            <SmartLink className={classes.link} to="/dashboard/user">
                                <div className={classes.linkLabel}>Moderation</div>
                            </SmartLink>
                            {/** TODO: This needs to be a dynamic link to the last visited settings page. */}
                            <SmartLink className={classes.link} to="/dashboard/settings/branding">
                                <div className={classes.linkLabel}>Settings</div>
                            </SmartLink>
                            <SmartLink className={classNames(classes.link, "active")} to="/analytics/v2/dashboards">
                                <div className={classes.linkLabel}>Analytics</div>
                            </SmartLink>
                        </nav>
                    </>
                )}
                <DashboardMeBox
                    currentUser={currentUser}
                    forceOpen={props.forceMeBoxOpen}
                    forceOpenAsModal={props.forceMeBoxOpenAsModal}
                />
            </Container>
        </header>
    );
}
