/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { translationReducer, ITranslationState, INITIAL_TRANSLATION_STATE } from "./translationReducer";
import { TranslationActions, ITranslationApiItem } from "./TranslationActions";
import { LoadStatus } from "@library/@types/api/core";
import { TranslationPropertyType } from "@vanilla/i18n";

describe("translationReducer", () => {
    it("can do partial updates when updating translations", () => {
        const item1 = dummyTranslationItem({ translationPropertyKey: "key1" });
        const item2 = dummyTranslationItem({ translationPropertyKey: "key2" });
        const initial: ITranslationState = {
            ...INITIAL_TRANSLATION_STATE,
            translationsByLocale: {
                en: {
                    status: LoadStatus.SUCCESS,
                    data: {
                        [item1.translationPropertyKey]: item1,
                    },
                },
            },
        };

        const action = TranslationActions.patchTranslationsACs.done({
            params: [item2],
            result: {},
        });

        const expectedData = {
            [item1.translationPropertyKey]: item1,
            [item2.translationPropertyKey]: item2,
        };

        const actual = translationReducer(initial, action);
        expect(actual.translationsByLocale["en"].data).toEqual(expectedData);

        const updated2 = {
            ...item2,
            translation: "test translation UPDATED",
        };

        const updateAction = TranslationActions.patchTranslationsACs.done({
            params: [updated2],
            result: {},
        });

        const updatedActual = translationReducer(initial, updateAction);
        const expectedUpdatedData = {
            [item1.translationPropertyKey]: item1,
            [updated2.translationPropertyKey]: updated2,
        };
        expect(updatedActual.translationsByLocale["en"].data).toEqual(expectedUpdatedData);
    });
});

function dummyTranslationItem(overrides: Partial<ITranslationApiItem> = {}): ITranslationApiItem {
    return {
        locale: "en",
        propertyName: "testProp",
        propertyType: TranslationPropertyType.TEXT,
        translation: "test translation",
        recordType: "testRecordType",
        recordID: 0,
        translationPropertyKey: "testRecordType.0.testProp",
        ...overrides,
    };
}
