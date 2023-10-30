/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState, useEffect } from "react";
import { IEmailDigestDeliveryDates, ISentDigest } from "@dashboard/emailSettings/types";
import Message from "@library/messages/Message";
import Heading from "@library/layout/Heading";
import { InformationIcon } from "@library/icons/common";
import moment from "moment";
import apiv2 from "@library/apiv2";
import { useQuery } from "@tanstack/react-query";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { t } from "@vanilla/i18n";
import { Table } from "@dashboard/components/Table";
import digestTableStyles from "./DigestTable.styles";

const dateFormat = "ddd MMM Do, YYYY";

export default function DigestSchedule(props: { dayOfWeek: number }) {
    const { dayOfWeek } = props;
    const [upcomingDigestDates, setUpcomingDigestDates] = useState<string>("");
    const [sentDigestDates, setSentDigestDates] = useState<ISentDigest[]>([]);

    const deliveryDatesQuery = useQuery<any, IError, IEmailDigestDeliveryDates>({
        queryFn: async () => {
            const response = await apiv2.get<IEmailDigestDeliveryDates>(
                `/digest/delivery-dates/?dayOfWeek=${dayOfWeek}`,
            );
            return response.data;
        },
        queryKey: ["digestDeliveryDates", { dayOfWeek }],
    });

    useEffect(() => {
        if (deliveryDatesQuery.data?.upcoming) {
            let string = "";
            for (let i = 0; i < 3; i++) {
                string += moment(deliveryDatesQuery.data.upcoming[i]).format(dateFormat) + "; ";
            }
            setUpcomingDigestDates(string);
        }
        if (deliveryDatesQuery.data?.sent && !sentDigestDates.length) {
            setSentDigestDates(deliveryDatesQuery.data.sent.reverse());
        }
    }, [deliveryDatesQuery.data, sentDigestDates]);

    if (deliveryDatesQuery.isLoading) {
        return null;
    }

    return <DigestScheduleImpl upcomingDigestDates={upcomingDigestDates} sentDigestDates={sentDigestDates} />;
}

export function DigestScheduleImpl(props: { upcomingDigestDates: string; sentDigestDates: ISentDigest[] }) {
    const { upcomingDigestDates, sentDigestDates } = props;

    return (
        <>
            <li className="form-group" style={{ background: "#fff", marginTop: -28 }}>
                <div style={{ width: "100%", marginInline: 18 }}>
                    <Message
                        icon={<InformationIcon />}
                        stringContents={`The next three email digest delivery dates: ${upcomingDigestDates}`}
                        type="neutral"
                    />
                </div>
            </li>
            {sentDigestDates.length > 0 && (
                <li className="form-group" style={{ background: "#fff", border: 0 }}>
                    <div style={{ width: "100%", marginInline: 18 }}>
                        <>
                            <Heading depth={5} title={t("History")} style={{ margin: 0 }} />
                            <Table
                                data={Object.values(sentDigestDates).map((sentDigest) => {
                                    return {
                                        dateScheduled: moment(sentDigest.dateScheduled).format(dateFormat),
                                        totalSubscribers:
                                            sentDigest.totalSubscribers &&
                                            `${sentDigest.totalSubscribers} ${t("subscribers")}`,
                                    };
                                })}
                                tableClassNames={digestTableStyles().table}
                            />
                        </>
                    </div>
                </li>
            )}
        </>
    );
}
