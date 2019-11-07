/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState } from "react";
import { IContentTranslatorProps, useLocaleInfo, TranslationPropertyType } from "@vanilla/i18n";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import Modal from "@library/modal/Modal";
import { TranslationGrid } from "@library/content/translationGrid/TranslationGrid";

export const ContentTranslator: React.FC<IContentTranslatorProps> = props => {
    let [displayModal, setDisplayModal] = useState(false);
    const { locales } = useLocaleInfo();

    return (
        <>
            <Button baseClass={ButtonTypes.ICON} onClick={() => setDisplayModal(true)}>
                <TranslateIcon />
            </Button>
            {displayModal && (
                <Modal titleID="" exitHandler={() => setDisplayModal(false)}>
                    <TranslationGrid
                        properties={[
                            {
                                resource: "kb",
                                sourceText: "Hello world!",
                                propertyKey: "test.test.test",
                                recordType: "knowledge-base",
                                recordID: 1,
                                property: "test",
                                propertyType: TranslationPropertyType.TEXT,
                                propertyValidation: {},
                            },
                            {
                                resource: "kb",
                                sourceText: "Hello world 2!",
                                propertyKey: "test.test.2",
                                recordType: "knowledge-base",
                                recordID: 1,
                                property: "test",
                                propertyType: TranslationPropertyType.TEXT_MULTILINE,
                                propertyValidation: {},
                            },
                        ]}
                        existingTranslations={{}}
                        otherLanguages={[]}
                    />
                </Modal>
            )}
        </>
    );
};

export function TranslateIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
            <g fill="none" fillRule="evenodd">
                <path
                    fill="currentColor"
                    fillRule="nonzero"
                    d="M9.836 13.2l-.455-1.495H7.09L6.635 13.2H5.2l2.217-6.308h1.629L11.27 13.2H9.836zm-.773-2.612a442.299 442.299 0 0 1-.711-2.3 6.489 6.489 0 0 1-.114-.42c-.095.366-.365 1.273-.812 2.72h1.637zM18.535 12.214c.257 0 .465-.223.465-.5 0-.276-.208-.5-.465-.5h-2.07V10c0-.276-.208-.5-.465-.5s-.465.224-.465.5v1.214h-1.9c-.256 0-.088.22 0 .5.09.28-.002.5.255.5h.216a4.937 4.937 0 0 0 1.164 2.75c-.528.34.31.329-.35.329-.256 0 0 .077 0 .353 0 .276.093.508.35.508.614 0 .01.03.73-.508.72.538 1.593.854 2.535.854.257 0 .465-.224.465-.5s-.208-.5-.465-.5c-.66 0-1.277-.196-1.805-.536a4.937 4.937 0 0 0 1.164-2.75h.64zM16 14.34a3.895 3.895 0 0 1-.956-2.125h1.912A3.894 3.894 0 0 1 16 14.34z"
                />
                <path
                    stroke="currentColor"
                    strokeWidth="1.2"
                    d="M11.271 2.709H4.017c-.945 0-1.417.633-1.417 1.9v11.836c0 1.05.578 1.575 1.733 1.575H16.1L11.271 2.709z"
                />
                <path
                    stroke="currentColor"
                    strokeWidth="1.2"
                    d="M12.348 6.116h7.702c.875 0 1.312.411 1.312 1.234v12.697c0 .98-.437 1.47-1.312 1.47h-6.415L12 18.02h4.1L12.348 6.116zM13.635 21.517l2.817-3.287"
                />
            </g>
        </svg>
    );
}
