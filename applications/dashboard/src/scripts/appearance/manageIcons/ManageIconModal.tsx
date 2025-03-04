/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ManagedIcon } from "@dashboard/appearance/manageIcons/ManagedIcon";
import {
    useDeleteIconMutation,
    useIconRevisions,
    useRestoreIconMutation,
    useUploadIconMutation,
} from "@dashboard/appearance/manageIcons/ManageIcons.hooks";
import type { ManageIconsApi } from "@dashboard/appearance/manageIcons/ManageIconsApi";
import { DashboardFormSubheading } from "@dashboard/forms/DashboardFormSubheading";
import { css } from "@emotion/css";
import DateTime from "@library/content/DateTime";
import Translate from "@library/content/Translate";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import { UploadButton } from "@library/forms/UploadButton";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameHeader from "@library/layout/frame/FrameHeader";
import { Row } from "@library/layout/Row";
import { List } from "@library/lists/List";
import { ListItem } from "@library/lists/ListItem";
import { QueryLoader } from "@library/loaders/QueryLoader";
import Message from "@library/messages/Message";
import { MetaItem } from "@library/metas/Metas";
import { metasClasses } from "@library/metas/Metas.styles";
import { Tag } from "@library/metas/Tags";
import { TagPreset } from "@library/metas/Tags.variables";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import ProfileLink from "@library/navigation/ProfileLink";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { downloadAsFile } from "@vanilla/dom-utils";
import { t } from "@vanilla/i18n";

interface IProps {
    activeIcon: ManageIconsApi.IManagedIcon | null;
    onClose: () => void;
}

export function ManageIconModal(props: IProps) {
    return (
        <Modal size={ModalSizes.XL} isVisible={props.activeIcon !== null} exitHandler={props.onClose}>
            {props.activeIcon && (
                <Frame
                    header={
                        <FrameHeader
                            title={
                                <Row align={"center"} gap={6}>
                                    {t("Manage Icon")} <Tag> {props.activeIcon.iconName}</Tag>
                                </Row>
                            }
                            closeFrame={props.onClose}
                        />
                    }
                    body={
                        <FrameBody>
                            <ManageIconForm icon={props.activeIcon} />
                        </FrameBody>
                    }
                />
            )}
        </Modal>
    );
}

function ManageIconForm(props: { icon: ManageIconsApi.IManagedIcon }) {
    const { icon } = props;
    const iconRevisionsQuery = useIconRevisions(icon.iconName);
    const uploadIconMutation = useUploadIconMutation(icon.iconName);
    return (
        <div>
            <section className={classes.section}>
                <p>
                    {t(
                        "Manage Icon helptext",
                        "You can manage system and historical icons here. When uploading, make sure the icon is in SVG format, includes a viewBox attribute, and uses color #000000 to ensure it can be dynamically colored based on theme settings and user interactions. Only one icon of each type can be active at a time.",
                    )}
                </p>
            </section>

            <QueryLoader
                query={iconRevisionsQuery}
                success={(iconRevisions) => {
                    return (
                        <>
                            <section className={classes.section}>
                                <DashboardFormSubheading
                                    hasBackground={true}
                                    actions={
                                        <UploadButton
                                            accessibleTitle={t("Upload New Icon")}
                                            acceptedMimeTypes={"image/svg+xml"}
                                            multiple={false}
                                            onUpload={(file) => {
                                                uploadIconMutation.mutate(file);
                                            }}
                                        >
                                            {t("Upload New Icon")}
                                        </UploadButton>
                                    }
                                >
                                    {t("Active Icon")}
                                </DashboardFormSubheading>

                                {uploadIconMutation.error && (
                                    <Message type={"error"} error={uploadIconMutation.error as any} />
                                )}

                                <List
                                    options={{
                                        itemBox: {
                                            borderType: BorderType.SEPARATOR_BETWEEN,
                                        },
                                    }}
                                >
                                    {iconRevisions
                                        .filter((icon) => icon.isActive)
                                        .map((icon) => {
                                            return <IconRow key={icon.iconUUID} icon={icon} />;
                                        })}
                                </List>
                            </section>
                            <DashboardFormSubheading hasBackground={true}>Previous Icons</DashboardFormSubheading>
                            <section className={classes.section}>
                                <p>Previous versions of your icons can be found here and restored at any time.</p>
                                <List
                                    options={{
                                        itemBox: {
                                            borderType: BorderType.SEPARATOR_BETWEEN,
                                        },
                                    }}
                                >
                                    {iconRevisions
                                        .filter((icon) => !icon.isActive)
                                        .map((icon) => {
                                            return <IconRow key={icon.iconUUID} icon={icon} />;
                                        })}
                                </List>
                            </section>
                        </>
                    );
                }}
            />
        </div>
    );
}

function IconRow(props: { icon: ManageIconsApi.IManagedIcon }) {
    const { icon } = props;
    const restoreMutation = useRestoreIconMutation(icon.iconName);
    const deleteMutation = useDeleteIconMutation(icon.iconUUID);
    return (
        <ListItem
            icon={<ManagedIcon withGrid managedIcon={icon} iconSize={96} />}
            actions={
                <DropDown flyoutType={FlyoutType.LIST} asReachPopover>
                    <DropDownItemButton
                        onClick={() => {
                            deleteMutation.mutate();
                        }}
                        disabled={!icon.isCustom || deleteMutation.isLoading}
                        isLoading={deleteMutation.isLoading}
                    >
                        {t("Delete")}
                    </DropDownItemButton>
                    <DropDownItemButton
                        isLoading={restoreMutation.isLoading}
                        disabled={restoreMutation.isLoading || icon.isActive}
                        onClick={() => {
                            restoreMutation.mutate(icon.iconUUID);
                        }}
                    >
                        {t("Set as Active")})
                    </DropDownItemButton>
                    <DropDownItemButton
                        onClick={() => {
                            downloadAsFile(icon.svgRaw, `${icon.iconName}`, { fileExtension: "svg" });
                        }}
                    >
                        {t("Download")}
                    </DropDownItemButton>
                </DropDown>
            }
            name={icon.isActive ? <Tag preset={TagPreset.COLORED}>{t("Active")}</Tag> : <Tag>{t("Inactive")}</Tag>}
            metas={
                <>
                    <MetaItem>
                        <Translate
                            source={"Uploaded <0/> by <1/>"}
                            c0={<DateTime timestamp={icon.dateInserted} />}
                            c1={<ProfileLink className={metasClasses().metaLink} userFragment={icon.insertUser} />}
                        />
                    </MetaItem>
                </>
            }
        />
    );
}

const classes = {
    metadata: css({}),
    section: css({
        marginTop: 16,
    }),
    actions: css({
        display: "flex",
        gap: 16,
    }),
    activeIcons: css({
        marginTop: 8,
        display: "flex",
        gap: 24,
        alignItems: "flex-end",
    }),
};
