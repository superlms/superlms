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

    // Ordered high → low. A percentage lands in the first band it reaches, so
    // the maxes are what the grading key prints and the mins are what decide.
    'scale' => [
        ['grade' => 'O',  'min' => 91, 'max' => 100, 'remark' => 'Outstanding'],
        ['grade' => 'A+', 'min' => 81, 'max' => 90,  'remark' => 'Excellent'],
        ['grade' => 'A',  'min' => 71, 'max' => 80,  'remark' => 'Very Good'],
        ['grade' => 'B',  'min' => 61, 'max' => 70,  'remark' => 'Good'],
        ['grade' => 'C',  'min' => 51, 'max' => 60,  'remark' => 'Fair'],
        ['grade' => 'D',  'min' => 41, 'max' => 50,  'remark' => 'Average'],
        ['grade' => 'P',  'min' => 35, 'max' => 40,  'remark' => 'Pass'],
        ['grade' => 'F',  'min' => 0,  'max' => 34,  'remark' => 'Fail'],
    ],

    // Minimum percentage to be counted as "Pass" overall.
    'pass_percentage' => 35,
];
