/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardInputWrap } from "@dashboard/forms/DashboardInputWrap";
import { DashboardLabelType } from "@dashboard/forms/DashboardLabelType";
import { DashboardSchemaForm } from "@dashboard/forms/DashboardSchemaForm";
import type { IRole } from "@dashboard/roles/roleTypes";
import type { IMe } from "@library/@types/api/users";
import apiv2 from "@library/apiv2";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import Permission from "@library/features/users/Permission";
import { PermissionOverridesContext } from "@library/features/users/PermissionOverrideContext";
import { PermissionsContext } from "@library/features/users/PermissionsContext";
import { CurrentUserContextProvider } from "@library/features/users/userHooks";
import { GUEST_USER_ID } from "@library/features/users/userModel";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import Button from "@library/forms/Button";
import InputBlock from "@library/forms/InputBlock";
import { NestedSelect } from "@library/forms/nestedSelect";
import { Row } from "@library/layout/Row";
import { MetaItem, Metas, MetaTag } from "@library/metas/Metas";
import { TokenItem } from "@library/metas/TokenItem";
import { FramedModal } from "@library/modal/FramedModal";
import { useQuery } from "@tanstack/react-query";
import { formatList, t } from "@vanilla/i18n";
import { useSessionStorage } from "@vanilla/react-utils";
import type { RecordID } from "@vanilla/utils";
import { createContext, useCallback, useContext, useMemo, useState } from "react";

interface IEditorRolePreviewContext {
    selectedRoleIDs: Array<IRole["roleID"]>;
    selectedRoleNames: Array<IRole["name"]>;
    setSelectedRoleIDs: (roleIDs: Array<IRole["roleID"]>) => void;
}

export const EditorRolePreviewContext = createContext<IEditorRolePreviewContext>({
    selectedRoleIDs: [],
    selectedRoleNames: [],
    setSelectedRoleIDs: () => undefined,
});

export function EditorRolePreviewProvider(props: {
    children: React.ReactNode;
    selectedRoleIDs?: Array<IRole["roleID"]>;
    onSelectedRoleIDsChange?: (roleIDs: Array<IRole["roleID"]>) => void;
    fallback?: React.ReactNode;
}) {
    const [_selectedRoleIDs, _setSelectedRoleIDs] = useSessionStorage("previewRoleIDs", props.selectedRoleIDs ?? []);

    const selectedRoleIDs = props.selectedRoleIDs ?? _selectedRoleIDs;
    const setSelectedRoleIDs = useCallback(
        (roleIDs: Array<IRole["roleID"]>) => {
            _setSelectedRoleIDs(roleIDs);
            props.onSelectedRoleIDsChange?.(roleIDs);
        },
        [props.onSelectedRoleIDsChange],
    );

    const rolesQuery = useQuery({
        queryKey: ["rolesWithPermissions"],
        queryFn: async () => {
            const roles = await apiv2.get<IRole[]>("/roles?expand=permissions");
            return roles.data;
        },
    });

    const selectedRoles = rolesQuery.data?.filter((role) => selectedRoleIDs.includes(role.roleID)) ?? null;

    const fakePermissions = useMemo(() => {
        if (!selectedRoles) {
            return {};
        }

        // We're only working with global permissions
        const finalPermissions: Record<string, boolean> = {};
        for (const role of selectedRoles) {
            for (const permissionSet of role.permissions ?? []) {
                if (permissionSet.type !== "global") {
                    continue;
                }

                for (const [permissionName, value] of Object.entries(permissionSet.permissions)) {
                    // Permissions are additive.
                    finalPermissions[permissionName] = finalPermissions[permissionName] || value;
                }
            }
        }

        return finalPermissions;
    }, [selectedRoles]);

    const fakeUser: IMe | null = useMemo(() => {
        if (!selectedRoles) {
            return null;
        }

        // Use a -1 user if we have permissions, otherwise use a 0 (guest) user.
        const userID = fakePermissions["session.valid"] ? -1 : GUEST_USER_ID;
        const name = selectedRoles.map((role) => role.name).join("+");

        const user: IMe = UserFixture.createMockUser({
            userID,
            name,
            email: `${name}@example.com`,
            countUnreadNotifications: 0,
            countUnreadConversations: 0,
            photoUrl: "",
            dateLastActive: null,
            roles: selectedRoles,
            roleIDs: selectedRoleIDs,
        });
        return user;
    }, [selectedRoleIDs, selectedRoles, fakePermissions]);

    let content: React.ReactNode = <>{props.children}</>;

    const hasPermission = useCallback(
        (permissionName: string) => {
            return fakePermissions[permissionName] === true;
        },
        [fakePermissions],
    );

    if (selectedRoleIDs.length > 0 && fakeUser) {
        if (rolesQuery.data) {
            content = (
                <CurrentUserContextProvider currentUser={fakeUser}>
                    <PermissionsContext.Provider value={{ hasPermission }}>
                        {props.children}
                    </PermissionsContext.Provider>
                </CurrentUserContextProvider>
            );
        } else {
            content = props.fallback ?? <></>;
        }
    }

    return (
        <EditorRolePreviewContext.Provider
            value={{
                selectedRoleIDs,
                setSelectedRoleIDs,
                selectedRoleNames: selectedRoles?.map((role) => role.name) ?? [],
            }}
        >
            {content}
        </EditorRolePreviewContext.Provider>
    );
}

export function useEditorRolePreviewContext() {
    return useContext(EditorRolePreviewContext);
}

export function EditorRolePreviewDropDownItem() {
    const { selectedRoleNames, selectedRoleIDs, setSelectedRoleIDs } = useEditorRolePreviewContext();

    const [modalIsOpen, setModalIsOpen] = useState(false);
    return (
        <DropDownItemButton
            onClick={() => {
                setModalIsOpen(true);
            }}
        >
            <div>
                {t("Preview Roles")}

                <Row style={{ marginTop: 6 }} align={"center"} gap={6} wrap={true}>
                    {selectedRoleNames.map((name, i) => {
                        return <TokenItem key={i}>{name}</TokenItem>;
                    })}
                    {selectedRoleIDs.length === 0 && (
                        <Metas>
                            <MetaItem>{t("Current User")}</MetaItem>
                        </Metas>
                    )}
                </Row>
                {modalIsOpen && (
                    <RolePreviewModal
                        onClose={() => {
                            setModalIsOpen(false);
                        }}
                    />
                )}
            </div>
        </DropDownItemButton>
    );
}

function RolePreviewModal(props: { onClose: () => void }) {
    const context = useEditorRolePreviewContext();

    const [value, setValue] = useState<number[]>(context.selectedRoleIDs);
    return (
        <FramedModal
            onFormSubmit={() => {
                context.setSelectedRoleIDs(value);
                props.onClose();
            }}
            onClose={() => props.onClose()}
            title={t("Role Preview")}
            footer={
                <Button buttonType={"textPrimary"} submit>
                    {t("Preview")}
                </Button>
            }
        >
            <DashboardFormGroup labelType={DashboardLabelType.VERTICAL} label={t("Select Roles")}>
                <DashboardInputWrap>
                    <NestedSelect
                        value={value}
                        onChange={(set) => {
                            if (!set) {
                                setValue([]);
                            }
                            setValue(set as any);
                        }}
                        multiple={true}
                        optionsLookup={{
                            singleUrl: "/roles/%s",
                            searchUrl: "/roles",
                            valueKey: "roleID",
                            labelKey: "name",
                        }}
                        isClearable={true}
                    />
                </DashboardInputWrap>
            </DashboardFormGroup>
        </FramedModal>
    );
}
