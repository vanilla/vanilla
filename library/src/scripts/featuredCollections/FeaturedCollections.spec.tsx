/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render, screen } from "@testing-library/react";
import { FeaturedCollections } from "@library/featuredCollections/FeaturedCollections";
import { fakeCollection } from "@library/featuredCollections/FeaturedCollections.story";

describe("Featured Collection", () => {
    it("Render title, subtitle, and description", () => {
        render(<FeaturedCollections title="Title" subtitle="Subtitle" description="Description" />);
        expect(screen.getByText("Title")).toBeInTheDocument();
        expect(screen.getByText("Subtitle")).toBeInTheDocument();
        expect(screen.getByText("Description")).toBeInTheDocument();
    });

    it("Render records", () => {
        render(<FeaturedCollections collection={fakeCollection} />);
        fakeCollection.records.forEach((record) => {
            if (record.record?.name) {
                expect(screen.getByText(record.record.name)).toBeInTheDocument();
            }
        });
    });

    it("Render featured images", () => {
        const { container } = render(
            <FeaturedCollections collection={fakeCollection} options={{ featuredImage: { display: true } }} />,
        );
        const images = container.querySelectorAll("svg");
        expect(images.length).toEqual(fakeCollection.records.length);
    });
});
