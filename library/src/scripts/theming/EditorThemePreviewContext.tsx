/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css, cx } from "@emotion/css";
import apiv2 from "@library/apiv2";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import InputTextBlock from "@library/forms/InputTextBlock";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { QueryLoader } from "@library/loaders/QueryLoader";
import { MetaItem, Metas } from "@library/metas/Metas";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { DynamicCustomFontLoader } from "@library/theming/DynamicCustomFontLoader";
import { ThemeOverrideContext } from "@library/theming/ThemeOverrideContext";
import ThemePreviewCard from "@library/theming/ThemePreviewCard";
import type { ITheme } from "@library/theming/themeReducer";
import { useQuery, type UseQueryResult } from "@tanstack/react-query";
import { t } from "@vanilla/i18n";
import { useSessionStorage } from "@vanilla/react-utils";
import { CustomRadioGroup, CustomRadioInput } from "@vanilla/ui";
import { createContext, useCallback, useContext, useEffect, useState } from "react";

interface IContext {
    previewedThemeID: string | null;
    setPreviewedThemeID: (themeID: string | null) => void;
    previewedThemeQuery: UseQueryResult<ITheme>;
    allThemesQuery: UseQueryResult<ITheme[]>;
}

export const EditorThemePreviewContext = createContext<IContext>({
    previewedThemeID: "",
    setPreviewedThemeID: () => {},
    previewedThemeQuery: {
        isLoading: true,
    } as any,
    allThemesQuery: {
        isLoading: true,
    } as any,
});

export function EditorThemePreviewProvider(props: {
    children: React.ReactNode;
    previewedThemeID?: string | null;
    onPreviewedThemeIDChange?: (themeID: string | null) => void;
}) {
    const allThemesQuery = useQuery({
        queryKey: ["allThemes"],
        queryFn: async () => {
            const response = await apiv2.get("/themes");
            return response.data as ITheme[];
        },
        keepPreviousData: true,
    });

    function getInitialThemeID(): string | null {
        if (allThemesQuery.data == null) {
            return null;
        }
        const themes = allThemesQuery.data;
        return (
            themes.find((theme) => theme.current)?.themeID ??
            themes.find((theme) => theme.themeID === "theme-foundation")?.themeID ??
            themes[0]?.themeID ??
            null
        );
    }

    const [_previewedThemeID, _setPreviewedThemeID] = useSessionStorage<string | null>(
        "layoutPreviewedTheme",
        getInitialThemeID(),
    );
    const setPreviewedThemeID = useCallback(
        (newThemeID: string | null) => {
            _setPreviewedThemeID(newThemeID);
            props.onPreviewedThemeIDChange?.(newThemeID);
        },
        [_setPreviewedThemeID, props.onPreviewedThemeIDChange],
    );
    const previewedThemeID = props.previewedThemeID ?? _previewedThemeID;

    const previewedThemeQuery = useQuery({
        queryKey: ["layoutTheme", previewedThemeID],
        queryFn: async () => {
            if (previewedThemeID) {
                const response = await apiv2.get(`/themes/${previewedThemeID}?expand=all`);
                return response.data as ITheme;
            } else {
                const response = await apiv2.get("/themes/current");
                return response.data as ITheme;
            }
        },
        keepPreviousData: true,
    });

    useEffect(() => {
        if (previewedThemeID == null) {
            // All themes just loaded.
            setPreviewedThemeID(getInitialThemeID());
        } else if (allThemesQuery.data && !allThemesQuery.data.some((theme) => theme.themeID === previewedThemeID)) {
            // The themeID in session storage is not in the list of themes anymore.
            setPreviewedThemeID(getInitialThemeID());
        }
    }, [previewedThemeID, allThemesQuery.data]);

    const contextValue = {
        previewedThemeID,
        setPreviewedThemeID,
        allThemesQuery,
        previewedThemeQuery,
    };

    return (
        <EditorThemePreviewContext.Provider value={contextValue}>{props.children}</EditorThemePreviewContext.Provider>
    );
}

export function useEditorThemePreview() {
    const context = useContext(EditorThemePreviewContext);
    return context;
}

export function EditorThemePreviewOverrides(props: { fallback?: React.ReactNode; children: React.ReactNode }) {
    const { previewedThemeQuery } = useEditorThemePreview();

    return (
        <QueryLoader
            query={previewedThemeQuery}
            loader={props.fallback}
            success={(theme) => {
                return (
                    <ThemeOverrideContext.Provider
                        value={{
                            themeID: theme.themeID,
                            overridesVariables: theme.assets.variables?.data ?? {},
                        }}
                    >
                        <DynamicCustomFontLoader />
                        {props.children}
                    </ThemeOverrideContext.Provider>
                );
            }}
        />
    );
}

export function EditorThemePreviewDropDownItem() {
    const { previewedThemeQuery } = useEditorThemePreview();
    const [showThemePreviewModal, setShowThemePreviewModal] = useState(false);
    return (
        <DropDownItemButton
            onClick={() => {
                setShowThemePreviewModal(true);
            }}
        >
            <div>
                {t("Change Preview Styleguide")}
                <Metas>
                    <MetaItem>{previewedThemeQuery.data?.name}</MetaItem>
                </Metas>
            </div>
            {showThemePreviewModal && (
                <EditorThemePreviewPickerModal
                    onClose={() => {
                        setShowThemePreviewModal(false);
                    }}
                />
            )}
        </DropDownItemButton>
    );
}

export function EditorThemePreviewPickerModal(props: { onClose: () => void }) {
    const { previewedThemeQuery, previewedThemeID, setPreviewedThemeID, allThemesQuery } = useEditorThemePreview();
    const [newValue, setNewValue] = useState<string>(previewedThemeID ?? "");
    const [awaitNewThemeID, setAwaitNewThemeID] = useState<string | null>(null);
    const [filter, setFilter] = useState("");

    useEffect(() => {
        if (
            awaitNewThemeID &&
            !previewedThemeQuery.isLoading &&
            previewedThemeQuery.data?.themeID === awaitNewThemeID
        ) {
            props.onClose();
        }
    }, [awaitNewThemeID, previewedThemeQuery.isLoading, props.onClose]);

    return (
        <Modal size={ModalSizes.LARGE} isVisible={true} exitHandler={props.onClose}>
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    setPreviewedThemeID(newValue);
                    setAwaitNewThemeID(newValue);
                }}
            >
                <Frame
                    header={
                        <FrameHeader
                            titleID={"preview-theme-title"}
                            title={t("Choose Preview Styleguide")}
                            closeFrame={props.onClose}
                        />
                    }
                    footer={
                        <FrameFooter justifyRight={true}>
                            <Button
                                disabled={awaitNewThemeID != null}
                                buttonType={ButtonTypes.TEXT_PRIMARY}
                                type="submit"
                            >
                                {awaitNewThemeID != null ? <ButtonLoader /> : t("Preview")}
                            </Button>
                        </FrameFooter>
                    }
                    body={
                        <FrameBody selfPadded={true}>
                            <div className={classes.filter}>
                                <InputTextBlock
                                    inputProps={{
                                        value: filter,
                                        onChange: (e) => setFilter(e.target.value),
                                        placeholder: "My theme name...",
                                    }}
                                    label={"Filter"}
                                ></InputTextBlock>
                            </div>
                            <QueryLoader
                                query={allThemesQuery}
                                success={(allThemes) => {
                                    return (
                                        <CustomRadioGroup
                                            aria-labelledby={"preview-theme-title"}
                                            name="layoutSection"
                                            onChange={(val) => {
                                                setNewValue(val as string);
                                            }}
                                            value={newValue}
                                        >
                                            <div className={classes.grid}>
                                                {allThemes
                                                    .filter((theme) => {
                                                        return theme.name.toLowerCase().includes(filter.toLowerCase());
                                                    })
                                                    .map((theme) => {
                                                        return (
                                                            <CustomRadioInput value={theme.themeID} key={theme.themeID}>
                                                                {({ isSelected, isFocused }) => {
                                                                    return (
                                                                        <div
                                                                            className={cx(classes.gridItem, {
                                                                                isSelected,
                                                                                isFocused,
                                                                            })}
                                                                        >
                                                                            <ThemePreviewCard
                                                                                focusable={false}
                                                                                noActions={true}
                                                                                name={theme.name}
                                                                                preview={theme.preview}
                                                                                active={isSelected}
                                                                            />
                                                                            <div className={classes.gridItemLabel}>
                                                                                <strong>{theme.name}</strong>
                                                                            </div>
                                                                        </div>
                                                                    );
                                                                }}
                                                            </CustomRadioInput>
                                                        );
                                                    })}
                                            </div>
                                        </CustomRadioGroup>
                                    );
                                }}
                            ></QueryLoader>
                        </FrameBody>
                    }
                />
            </form>
        </Modal>
    );
}

const classes = {
    grid: css({
        display: "grid",
        justifyItems: "stretch",
        alignItems: "stretch",
        gridTemplateColumns: "repeat(auto-fill, minmax(200px, 1fr))",
    }),
    gridItem: css({
        padding: "16px",
        borderRadius: 6,
        height: "100%",
        "&:hover": {
            backgroundColor: ColorsUtils.colorOut(globalVariables().states.hover.highlight),
        },
        "&:focus, &.isFocused": {
            backgroundColor: ColorsUtils.colorOut(globalVariables().states.hover.highlight),
            border: `1px solid ${globalVariables().mainColors.primary}`,
        },
        "& .constraintContainer": {
            border: "none",
        },
    }),
    gridItemLabel: css({
        paddingTop: 12,
    }),
    filter: css({
        margin: 16,
    }),
};
