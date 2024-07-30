/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { RecordID } from "@vanilla/utils";

export interface AISuggestionsSettings {
    enabled: boolean;
    name: string;
    icon?: string;
    toneOfVoice: "friendly" | "professional" | "technical";
    levelOfTech: "layman" | "intermediate" | "balanced" | "advanced" | "technical";
    useBrEnglish?: boolean;
    sources?: Record<string, AISuggestionSource>;
}

export const INITIAL_AISUGGESTION_SETTINGS: AISuggestionsSettings = {
    enabled: false,
    name: "",
    toneOfVoice: "friendly",
    levelOfTech: "layman",
    useBrEnglish: false,
};

export type AISuggestionsSettingsForm = Omit<AISuggestionsSettings, "sources"> & {
    sources: {
        enabled: string[];
        exclusions: {
            [key: string]: RecordID[];
        };
    };
};

export interface AISuggestionSource {
    enabled: boolean;
    exclusionIDs?: RecordID[];
}

export interface AISuggestionSourceData {
    enabledLabel: string;
    exclusionLabel?: string;
    exclusionChoices?: any;
}
