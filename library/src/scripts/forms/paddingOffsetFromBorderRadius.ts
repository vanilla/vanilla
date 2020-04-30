import { formElementsVariables } from "@library/forms/formElementStyles";

export interface IPaddingBasedOnRadius {
    radius?: number | string; // pixel value in integer or percentage in string
    extraPadding?: number | string; // If undefined, return 0 for both sides
    height?: number; // Min height of element
    side?: "horizontal" | "left" | "right"; // defaults to horizontal
    debug?: boolean;
}

export const paddingOffsetBasedOnBorderRadius = (props: IPaddingBasedOnRadius) => {
    let leftOffset = 0;
    let rightOffset = 0;
    const height = props.height ?? formElementsVariables().sizing.height;
    const debug = props.debug;

    if (props && props.radius && props.extraPadding) {
        const maxValue = parseInt(props.extraPadding.toString());
        const rawRadius = props.radius.toString().trim();
        const workingBorderRadius = parseFloat(rawRadius);
        const halfHeight = height / 2;

        let finalBorderRadiusRatio = 0;
        if (rawRadius.endsWith("%")) {
            const percent = Math.min(50, workingBorderRadius); // you can't go over 50% anyways.
            finalBorderRadiusRatio = percent * height;
        } else {
            //assume pixels, we don't currently support "ems" or any other units.
            finalBorderRadiusRatio =
                (Math.min(halfHeight, parseInt(workingBorderRadius.toString())) / halfHeight) * 100; // anything above have half the height is too much
        }

        const offset = (finalBorderRadiusRatio / 100) * maxValue;
        if (props.side !== "right") {
            leftOffset = offset;
        }
        if (props.side !== "left") {
            rightOffset = offset;
        }
    }

    const result = {
        left: leftOffset,
        right: rightOffset,
    };

    return result;
};
