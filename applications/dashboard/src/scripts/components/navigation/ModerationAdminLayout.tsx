/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import AdminLayout from "@dashboard/components/AdminLayout";
import { ModerationNav } from "@dashboard/components/navigation/ModerationNav";
import { useTitleBarDevice, TitleBarDevices } from "@library/layout/TitleBarContext";
import { useCollisionDetector } from "@vanilla/react-utils";

type IProps = Omit<React.ComponentProps<typeof AdminLayout>, "adminHamburgerContent" | "activeSectionID" | "leftPanel">;

export function ModerationAdminLayout(props: IProps) {
    const device = useTitleBarDevice();
    const { hasCollision } = useCollisionDetector();
    const isCompact = hasCollision || device === TitleBarDevices.COMPACT;

    return (
        <AdminLayout
            adminBarHamburgerContent={<ModerationNav asHamburger />}
            leftPanel={!isCompact && <ModerationNav />}
            {...(props as any)}
        />
    );
}
