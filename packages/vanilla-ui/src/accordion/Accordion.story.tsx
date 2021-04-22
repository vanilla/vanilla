/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { Accordion, AccordionHeader, AccordionItem, AccordionPanel } from "./";

export default {
    title: "Vanilla UI/Accordion",
};

const ExampleContent = [
    "Your mother was a hamster and your father smelt of elderberries! Now leave before I am forced to taunt you a second time! A newt? Why? Why do you think that she is a witch? Ni! Ni! Ni! Ni! Listen. Strange women lying in ponds distributing swords is no basis for a system of government. Supreme executive power derives from a mandate from the masses, not from some farcical aquatic ceremony. Now, look here, my good man. Well, I didn't vote for you. I don't want to talk to you no more, you empty-headed animal food trough water! I fart in your general direction! Your mother was a hamster and your father smelt of elderberries! Now leave before I am forced to taunt you a second time! Oh! This is a false one. Camelot! How do you know she is a witch? No, no, no! Yes, yes. A bit. But she's    got a wart. Oh! Come and see the violence inherent in the system! Help, help, I'm being repressed! I am your king. Bloody Peasant! Shut up! Knights of Ni, we are but simple travelers who seek the enchanter who lives beyond these woods. Bring her forward! And the hat. She's a witch! You don't vote for kings. Did you dress her up like this? Shut up! Will you shut up?! Ni! Ni! Ni! Ni!",
    "Bloody Peasant! How do you know she is a witch? She looks like one. Well, I didn't vote for you. Well, how'd you become king, then? And this isn't my nose. This is a false one. Camelot! How do you know she is a witch? No, no, no! Yes, yes. A bit. But she's got a wart. Oh! Come and see the violence inherent in the system! Help, help, I'm being repressed! I am your king. Bloody Peasant! Shut up! Knights of Ni, we are but simple travelers who seek the enchanter who lives beyond these woods. Bring her forward! And the hat. She's a witch! You don't vote for kings. Did you dress her up like this? Shut up! Will you shut up?! Ni! Ni! Ni! Ni!",
    "Bring her forward! Shut up! Will you shut up?! How do you know she is a witch? Listen. Strange women lying in ponds distributing swords is no basis for a system of government. Supreme executive power derives from a mandate from the masses, not from some farcical aquatic ceremony.",
];

export function Default() {
    return (
        <Accordion collapsible>
            <AccordionItem>
                <AccordionHeader arrow>Item 1</AccordionHeader>
                <AccordionPanel>{ExampleContent[0]}</AccordionPanel>
            </AccordionItem>
            <AccordionItem>
                <AccordionHeader arrow>Item 2</AccordionHeader>
                <AccordionPanel>{ExampleContent[1]}</AccordionPanel>
            </AccordionItem>
            <AccordionItem>
                <AccordionHeader arrow>Item 3</AccordionHeader>
                <AccordionPanel>{ExampleContent[2]}</AccordionPanel>
            </AccordionItem>
        </Accordion>
    );
}
export function Multiple() {
    return (
        <Accordion multiple collapsible expandAll>
            <AccordionItem>
                <AccordionHeader arrow>Item 1</AccordionHeader>
                <AccordionPanel>{ExampleContent[0]}</AccordionPanel>
            </AccordionItem>
            <AccordionItem>
                <AccordionHeader arrow>Item 2</AccordionHeader>
                <AccordionPanel>{ExampleContent[1]}</AccordionPanel>
            </AccordionItem>
            <AccordionItem>
                <AccordionHeader arrow>Item 3</AccordionHeader>
                <AccordionPanel>{ExampleContent[2]}</AccordionPanel>
            </AccordionItem>
        </Accordion>
    );
}
