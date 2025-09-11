/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import AdminLayout from "@dashboard/components/AdminLayout";
import { SettingsNav } from "@dashboard/components/navigation/SettingsNav";
import { useTitleBarDevice, TitleBarDevices } from "@library/layout/TitleBarContext";

type IProps = Omit<React.ComponentProps<typeof AdminLayout>, "adminHamburgerContent" | "activeSectionID" | "leftPanel">;

export function SettingsAdminLayout(props: IProps) {
    const device = useTitleBarDevice();
    const isCompact = device === TitleBarDevices.COMPACT;

    return (
        <AdminLayout
            adminBarHamburgerContent={<SettingsNav asHamburger />}
            leftPanel={!isCompact && <SettingsNav />}
            {...(props as any)}
        />
    );
}
