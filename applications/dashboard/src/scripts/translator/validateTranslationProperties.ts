import { ITranslationProperty } from "@vanilla/i18n";

export function validateTranslationProperties(properties: ITranslationProperty[]) {
    const recordTypes = properties.map((prop) => prop.recordType);
    const set = new Set(recordTypes);
    if (set.size > 1) {
        const joined = Array.from(set).join(", ");
        throw new Error("<ContentTranslator /> can only work with 1 recordType at a time. Got mulitple " + joined);
    }
}
