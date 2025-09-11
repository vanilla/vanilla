/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import AdminLayout from "@dashboard/components/AdminLayout";
import { AppearanceNav } from "@dashboard/components/navigation/AppearanceNav";
import { useTitleBarDevice, TitleBarDevices } from "@library/layout/TitleBarContext";

type IProps = Omit<
    React.ComponentProps<typeof AdminLayout>,
    "adminBarHamburgerContent" | "activeSectionID" | "leftPanel"
>;

export function AppearanceAdminLayout(props: IProps) {
    const device = useTitleBarDevice();
    const isCompact = device === TitleBarDevices.COMPACT;

    return (
        <AdminLayout
            adminBarHamburgerContent={<AppearanceNav asHamburger />}
            leftPanel={!isCompact && <AppearanceNav />}
            {...(props as any)}
        />
    );
}
