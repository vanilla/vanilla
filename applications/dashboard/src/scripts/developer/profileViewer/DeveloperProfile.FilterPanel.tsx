/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IDeveloperProfileDetails } from "@dashboard/developer/profileViewer/DeveloperProfile.types";
import { useDeveloperProfile } from "@dashboard/developer/profileViewer/DeveloperProfile.context";
import CheckBox from "@library/forms/Checkbox";
import { useEffect } from "react";

interface IProps {
    profile: IDeveloperProfileDetails;
}

/**
 * Component rendering details of a single developer profile.
 */
export function DeveloperProfileFilterPanel(props: IProps) {
    const { filteredSpanTypes, setFilteredSpanTypes } = useDeveloperProfile();
    const { timers } = props.profile;
    useEffect(() => {
        if (filteredSpanTypes == null) {
            setFilteredSpanTypes(Object.keys(timers).map(makeTypeFromTimerKey));
        }
    });

    return (
        <>
            <h2>Filters</h2>
            <div>
                {Object.entries(timers)
                    .sort(([keyA, elapsedA], [keyB, elaspedB]) => {
                        return elaspedB - elapsedA;
                    })
                    .map(([key, value]) => {
                        const type = makeTypeFromTimerKey(key);
                        return (
                            <CheckBox
                                labelBold={false}
                                onChange={(e) => {
                                    const isChecked = e.target.checked;
                                    const set = new Set(filteredSpanTypes);
                                    if (isChecked) {
                                        set.add(type);
                                    } else {
                                        set.delete(type);
                                    }
                                    setFilteredSpanTypes(Array.from(set));
                                }}
                                checked={filteredSpanTypes?.includes(type) ?? true}
                                key={key}
                                label={`${type} (${value.toFixed(2)}ms)`}
                            />
                        );
                    })}
            </div>
        </>
    );
}

function makeTypeFromTimerKey(key: string): string {
    return key.replace("_elapsed_ms", "");
}
