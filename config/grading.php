<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Grading scale
    |--------------------------------------------------------------------------
    |
    | Percentage → grade mapping used across report cards and result summaries.
    | Bounds are inclusive (min ≤ percentage ≤ max). Edit this single list to
    | change the grading system school-wide; GradingService reads from here.
    |
    */

    'scale' => [
        ['grade' => 'A1', 'min' => 91, 'max' => 100, 'remark' => 'Outstanding'],
        ['grade' => 'A2', 'min' => 81, 'max' => 90,  'remark' => 'Excellent'],
        ['grade' => 'B1', 'min' => 71, 'max' => 80,  'remark' => 'Very Good'],
        ['grade' => 'B2', 'min' => 61, 'max' => 70,  'remark' => 'Good'],
        ['grade' => 'C1', 'min' => 51, 'max' => 60,  'remark' => 'Fair'],
        ['grade' => 'C2', 'min' => 41, 'max' => 50,  'remark' => 'Average'],
        ['grade' => 'D',  'min' => 33, 'max' => 40,  'remark' => 'Needs Improvement'],
        ['grade' => 'E',  'min' => 0,  'max' => 32,  'remark' => 'Unsatisfactory'],
    ],

    // Minimum percentage to be counted as "Pass" overall.
    'pass_percentage' => 33,
];
