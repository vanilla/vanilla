/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import AdminLayout from "@dashboard/components/AdminLayout";
import { StaffNav } from "@dashboard/components/navigation/StaffNav";
import { useTitleBarDevice, TitleBarDevices } from "@library/layout/TitleBarContext";

type IProps = Omit<React.ComponentProps<typeof AdminLayout>, "adminHamburgerContent" | "activeSectionID" | "leftPanel">;

export function StaffAdminLayout(props: IProps) {
    const device = useTitleBarDevice();
    const isCompact = device === TitleBarDevices.COMPACT;

    return (
        <AdminLayout
            adminBarHamburgerContent={<StaffNav asHamburger />}
            leftPanel={!isCompact && <StaffNav />}
            {...(props as any)}
        />
    );
}
