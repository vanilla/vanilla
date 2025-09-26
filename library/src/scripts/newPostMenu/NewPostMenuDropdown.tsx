/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { useLayoutEffect, useRef } from "react";
import { NewPostMenuIcon } from "@library/icons/common";
import LinkAsButton from "@library/routing/LinkAsButton";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { newPostMenuDropdownClasses } from "@library/newPostMenu/NewPostMenuDropdown.styles";
import { t } from "@vanilla/i18n";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import { INewPostMenuProps } from "@library/newPostMenu/NewPostMenu";
import { cx } from "@emotion/css";
import { Menu, MenuButton, MenuLink, MenuList, useMenuButtonContext } from "@reach/menu-button";
import { getClassForButtonType } from "@library/forms/Button.getClassForButtonType";
import { useLinkContext } from "@library/routing/links/LinkContextProvider";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import Container from "@library/layout/components/Container";
import { StackingContextProvider, useLastValue, useMeasure, useStackingContext } from "@vanilla/react-utils";
import { pointerEventsClass } from "@library/styles/styleHelpersFeedback";
import { buttonClasses } from "@library/forms/Button.styles";

interface MenuButtonImplProps extends INewPostMenuProps, React.ComponentPropsWithoutRef<typeof MenuButton> {}

export const MenuButtonImpl = React.forwardRef(function MenuButtonImpl(
    props: MenuButtonImplProps,
    ref: React.Ref<HTMLButtonElement>,
) {
    const { isExpanded } = useMenuButtonContext();
    const wasExpanded = useLastValue(isExpanded);
    const classes = newPostMenuDropdownClasses.useAsHook(props.containerOptions);
    const classesButton = buttonClasses.useAsHook();

    // Kludge for interaction with old flyout system.
    useLayoutEffect(() => {
        if (!wasExpanded && isExpanded && window.closeAllFlyouts) {
            window.closeAllFlyouts();
        }
    }, [isExpanded, wasExpanded]);

    return (
        <MenuButton ref={ref} className={cx(classesButton.primary, classes.button(props.borderRadius))}>
            <div className={classes.buttonContents}>
                <div
                    className={classes.buttonIcon}
                    style={{
                        transform: isExpanded ? "rotate(-135deg)" : "rotate(0deg)",
                        transition: "transform .25s",
                    }}
                >
                    {props.children}
                </div>
                <div className={classes.buttonLabel}>{t("New Post")}</div>
            </div>
        </MenuButton>
    );
});

export default function NewPostMenuDropDown(props: INewPostMenuProps) {
    const { title, items, borderRadius, containerOptions } = props;

    const classes = newPostMenuDropdownClasses.useAsHook(containerOptions);
    const containerRef = useRef<HTMLDivElement>(null);
    const menuButtonRef = useRef<HTMLButtonElement>(null);
    const context = useLinkContext();

    const { height: containerHeight } = useMeasure(containerRef);
    const isInCustomLayout = Boolean(props.titleType);

    // handle the edge cases when dropdowns container does not have enough space to open the dropdown without scroller
    useLayoutEffect(() => {
        if (containerRef.current && isInCustomLayout && (containerHeight > 0 || props.forceDesktopOnly)) {
            // it's in modal preview, provide enough space for the dropdown
            const widgetSettingsPreview =
                props.disableDropdownItemsClick &&
                (containerRef.current.closest(".widgetSettingsPreview") as HTMLElement);
            if (widgetSettingsPreview) {
                widgetSettingsPreview.style.minHeight = `${300}px`;
            }

            // in the FE in custom layout, adjust parent min height
            const panelArea = containerRef.current.closest(".panelArea") as HTMLElement;
            const panelWidgets = panelArea?.children;
            if (panelArea && panelWidgets.length) {
                const lastWidget = panelWidgets[panelWidgets.length - 1] as HTMLElement;

                const panelAreaHeight = panelArea.style.height
                    ? parseInt(panelArea.style.height, 10)
                    : panelArea?.clientHeight;
                const lastWidgetHeight = lastWidget.style.height
                    ? parseInt(lastWidget.style.height, 10)
                    : lastWidget?.clientHeight;

                const isNewPostLastWidget = Boolean(lastWidget?.querySelector(`.${containerRef.current.className}`));
                const isNewPostBeforeLastWidget =
                    !isNewPostLastWidget &&
                    Boolean(panelWidgets[panelWidgets.length - 2].querySelector(`.${containerRef.current.className}`));

                if (isNewPostLastWidget) {
                    panelArea.style.minHeight = `${panelAreaHeight + 180}px`;
                } else if (isNewPostBeforeLastWidget && lastWidgetHeight > 0 && lastWidgetHeight < 180) {
                    panelArea.style.minHeight = `${panelAreaHeight + 180 - lastWidgetHeight}px`;
                }
            }
        }
    }, [containerHeight]);

    // According to documentation here https://reach.tech/menu-button/#menu , "Escape" should close the menu drowdown,
    // but somehow, in our application it does not work so need to manually do it through this workaround
    const onMenuListKeyDown = (event: React.KeyboardEvent<any>) => {
        switch (event.key) {
            case "Escape":
                if (menuButtonRef && menuButtonRef.current) {
                    menuButtonRef.current.dispatchEvent(new Event("mousedown", { bubbles: true }));
                    menuButtonRef.current.focus();
                }
                break;
            default:
                break;
        }
    };

    const itemsInDropdown = items.length === 1 ? [] : items.filter((item) => !item.asOwnButton);
    const separateItems = items.length === 1 ? items : items.filter((item) => item.asOwnButton);

    const separateButtons = separateItems.map((item, i) => {
        return (
            <LinkAsButton
                key={i}
                buttonType={ButtonTypes.PRIMARY}
                className={cx(classes.button(borderRadius), classes.separateButton)}
                to={item.action as string}
            >
                <div className={classes.buttonContents}>
                    <div className={classes.buttonLabel}>{t(item.label)}</div>
                </div>
            </LinkAsButton>
        );
    });

    return (
        <Container>
            {title && (
                <PageHeadingBox
                    title={props.title}
                    options={{
                        alignment: containerOptions?.headerAlignment,
                    }}
                />
            )}
            <div className={classes.container} ref={containerRef}>
                {itemsInDropdown.length ? (
                    <Menu>
                        <MenuButtonImpl {...props} ref={menuButtonRef}>
                            <NewPostMenuIcon />
                        </MenuButtonImpl>
                        <StackingContextProvider>
                            <MenuList
                                portal={false}
                                onKeyDown={onMenuListKeyDown}
                                className={cx(dropDownClasses().contents, classes.buttonDropdownContents)}
                            >
                                {itemsInDropdown.map((item, i) => {
                                    return (
                                        <MenuLink
                                            key={i}
                                            as="a"
                                            href={context.makeHref(item.action as string)}
                                            className={cx(dropDownClasses().action, {
                                                [pointerEventsClass()]: props.disableDropdownItemsClick,
                                            })}
                                        >
                                            {t(item.label)}
                                        </MenuLink>
                                    );
                                })}
                            </MenuList>
                        </StackingContextProvider>
                    </Menu>
                ) : (
                    <React.Fragment />
                )}
                {separateButtons}
            </div>
        </Container>
    );
}
