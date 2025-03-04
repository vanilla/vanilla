/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ManagedIcon } from "@dashboard/appearance/manageIcons/ManagedIcon";
import {
    useActiveIconsQuery,
    useBulkUploadIconMutation,
    useDownloadIconsBulkMutation,
    useParseBulkIconZip,
    useSystemIconsQuery,
    type IParsedBulkIcons,
} from "@dashboard/appearance/manageIcons/ManageIcons.hooks";
import type { ManageIconsApi } from "@dashboard/appearance/manageIcons/ManageIconsApi";
import { Table } from "@dashboard/components/Table";
import { css } from "@emotion/css";
import { DataList } from "@library/dataLists/DataList";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import DropDownItem from "@library/flyouts/items/DropDownItem";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { UploadButton } from "@library/forms/UploadButton";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import { Row } from "@library/layout/Row";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Message from "@library/messages/Message";
import { Tag } from "@library/metas/Tags";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { t } from "@vanilla/i18n";
import { DropDownArrow } from "@vanilla/ui/src/forms/shared/DropDownArrow";
import { notEmpty } from "@vanilla/utils";

interface IProps {
    forceOpen?: boolean;
}

export function ManageIconsBulkActions(props: IProps) {
    const activeIconsQuery = useActiveIconsQuery();
    const systemIconsQuery = useSystemIconsQuery();

    const downloadActiveMutation = useDownloadIconsBulkMutation("vanilla-icons-active", activeIconsQuery.data ?? []);
    const downloadSystemMutation = useDownloadIconsBulkMutation("vanilla-icons-system", systemIconsQuery.data ?? []);
    const bulkParseMutation = useParseBulkIconZip();

    return (
        <Row align={"center"} gap={16}>
            <DropDown
                isVisible={props.forceOpen}
                buttonType={ButtonTypes.STANDARD}
                buttonContents={
                    <span className={classes.buttonContents}>
                        {t("Icon Packs")} <DropDownArrow />
                    </span>
                }
                flyoutType={FlyoutType.LIST}
            >
                <DropDownItemButton
                    mutation={downloadActiveMutation}
                    query={activeIconsQuery}
                    onClick={() => {
                        downloadActiveMutation.mutate();
                    }}
                >
                    {t("Download Active Icon Pack")}
                </DropDownItemButton>
                <DropDownItemButton
                    mutation={downloadSystemMutation}
                    query={systemIconsQuery}
                    onClick={() => {
                        downloadSystemMutation.mutate();
                    }}
                >
                    {t("Download System Icon Pack")}
                </DropDownItemButton>
                <DropDownItem>
                    <UploadButton
                        accessibleTitle={t("Upload Icon Pack")}
                        acceptedMimeTypes={"application/zip"}
                        multiple={false}
                        onUpload={(file) => {
                            bulkParseMutation.mutate(file);
                        }}
                        buttonType={ButtonTypes.CUSTOM}
                        className={dropDownClasses().action}
                    >
                        {t("Upload Icon Pack")}
                    </UploadButton>
                </DropDownItem>
            </DropDown>
            {bulkParseMutation.isSuccess && activeIconsQuery.data && bulkParseMutation.data && (
                <ParsedBulkUploadPreviewModal
                    activeIcons={activeIconsQuery.data}
                    onClose={() => {
                        bulkParseMutation.reset();
                    }}
                    parsed={bulkParseMutation.data}
                />
            )}
        </Row>
    );
}

function ParsedBulkUploadPreviewModal(props: {
    parsed: IParsedBulkIcons;
    onClose(): void;
    activeIcons: ManageIconsApi.IManagedIcon[];
}) {
    const { parsed, activeIcons } = props;
    const validIconNames = activeIcons.map((icon) => icon.iconName);
    const changedIcons = parsed.icons.filter((parsedIcon) => {
        const hasActiveIcon = validIconNames.includes(parsedIcon.iconName);
        const hasExactMatchActiveIcon = !!activeIcons.find((activeIcon) => {
            if (activeIcon.iconName !== parsedIcon.iconName) {
                // This is a different icon.
            }

            const hasUnsafeRawMatch = activeIcon.svgRaw === (parsedIcon.unsafeSvgRaw ?? parsedIcon.svgRaw);
            const hasRawMatch = activeIcon.svgRaw === parsedIcon.svgRaw;
            return hasUnsafeRawMatch || hasRawMatch;
        });
        return hasActiveIcon && !hasExactMatchActiveIcon;
    });

    const hasChangedIcons = changedIcons.length > 0;

    const bulkMutation = useBulkUploadIconMutation();

    return (
        <Modal size={ModalSizes.LARGE} isVisible={true} exitHandler={props.onClose}>
            <Frame
                header={<FrameHeader title={t("Preview Icon Pack")} closeFrame={props.onClose} />}
                body={
                    <FrameBody>
                        {bulkMutation.error && <Message type="error" error={bulkMutation.error} />}
                        <section>
                            <DataList
                                caption={"Icon Pack Summary"}
                                data={[
                                    {
                                        key: t("Changed Icons"),
                                        value: changedIcons.length,
                                    },
                                ]}
                            ></DataList>
                        </section>
                        {hasChangedIcons ? (
                            <Table
                                truncateCells={false}
                                data={changedIcons
                                    .map((icon) => {
                                        const activeIcon =
                                            activeIcons.find((activeIcon) => activeIcon.iconName === icon.iconName) ??
                                            null;

                                        if (!activeIcon) {
                                            return null;
                                        }

                                        return {
                                            [t("Icon Name")]: <Tag>{icon.iconName}</Tag>,
                                            [t("Active Icon")]: (
                                                <div className={classes.iconWrap}>
                                                    <ManagedIcon
                                                        withGrid={true}
                                                        managedIcon={activeIcon}
                                                        iconSize={48}
                                                    />
                                                </div>
                                            ),
                                            [t("New Icon")]: (
                                                <div className={classes.iconWrap}>
                                                    <ManagedIcon withGrid={true} managedIcon={icon} iconSize={48} />
                                                </div>
                                            ),
                                        };
                                    })
                                    .filter(notEmpty)}
                            />
                        ) : (
                            <Message
                                className={classes.error}
                                type="error"
                                error={{ message: t("All icons in this icon pack are currently active.") }}
                            />
                        )}
                    </FrameBody>
                }
                footer={
                    <FrameFooter justifyRight={true}>
                        <Button
                            disabled={!hasChangedIcons || bulkMutation.isLoading}
                            buttonType={ButtonTypes.TEXT_PRIMARY}
                            onClick={() => {
                                void bulkMutation
                                    .mutateAsync(
                                        changedIcons.map((icon) => ({ svgRaw: icon.svgRaw, iconName: icon.iconName })),
                                    )
                                    .then(() => {
                                        props.onClose();
                                    });
                            }}
                        >
                            {bulkMutation.isLoading ? <ButtonLoader /> : t("Activate Icon Pack")}
                        </Button>
                    </FrameFooter>
                }
            />
        </Modal>
    );
}

const classes = {
    error: css({
        marginBottom: 16,
    }),
    iconWrap: css({
        padding: 8,
        width: "100%",
    }),
    column: css({
        flex: 1,
    }),
    buttonContents: css({
        display: "inline-flex",
        alignItems: "center",
        gap: 16,
    }),
};
