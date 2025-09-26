/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */
import { DashboardTitleBarClasses } from "@dashboard/components/DashboardTitleBar.classes";
import type { DashboardMenusApi } from "@dashboard/DashboardMenusApi";
import { useCurrentUser } from "@library/features/users/userHooks";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import Hamburger from "@library/flyouts/Hamburger";
import MeBox from "@library/headers/mebox/MeBox";
import { MeBoxMobile } from "@library/headers/mebox/MeBoxMobile";
import { INavigationVariableItem } from "@library/headers/navigationVariables";
import { titleBarClasses } from "@library/headers/TitleBar.classes";
import { TitleBarParamContextProvider } from "@library/headers/TitleBar.ParamContext";
import Container from "@library/layout/components/Container";
import { TitleBarDevices, useTitleBarDevice } from "@library/layout/TitleBarContext";
import SmartLink from "@library/routing/links/SmartLink";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { StackingContextProvider } from "@vanilla/react-utils";
import classNames from "classnames";
import * as React from "react";

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
    isCompact?: boolean;
    forceHamburgerOpen?: boolean;
    hamburgerContent?: React.ReactNode;
    sections: DashboardMenusApi.Section[];
    extraSectionContent?: IExtraSectionContent;
    activeSectionID?: string;
}

export default function DashboardTitleBar(props: IProps) {
    const { sections, activeSectionID } = props;
    const classes = DashboardTitleBarClasses();
    const dropdownClasses = dropDownClasses();
    const currentUser = useCurrentUser();
    const device = useTitleBarDevice();

    const isCompact = props.isCompact || device === TitleBarDevices.COMPACT;

    return (
        <header className={classes.container}>
            <Container fullGutter className={classes.flexContainer}>
                <TitleBarParamContextProvider>
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
                                                <Icon icon="meta-external-compact" />
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
                                    <Icon icon="meta-external" size="compact" />
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

                    <StackingContextProvider>
                        {isCompact ? <MeBoxMobile /> : <MeBox currentUser={currentUser} />}
                    </StackingContextProvider>
                </TitleBarParamContextProvider>
            </Container>
        </header>
    );
}
