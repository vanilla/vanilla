/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/utility/appUtils";

interface IHumanFileSize {
    amount: number;
    unit: string;
    unitAbbr: string;
}

enum FileSizeUnit {
    B = "B",
    KB = "KB",
    MB = "MB",
    GB = "GB",
    TB = "TB",
}

/**
 * Parse a number of bytes into pieces of a human readable file size.
 *
 * @param size File size in bytes
 */
export function humanFileSize(size: number): IHumanFileSize {
    const i: number = Math.floor(Math.log(size) / Math.log(1024));
    const unitAbbr = Object.values(FileSizeUnit)[i];

    const fullValue: number = size / Math.pow(1024, i);
    // 2 digits
    const value = Number.parseFloat(fullValue.toFixed(2));
    const unit = getUnabbreviatedFileSizeUnit(unitAbbr);
    return {
        amount: value,
        unit,
        unitAbbr,
    };
}

/**
 * Component for rendering the results of `humanFileSize`.
 */
export function HumanFileSize(props: { numBytes: number }) {
    const humanSize = humanFileSize(props.numBytes);
    return (
        <>
            {humanSize.amount}
            <abbr title={humanSize.unit}>{` ${humanSize.unitAbbr}`}</abbr>
        </>
    );
}

/**
 * Convert from FileSizeUnit to the unabbreviated version.
 *
 * @param unit The unit to translate.
 */
function getUnabbreviatedFileSizeUnit(unit: FileSizeUnit): string {
    switch (unit) {
        case FileSizeUnit.B:
            return t("Byte");
        case FileSizeUnit.KB:
            return t("Kilobyte");
        case FileSizeUnit.MB:
            return t("Megabyte");
        case FileSizeUnit.GB:
            return t("Gigabyte");
        case FileSizeUnit.TB:
            return t("Terabyte");
    }
}
