<?php

namespace App\Helpers;

class Constants
{

    const USER_TYPE_STUDENT = 0;
    const USER_TYPE_TEACHER = 2;
    const USER_TYPE_ADMIN = 1;
    const USER_TYPE_SUPER_ADMIN = 3;
    const USER_TYPE_ACCOUNTS = 4;
    // const USER_TYPE_CAFE = 5;
    const ROLE = [
        0 => 'user',
        1 => 'admin',
        2 => 'teacher',
        3 => 'super-admin',
        4 => 'accounts',
    ];

    const ROLEVALUE = [
        'admin' => 'admin',
        'sub-admin' => 'admin',
        'teacher' => 'teacher',
        'super-admin' => 'super-admin',
        'sub-super-admin' => 'super-admin',
        'accounts' => 'accounts',
    ];
    const MALE = "male";
    const FEMALE = "female";
    const OTHER = "other";

    const GENDER =
    [
        self::MALE => "male",
        self::FEMALE => "female",
        self::OTHER => "other",
    ];

    // Select Class
    const FOURTH = "4th";
    const FIVFTH = "5th";
    const SIXTH = "6th";
    const SEVENTH = "7th";
    const EIGHTH = "8th";
    const NINTH = "9th";
    const TENTH = "10th";
    const ELEVENTH = "11th";
    const TWELVTH = "12th";

    const STANDARD =
    [
        self::FOURTH => "4th",
        self::FIVFTH => "5th",
        self::SIXTH => "6th",
        self::SEVENTH => "7th",
        self::EIGHTH => "8th",
        self::NINTH => "9th",
        self::TENTH => "10th",
        self::ELEVENTH => "11th",
        self::TWELVTH => "12th",
    ];

    //Select Course Level
    const EASY = "Easy";
    const MEDIUUM = "Medium";
    const HARD = "Hard";

    const COURSE_LEVEL =
    [
        self::EASY => "Easy",
        self::MEDIUUM => "Medium",
        self::HARD => "Hard"
    ];

    //Select Language
    const HINDI = "Hindi";
    const ENGLISH = "English";
    const HINGLISH = "Hinglish";

    const LANGUAGE =
    [
        self::HINDI => "hindi",
        self::ENGLISH => "english",
        self::HINGLISH => "hinglish"
    ];

    const PAYMENT_STATUS = [
        0 => "pending",
        1 => "completed",
        2 => "rejected",
    ];

    const STUDENT = "STUDENT";
    const TEACHER = "TEACHER";
    const DISTRIBUTOR = "DISTRIBUTOR";
    const COURSE = "COURSE";
    const BATCH = "BATCH";
    const PAYMENT = "PAYMENT";
    const REFERRAL = "REFERRAL";
    const REFUNDS = "REFUNDS";
    const PAYOUTS = "PAYOUTS";
    const SCHEDULE = "SCHEDULE";
    const CONTENT = "CONTENT";
    const DOUBTS = "DOUBTS";
    const FEATURES = "FEATURES";
    const PLATFORM = "PLATFORM";

    const BOARD = [
        'CBSE',
        'ICSE',
        'IB',
        'State Board',
        'IGCSE',
        'NIOS',
        'Other'
    ];

    const LIBRARY_CATEGORIES = [
        'fiction' => 'Fiction',
        'non_fiction' => 'Non-Fiction',
        'reference' => 'Reference',
        'magazine' => 'Magazine',
        'comics' => 'Comics',
        'other' => 'Other'
    ];

    const LIBRARY_TYPES = [
        'book' => 'Book',
        'journal' => 'Journal',
        'thesis' => 'Thesis',
        'ebook' => 'E-book',
        'other' => 'Other'
    ];

    const LIBRARY_AVAILABILITY = [
        'available' => 'Available',
        'checked_out' => 'Checked Out',
        'lost' => 'Lost',
        'reserved' => 'Reserved'
    ];

    const LANGUAGES = [
        'Hindi' => 'Hindi',
        'English' => 'English',
        'Sanskrit' => 'Sanskrit'
    ];
}
