/*
 * @author Carla França <cfranca@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import React from "react";
import { carouselClasses } from "@library/carousel/Carousel.style";
import { t } from "@library/utility/appUtils";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";

interface IProps {
    slideActiveIndex: number;
    setActiveIndex: (e: React.MouseEvent<HTMLButtonElement>) => void;
    numbSlidesToShow: number;
    numberOfDots: number[];
}

export function CarouselPaging(props: IProps) {
    const classes = carouselClasses();
    const { numberOfDots, numbSlidesToShow, slideActiveIndex, setActiveIndex } = props;
    const pagingActiveIndex: number = Math.ceil(slideActiveIndex / numbSlidesToShow);

    if (numberOfDots.length === 1) return null;

    return (
        <ol className={classes.dotWrapper}>
            {numberOfDots.map((_, idx) => {
                return (
                    <li key={idx}>
                        <Button
                            buttonType={ButtonTypes.ICON}
                            data-idx={idx}
                            disabled={pagingActiveIndex === idx}
                            className={`${pagingActiveIndex === idx ? "active" : ""} ${classes.dotBt} `}
                            onClick={setActiveIndex}
                        >
                            <ScreenReaderContent>
                                <span>{t(`indicator navigation`)}</span>
                            </ScreenReaderContent>
                            <span aria-hidden="true" className={classes.dot}></span>
                        </Button>
                    </li>
                );
            })}
        </ol>
    );
}
