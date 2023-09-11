/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import UserContent from "@library/content/UserContent";
import React from "react";
import { STORY_CONTENT_RICH, STORY_CONTENT_LEGACY, STORY_CONTENT_TABLES } from "@library/content/UserContent.storyData";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { legacyCssDecorator } from "@dashboard/__tests__/legacyCssDecorator";
import { TableStyle } from "@library/content/UserContent.variables";

export default {
    title: "User Content/Content",
};

export function CodeblockPython() {
    return (
        <UserContent
            content={`<pre class="code codeBlock" spellcheck="false">
/*
Runtime: 2 ms, faster than 100.00% of Java online submissions for Median of Two Sorted Arrays.
Memory Usage: 43 MB, less than 98.54% of Java online submissions for Median of Two Sorted Arrays.
Approach 2: Optimized TC: O(logn) SC: O(1) Solution Using Binary Search
    1. We Use Binary Search To Create Partitions In Both The Sorted Arrays
    2. We Check If The Left And Rights Of The Partition Satisfies The Conditions Which Are Written In Comments Below.
*/
def findMedianSortedArrays(self, A, B):
    l = len(A) + len(B)
    if l % 2 == 1:
        return self.kth(A, B, l // 2)
    else:
        return (self.kth(A, B, l // 2) + self.kth(A, B, l // 2 - 1)) / 2.
def kth(self, a, b, k):
    if not a:
        return b[k]
    if not b:
        return a[k]
    ia, ib = len(a) // 2 , len(b) // 2
    ma, mb = a[ia], b[ib]
    # when k is bigger than the sum of a and b's median indices
    if ia + ib < k:
        # if a's median is bigger than b's, b's first half doesn't include k
        if ma > mb:
            return self.kth(a, b[ib + 1:], k - ib - 1)
        else:
            return self.kth(a[ia + 1:], b, k - ia - 1)
    # when k is smaller than the sum of a and b's indices
    else:
        # if a's median is bigger than b's, a's second half doesn't include k
        if ma > mb:
            return self.kth(a[:ia], b, k)
        else:
            return self.kth(a, b[:ib], k)
            </pre>`}
        />
    );
}

export function CodeblockSwift() {
    return (
        <UserContent
            content={`<pre class="code codeBlock" spellcheck="false">
/*
Runtime: 2 ms, faster than 100.00% of Java online submissions for Median of Two Sorted Arrays.
Memory Usage: 43 MB, less than 98.54% of Java online submissions for Median of Two Sorted Arrays.
Approach 2: Optimized TC: O(logn) SC: O(1) Solution Using Binary Search
    1. We Use Binary Search To Create Partitions In Both The Sorted Arrays
    2. We Check If The Left And Rights Of The Partition Satisfies The Conditions Which Are Written In Comments Below.
*/
class Solution {
    func findMedianSortedArrays(_ nums1: [Int], _ nums2: [Int]) -> Double {
        let totalNumbers = nums1.count + nums2.count
        guard totalNumbers>0 else {return 0.0}
        var sortedNumber: [Int] = []
        var nums1 = nums1
        var nums2 = nums2
        for i in 0...totalNumbers{
            if let num1 = nums1.first , let num2 = nums2.first{
                if num1 <= num2{
                    sortedNumber.append(num1)
                    nums1.removeFirst()
                }else{
                    sortedNumber.append(num2)
                    nums2.removeFirst()
                }
            }else if let num1 = nums1.first{
                sortedNumber.append(num1)
                nums1.removeFirst()
            }else if let num2 = nums2.first{
                sortedNumber.append(num2)
                nums2.removeFirst()
            }
        }
        var median: Double = 0.0
        let x = totalNumbers/2 //normal mid index
        if totalNumbers%2 == 0{
            let midX = sortedNumber[x]
            let midY = sortedNumber[x-1]
            median = (Double(midX) + Double(midY)) / 2
        }else{
            median = Double(sortedNumber[x])
        }
        return median
    }
}
            </pre>`}
        />
    );
}

export function CodeblockJava() {
    return (
        <UserContent
            content={`<pre class="code codeBlock" spellcheck="false">
/*
Runtime: 2 ms, faster than 100.00% of Java online submissions for Median of Two Sorted Arrays.
Memory Usage: 43 MB, less than 98.54% of Java online submissions for Median of Two Sorted Arrays.
Approach 2: Optimized TC: O(logn) SC: O(1) Solution Using Binary Search
    1. We Use Binary Search To Create Partitions In Both The Sorted Arrays
    2. We Check If The Left And Rights Of The Partition Satisfies The Conditions Which Are Written In Comments Below.
*/
class Solution {
    public double findMedianSortedArrays(int[] nums1, int[] nums2) {
        //Calling The Function Again With nums1 As The Smaller Size Array
        if(nums1.length > nums2.length){
            return findMedianSortedArrays(nums2, nums1);
        }
        // Applying Binary Search To The 2 Arrays
        int nums1Length = nums1.length;
        int nums2Length = nums2.length;
        int start = 0;
        int end = nums1Length;
        while(start <= end){
            int partition1 = start + (end-start)/2; // Mid Partition In The Smaller Size Array
            int partition2 = (nums1Length + nums2Length + 1)/2 - partition1; // Mid Partition In The Larger Size Array
            // Taking Left And Right Values Of The Partition Of Both The Arrays And Cross Checking Them
            int left1 = (partition1 > 0)? nums1[partition1 - 1] : Integer.MIN_VALUE;
            int left2 = (partition2 > 0)? nums2[partition2 - 1] : Integer.MIN_VALUE;
            int right1 = (partition1 < nums1Length)? nums1[partition1] : Integer.MAX_VALUE;
            int right2 = (partition2 < nums2Length)? nums2[partition2] : Integer.MAX_VALUE;
            /*
            If Left Value Of The First Array Is Smaller Than Right Value Of The Second Array
                                               And
            Left Value Of The Second Array Is Smaller Than Right Value Of The First Array
                                              Then
            We Check If The Size Of The Sum Of Length Of Both Arrays Is Odd Or Even Because
            If Odd, We Return The Max Of Left1 And Left2, And If Even We Return The Average
            Of (Max(left1, left2) + Min(right1, right2)) / 2.0 as per the Average Formula.
            */
            if(left1 <= right2 && left2 <= right1){
                if((nums1Length + nums2Length) % 2 == 0){
                    return (Math.max(left1, left2) + Math.min(right1, right2)) / 2.0;
                }
                else{
                    return Math.max(left1, left2);
                }
            }
            else if(left1 > right2){    // Base Binary Search Condition
                end = partition1 - 1;
            }
            else{
                start = partition1 + 1; // Base Binary Search Condition
            }
        }
        // Default Return Input
        return 0.0;
    }
}
        </pre>`}
        />
    );
}

export function CSS() {
    return (
        <UserContent
            content={`<pre class="code codeBlock" spellcheck="false">
.userContent div.Spoiler div.SpoilerText,.UserContent div.Spoiler div.SpoilerText{
    border-left-width:0;
    margin:0;
    padding:0 14px 14px !important;
}
.userContent .codeBlock,.UserContent .codeBlock,.userContent code,.UserContent code,.userContent pre,.UserContent pre{
    border:0;
    font-family:Menlo,Monaco,Consolas,Courier New,monospace;
    font-size:.85em;
    margin:0;
    vertical-align:middle
}
.userContent pre,.UserContent pre,.userContent pre.codeBlock,.UserContent pre.codeBlock{
    -ms-flex-negative:0;
    background-color:#f7f7f8;
    color:#2a2f37;
    display:block;
    flex-shrink:0;
    max-width:100%;
    overflow-x:auto;
    padding:14px;
    position:relative
}
.userContent .codeBlock,.UserContent .codeBlock,.userContent code,.UserContent code{
    background-color:rgba(0,0,0,0);
    color:inherit;
    display:inline;
    padding:0
}
.userContent p .codeBlock,.UserContent p .codeBlock,.userContent p code,.UserContent p code{
    background-color:#f7f7f8;
}
            </pre>`}
        />
    );
}

export function Rich() {
    return <UserContent content={STORY_CONTENT_RICH} />;
}

export const Legacy = storyWithConfig({}, () => {
    return <UserContent content={STORY_CONTENT_LEGACY} />;
});

Legacy.decorators = [legacyCssDecorator];

function makeTableStory(tableStyle: TableStyle) {
    const storyFn = storyWithConfig(
        {
            themeVars: {
                userContent: {
                    tables: {
                        style: tableStyle,
                    },
                },
            },
        },
        () => {
            return <UserContent content={STORY_CONTENT_TABLES} />;
        },
    );
    storyFn.parameters = {
        chromatic: {
            viewports: [1200, 500],
        },
    };
    return storyFn;
}

export const TableHorizontal = makeTableStory(TableStyle.HORIZONTAL_BORDER);
export const TableHorizontalStriped = makeTableStory(TableStyle.HORIZONTAL_BORDER_STRIPED);
export const TableVertical = makeTableStory(TableStyle.VERTICAL_BORDER);
export const TableVerticalStriped = makeTableStory(TableStyle.VERTICAL_BORDER_STRIPED);
