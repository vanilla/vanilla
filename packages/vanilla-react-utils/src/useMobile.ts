/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import debounce from "lodash-es/debounce";
import { useCallback, useEffect, useState } from "react";

interface IUseMobileOverrides {
    portraitWidth?: number;
    landscapeWidth?: number;
}

/**
 * Used to determine if the current device is a mobile device by orientation and screen size.
 * @param overrides Optional overrides for the portrait and landscape width.
 * @param overrides.portraitWidth The width of the screen when in portrait mode.
 * @param overrides.landscapeWidth The width of the screen when in landscape mode.
 * @default overrides.portraitWidth 600
 * @default overrides.landscapeWidth 932
 * @returns True if the device is a mobile device.
 */
export function useMobile(overrides?: IUseMobileOverrides) {
    const [isMobile, setIsMobile] = useState(false);

    const orientationCheck = window.matchMedia(`(orientation: portrait)`);
    const portraitSizeCheck = window.matchMedia(`(max-width: ${overrides?.portraitWidth ?? 600}px)`);
    const landscapeSizeCheck = window.matchMedia(`(max-width: ${overrides?.landscapeWidth ?? 932}px)`);

    const handleOrientationChange = useCallback(
        debounce((event: MediaQueryListEvent) => {
            // We are portrait
            if (event.matches) {
                setIsMobile(portraitSizeCheck.matches);
            }
            // We are landscape
            else {
                setIsMobile(landscapeSizeCheck.matches);
            }
        }, 100),
        [],
    );

    const initialOrientation = () => {
        if (orientationCheck.matches) {
            setIsMobile(portraitSizeCheck.matches);
        } else {
            setIsMobile(landscapeSizeCheck.matches);
        }
    };

    useEffect(() => {
        orientationCheck.addEventListener("change", handleOrientationChange);

        initialOrientation();

        return () => {
            orientationCheck.removeEventListener("change", handleOrientationChange);
        };
    }, []);

    return isMobile;
}
