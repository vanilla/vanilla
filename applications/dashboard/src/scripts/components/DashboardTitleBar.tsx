/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */
import * as React from "react";
import { DashboardTitleBarClasses } from "@dashboard/components/DashboardTitleBar.classes";
import SmartLink from "@library/routing/links/SmartLink";
import { useUsersState } from "@library/features/users/userModel";
import DashboardMeBox from "@library/headers/mebox/pieces/DashboardMeBox";
import { Icon } from "@vanilla/icons";
import { TitleBarDevices, useTitleBarDevice } from "@library/layout/TitleBarContext";
import Hamburger from "@library/flyouts/Hamburger";
import { titleBarClasses } from "@library/headers/titleBarStyles";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import { t } from "@vanilla/i18n";
import Container from "@library/layout/components/Container";
import classNames from "classnames";
import { IDashboardSection } from "@dashboard/DashboardSectionType";
import { INavigationVariableItem } from "@library/headers/navigationVariables";

interface IExtraSectionContent {
    key: string;
    component: React.FunctionComponent;
}

DashboardTitleBar.extraSectionContent = [] as IExtraSectionContent[];
DashboardTitleBar.registerContent = function (registeredContent: IExtraSectionContent) {
    if (!DashboardTitleBar.extraSectionContent.find((content) => content.key === registeredContent.key)) {
        DashboardTitleBar.extraSectionContent.push(registeredContent);
    }
};

//Some props are for storybook, to force opening dropdowns, sidenavs and modals
interface IProps {
    forceMeBoxOpen?: boolean;
    forceMeBoxOpenAsModal?: boolean;
    isCompact?: boolean;
    forceHamburgerOpen?: boolean;
    hamburgerContent?: React.ReactNode;
    sections: IDashboardSection[];
    extraSectionContent?: IExtraSectionContent;
    activeSectionID?: string;
}

export default function DashboardTitleBar(props: IProps) {
    const { sections, activeSectionID } = props;
    const classes = DashboardTitleBarClasses();
    const dropdownClasses = dropDownClasses();
    const { currentUser } = useUsersState();
    const device = useTitleBarDevice();

    const isCompact = props.isCompact || device === TitleBarDevices.COMPACT;

    return (
        <header className={classes.container}>
            <Container fullGutter className={classes.flexContainer}>
                {isCompact && (
                    <>
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
                            navigationItems={sections as unknown as INavigationVariableItem[]}
                            forceHamburgerOpen={props.forceHamburgerOpen}
                        />
                        <div className={classes.logoContainer}>
                            <Icon icon={"vanilla-logo"} className={classes.logo} />
                        </div>
                    </>
                )}
                {!isCompact && (
                    <>
                        <div className={classes.brand}>
                            <div className={classes.logoContainer}>
                                <Icon icon={"vanilla-logo"} className={classes.logo} />
                            </div>
                            <SmartLink to={"/"} className={classes.backBtn}>
                                {t("Visit Site")}
                                <Icon icon="external-link" size="compact" />
                            </SmartLink>
                        </div>
                        <nav className={classes.nav}>
                            {sections &&
                                sections.map((section, key) => {
                                    return (
                                        <SmartLink
                                            key={key}
                                            className={classNames(
                                                classes.link,
                                                section.id === activeSectionID ? "active" : "",
                                            )}
                                            to={section.url}
                                        >
                                            <div className={classes.linkLabel}>{section.name}</div>
                                        </SmartLink>
                                    );
                                })}
                            {DashboardTitleBar.extraSectionContent.map((content, key) => (
                                <content.component key={key} />
                            ))}
                        </nav>
                    </>
                )}
                <DashboardMeBox
                    currentUser={currentUser}
                    forceOpen={props.forceMeBoxOpen}
                    forceOpenAsModal={props.forceMeBoxOpenAsModal}
                    isCompact={isCompact}
                />
            </Container>
        </header>
    );
}
