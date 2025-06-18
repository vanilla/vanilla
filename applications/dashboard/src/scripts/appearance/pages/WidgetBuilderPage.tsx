/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { WidgetBuilderDisclosureAccess } from "@dashboard/appearance/fragmentEditor/FragmentEditorDisclosureAccess";
import {
    FragmentListFilters,
    type IFragmentListFilters,
} from "@dashboard/appearance/fragmentEditor/FragmentListFilters";
import { FragmentsApi } from "@dashboard/appearance/fragmentEditor/FragmentsApi";
import { useActiveFragments, useDeleteFragmentMutation } from "@dashboard/appearance/fragmentEditor/FragmentsApi.hooks";
import { FragmentEditorRoute } from "@dashboard/appearance/routes/appearanceRoutes";
import { AppearanceAdminLayout } from "@dashboard/components/navigation/AppearanceAdminLayout";
import { LayoutThumbnailsModal } from "@dashboard/layout/editor/thumbnails/LayoutThumbnailsModal";
import { useLayoutCatalog } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import { EmptyState } from "@dashboard/moderation/components/EmptyState";
import { css } from "@emotion/css";
import DateTime from "@library/content/DateTime";
import Translate from "@library/content/Translate";
import { userContentClasses } from "@library/content/UserContent.styles";
import DropDown from "@library/flyouts/DropDown";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import Button from "@library/forms/Button";
import InputTextBlock from "@library/forms/InputTextBlock";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameHeader from "@library/layout/frame/FrameHeader";
import { Row } from "@library/layout/Row";
import { List } from "@library/lists/List";
import { ListItem } from "@library/lists/ListItem";
import { QueryLoader } from "@library/loaders/QueryLoader";
import Message from "@library/messages/Message";
import { MetaIcon, MetaItem, Metas, MetaTag } from "@library/metas/Metas";
import Notice from "@library/metas/Notice";
import { FramedModal } from "@library/modal/FramedModal";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { useLinkContext } from "@library/routing/links/LinkContextProvider";
import SmartLink from "@library/routing/links/SmartLink";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { BorderType, singleBorder } from "@library/styles/styleHelpersBorders";
import { assetUrl } from "@library/utility/appUtils";
import { useQuery } from "@tanstack/react-query";
import { formatList, t } from "@vanilla/i18n";
import { useState } from "react";
import { sprintf } from "sprintf-js";

export default function WidgetBuilderPage() {
    const [formValue, setFormValue] = useState<IFragmentListFilters>({
        appliedStatus: "all",
        name: "",
        fragmentType: undefined,
    });

    const fragmentsQuery = useActiveFragments({
        fragmentType: formValue.fragmentType,
        appliedStatus: formValue.appliedStatus,
    });
    const [showAddModal, setShowAddModal] = useState(false);
    const linkContext = useLinkContext();
    const didAcceptQuery = useQuery({
        queryKey: ["didAcceptDisclosure"],
        queryFn: async () => {
            return FragmentsApi.getAcceptedDisclosure();
        },
    });

    return (
        <AppearanceAdminLayout
            title={t("Widget Builder")}
            titleBarActions={
                <Button
                    disabled={!didAcceptQuery.data}
                    buttonType={"outline"}
                    onClick={() => {
                        setShowAddModal(true);
                    }}
                >
                    {t("Create Widget")}
                </Button>
            }
            content={
                <div style={{ padding: 16 }}>
                    <WidgetBuilderDisclosureAccess type={"disclosure"}>
                        <>
                            <List>
                                <QueryLoader
                                    query={fragmentsQuery}
                                    success={(fragments) => {
                                        const filteredFragments = fragments.filter((result) => {
                                            if (
                                                formValue.name.length > 0 &&
                                                !result.name.toLowerCase().includes(formValue.name.toLowerCase())
                                            ) {
                                                return false;
                                            }

                                            return true;
                                        });

                                        if (filteredFragments.length === 0) {
                                            return (
                                                <EmptyState
                                                    text={t("No custom widgets found.")}
                                                    subtext={
                                                        fragments.length === 0
                                                            ? t("Create one to get started.")
                                                            : t("Try adjusting your filters.")
                                                    }
                                                />
                                            );
                                        }

                                        return (
                                            <>
                                                {filteredFragments.map((fragment) => (
                                                    <FragmentItem key={fragment.fragmentUUID} fragment={fragment} />
                                                ))}
                                            </>
                                        );
                                    }}
                                />
                            </List>
                            <SelectFragmentModal
                                isVisible={showAddModal}
                                setVisible={setShowAddModal}
                                onSelect={(fragmentType) => {
                                    linkContext.pushSmartLocation(FragmentEditorRoute.url({ fragmentType }));
                                }}
                            />
                        </>
                    </WidgetBuilderDisclosureAccess>
                </div>
            }
            rightPanel={didAcceptQuery.data && <FragmentListFilters value={formValue} onChange={setFormValue} />}
        />
    );
}

function SelectFragmentModal(props: {
    isVisible: boolean;
    setVisible: (val: boolean) => void;
    onSelect: (fragmentType: string) => void;
}) {
    const catalog = useLayoutCatalog("all");

    const widgetsWithFragments = Object.fromEntries(
        Object.entries({ ...catalog?.widgets, ...catalog?.assets }).filter(([key, value]) => {
            return (value.fragmentTypes?.length ?? 0) > 0;
        }),
    );

    const uniqueWidgetsWithFragments = Object.fromEntries(
        Object.entries(widgetsWithFragments).reduce((acc, [key, value]) => {
            const existingWidget = acc.find(([_, widget]) => widget.$reactComponent === value.$reactComponent);
            if (!existingWidget) {
                acc.push([key, value]);
            }
            return acc;
        }, [] as Array<[string, any]>),
    );

    uniqueWidgetsWithFragments["CustomFragment"] = {
        schema: {
            type: "object",
            properties: {},
        },
        name: t("Custom Fragment"),
        fragmentTypes: ["CustomFragment"],
        iconUrl: assetUrl("/applications/dashboard/design/images/widgetIcons/customhtml.svg"),
        $reactComponent: "CustomFragment",
        widgetGroup: "Widgets",
    };

    return (
        <>
            {catalog && (
                <LayoutThumbnailsModal
                    title={t("Select Widget")}
                    descriptionOverride={<p>{t("Select a widget to customize")}</p>}
                    sections={uniqueWidgetsWithFragments}
                    isVisible={props.isVisible}
                    exitHandler={() => {
                        props.setVisible(false);
                    }}
                    onAddSection={function (widgetID: string): void {
                        const fragmentType = uniqueWidgetsWithFragments[widgetID]?.fragmentTypes?.[0];
                        if (fragmentType) {
                            props.onSelect(fragmentType);
                        }
                    }}
                    itemType={"widgets"}
                    disableGrouping={true}
                />
            )}
        </>
    );
}

function FragmentItem(props: { fragment: FragmentsApi.Fragment }) {
    const { fragment } = props;

    const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);

    return (
        <>
            <ListItem
                name={
                    <Row align={"center"} gap={12}>
                        <span>{fragment.name}</span>
                    </Row>
                }
                url={FragmentEditorRoute.url(fragment)}
                metas={<FragmentMetas fragment={fragment} includeApplied={true} />}
                boxOptions={{ borderType: BorderType.SHADOW }}
                actionAlignment={"center"}
                actions={
                    <Row gap={12} align={"center"}>
                        <DropDown>
                            <DropDownItemLink to={FragmentEditorRoute.url(fragment)}>
                                {fragment.status === "draft" ? t("Edit Draft") : t("Edit")}
                            </DropDownItemLink>
                            <DropDownItemLink
                                to={FragmentEditorRoute.url({
                                    ...fragment,
                                    isCopy: true,
                                })}
                            >
                                {t("Copy")}
                            </DropDownItemLink>
                            <DropDownItemButton
                                onClick={() => {
                                    setShowDeleteConfirm(true);
                                }}
                            >
                                {t("Delete")}
                            </DropDownItemButton>
                        </DropDown>
                        {showDeleteConfirm && (
                            <DeleteModal fragment={fragment} onClose={() => setShowDeleteConfirm(false)} />
                        )}
                    </Row>
                }
            />
        </>
    );
}

function FragmentMetas(props: { fragment: FragmentsApi.Fragment; includeApplied?: boolean }) {
    const { fragment, includeApplied } = props;

    const [showApplied, setShowApplied] = useState(false);

    let appliedLabel: React.ReactNode = null;
    const appliedThemes = fragment.fragmentViews.filter((view) => view.recordType === "theme");
    const appliedLayouts = fragment.fragmentViews.filter((view) => view.recordType === "layout");
    if (fragment.isApplied) {
        const labelPieces: string[] = [];

        if (appliedThemes.length === 1) {
            labelPieces.push(sprintf(t("%d theme"), 1));
        } else if (appliedThemes.length > 1) {
            labelPieces.push(sprintf(t("%d themes"), appliedThemes.length));
        }

        if (appliedLayouts.length === 1) {
            labelPieces.push(sprintf(t("%d layout"), 1));
        } else if (appliedLayouts.length > 1) {
            labelPieces.push(sprintf(t("%d layouts"), appliedLayouts.length));
        }

        appliedLabel = formatList(labelPieces);
    }

    return (
        <>
            <MetaTag>{fragment.fragmentType}</MetaTag>
            <MetaIcon icon="meta-time">
                <Translate source="Last updated <0/>" c0={<DateTime timestamp={fragment.dateRevisionInserted} />} />
            </MetaIcon>
            {!fragment.isLatest && (
                <MetaItem>
                    <Notice>{t("Pending Draft")}</Notice>
                </MetaItem>
            )}
            {includeApplied && appliedLabel && (
                <MetaItem>
                    <Translate
                        source={"Applied on <0/>"}
                        c0={
                            <Button
                                onClick={() => {
                                    setShowApplied(true);
                                }}
                                buttonType={"text"}
                            >
                                {appliedLabel}
                            </Button>
                        }
                    />
                    {showApplied && (
                        <Modal
                            size={ModalSizes.MEDIUM}
                            isVisible={showApplied}
                            exitHandler={() => {
                                setShowApplied(false);
                            }}
                        >
                            <Frame
                                header={
                                    <FrameHeader
                                        title={t("Fragment Usages")}
                                        closeFrame={() => {
                                            setShowApplied(false);
                                        }}
                                    />
                                }
                                body={
                                    <FrameBody hasVerticalPadding={true}>
                                        {appliedThemes.length > 0 && (
                                            <>
                                                <strong>{t("Themes")}</strong>
                                                <ul>
                                                    {appliedThemes.map((view) => (
                                                        <li key={view.recordID}>
                                                            <SmartLink to={view.recordUrl}>{view.recordName}</SmartLink>
                                                        </li>
                                                    ))}
                                                </ul>
                                            </>
                                        )}
                                        {appliedLayouts.length > 0 && (
                                            <>
                                                <strong>{t("Layouts")}</strong>
                                                <ul>
                                                    {appliedLayouts.map((view) => (
                                                        <li key={view.recordID}>
                                                            <SmartLink to={view.recordUrl}>{view.recordName}</SmartLink>
                                                        </li>
                                                    ))}
                                                </ul>
                                            </>
                                        )}
                                    </FrameBody>
                                }
                            />
                        </Modal>
                    )}
                </MetaItem>
            )}
        </>
    );
}

function DeleteModal(props: { fragment: FragmentsApi.Fragment; onClose: () => void }) {
    const { onClose, fragment } = props;
    const [showWarning, setShowWarning] = useState(true);
    const [inputValue, setInputValue] = useState("");

    const deleteMutation = useDeleteFragmentMutation({
        fragmentUUID: fragment.fragmentUUID,
    });

    return (
        <FramedModal
            padding="all"
            title={t("Delete Fragment")}
            onClose={props.onClose}
            size={ModalSizes.MEDIUM}
            onBackClick={
                !showWarning
                    ? () => {
                          setShowWarning(true);
                      }
                    : undefined
            }
            onFormSubmit={() => {
                // Prevent the form from submitting when the modal is closed via the button.
                // This is handled by the mutation itself.
                deleteMutation.mutate({});
                onClose();
            }}
        >
            <div className={classes.deleteFragmentRow}>
                <strong>{fragment.name}</strong>
                <Metas>
                    <FragmentMetas fragment={fragment} />
                </Metas>
            </div>

            {showWarning ? (
                <>
                    {fragment.isApplied && (
                        <Message
                            className={classes.deleteWarning}
                            type={"warning"}
                            title={t("Fragment is applied")}
                            stringContents={t(
                                "Deleting an applied fragment will cause all usages to revert to the system implementation.",
                            )}
                            contents={
                                <>
                                    <p>
                                        {t(
                                            "Deleting an applied fragment the following usages to revert to the system implementation.",
                                        )}
                                    </p>
                                    <FragmentAppliedLocations fragment={fragment} />
                                </>
                            }
                        />
                    )}
                    <div className={userContentClasses().root}>
                        <p>
                            <Translate source="Deleting this fragment will have the following effects:" />
                        </p>
                        <ul>
                            <li>{t("The fragment and all its revisions will be permanently deleted.")}</li>
                            <li>{t("Any current usages of the fragment will revert to the system implentation.")}</li>
                        </ul>
                    </div>
                    <Button
                        className={classes.confirmButton}
                        buttonType={"standard"}
                        style={{ width: "100%" }}
                        onClick={() => {
                            setShowWarning(false);
                        }}
                    >
                        {t("I have read and understand these effects.")}
                    </Button>
                </>
            ) : (
                <>
                    <InputTextBlock
                        labelClassName={classes.deleteLabel}
                        inputProps={{
                            inputClassNames: classes.deleteInput,
                            value: inputValue,
                            onChange: (e) => {
                                setInputValue(e.target.value);
                            },
                        }}
                        label={<Translate source='To confirm, type "<0/>" in the box below' c0={fragment.name} />}
                    />
                    <Button
                        type={"submit"}
                        mutation={deleteMutation}
                        buttonType={"standard"}
                        className={classes.deleteButton}
                    >
                        {t("Delete Fragment")}
                    </Button>
                </>
            )}
        </FramedModal>
    );
}

function FragmentAppliedLocations(props: { fragment: FragmentsApi.Fragment }) {
    const { fragment } = props;
    const appliedThemes = fragment.fragmentViews.filter((view) => view.recordType === "theme");
    const appliedLayouts = fragment.fragmentViews.filter((view) => view.recordType === "layout");
    return (
        <>
            {appliedThemes.length > 0 && (
                <>
                    <strong className={classes.appliedLabel}>{t("Themes")}</strong>
                    <ul className={classes.appliedList}>
                        {appliedThemes.map((view) => (
                            <li key={view.recordID}>
                                <SmartLink to={view.recordUrl}>{view.recordName}</SmartLink>
                            </li>
                        ))}
                    </ul>
                </>
            )}
            {appliedLayouts.length > 0 && (
                <>
                    <strong className={classes.appliedLabel}>{t("Layouts")}</strong>
                    <ul className={classes.appliedList}>
                        {appliedLayouts.map((view) => (
                            <li key={view.recordID}>
                                <SmartLink to={view.recordUrl}>{view.recordName}</SmartLink>
                            </li>
                        ))}
                    </ul>
                </>
            )}
        </>
    );
}

const classes = {
    deleteFragmentRow: css({
        borderBottom: singleBorder(),
        paddingBottom: 16,
        marginBottom: 16,
    }),
    appliedLabel: css({
        display: "block",
        marginBottom: 4,
        marginTop: 8,
    }),
    appliedList: css({
        "& li": {
            listStyle: "inside",
        },
    }),
    confirmButton: css({
        width: "100%",
        marginTop: 12,
    }),
    deleteButton: css({
        width: "100%",
        marginTop: 12,
        color: ColorsUtils.colorOut(globalVariables().elementaryColors.red),
    }),
    deleteLabel: css({
        color: ColorsUtils.colorOut(globalVariables().elementaryColors.red),
    }),
    deleteInput: css({
        borderColor: ColorsUtils.colorOut(globalVariables().elementaryColors.red),
    }),
    deleteWarning: css({
        marginBottom: 16,
    }),
};
