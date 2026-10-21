<?php

namespace App\Support;

use App\Models\SchoolWebsite;

/**
 * Sample content for a school's whole website — every page of the Classic
 * template filled the way a real CBSE school would fill it — so the demo
 * school's site can be seen with real-looking data (lms:seed-demo-website).
 *
 * Photos are Unsplash images (free to use, served from Unsplash's CDN); the
 * people on the desks and the team are made up and their photos are stock.
 * Nothing here is read by a school's site unless the command writes it.
 */
class DemoWebsiteContent
{
    /** Unsplash photo ids, by what they show. */
    private const PHOTOS = [
        'building'      => '1592066575517-58df903152f2',
        'campus'        => '1591123120675-6f7f1aae0e5b',
        'block'         => '1613896527026-f195d5c818ed',
        'brick'         => '1584750153892-38414eb8e76a',
        'classroom'     => '1709290749293-c6152a187b14',
        'kids_class'    => '1692269725836-fbd72e98883f',
        'girls_desk'    => '1692269725911-87697c558be1',
        'boys_desk'     => '1692269725827-699e04a11cdf',
        'boys_books'    => '1692269726060-9c604e06f63b',
        'class_desks'   => '1636202339022-7d67f7447e3a',
        'girl_uniform'  => '1572847748080-bac263fae977',
        'boys_uniform'  => '1569173675610-42c361a86e37',
        'seniors'       => '1698993081947-8a3654303904',
        'girls_class'   => '1573894998033-c0cef4ed722b',
        'assembly'      => '1729284440498-19b2295ac7bb',
        'kids_group'    => '1524069290683-0457abfe42c3',
        'kids_floor'    => '1542810634-71277d95dcbb',
        'teacher_board' => '1511629091441-ee46146481b6',
        'empty_class'   => '1580582932707-520aed937b7b',
        'reading'       => '1623303366639-0e330d7c3d9f',
        'lab_kids'      => '1758685734153-132c8620c1bd',
        'microscope'    => '1780265596033-47b8cb06c794',
        'lab_girl'      => '1633828763399-e29f1cd3f4c1',
        'lab_bench'     => '1602052577122-f73b9710adba',
        'lab_teacher'   => '1758685734030-a31d96462eec',
        'library'       => '1564981797816-1043664bf78d',
        'library2'      => '1758885698366-47d44c6ec6ba',
        'library_boy'   => '1540151812223-c30b3fab58e6',
        'computer_lab'  => '1719159381981-1327b22aff9b',
        'computers'     => '1569653402334-2e98fbaa80ee',
        'yoga'          => '1649008726820-d90aeb70c32e',
        'track'         => '1700914299961-d8f91559d85d',
        'football'      => '1789215724846-e8b8b44cb860',
        'field_day'     => '1791034347509-6ef18fde9702',
        'girls_game'    => '1783504080269-88e35679d436',
        'hoops'         => '1763639700458-38a0fd25335d',
        'ball_game'     => '1502086223501-7ea6ecd79368',
        'ground'        => '1776659960083-4b31bf309f28',
        'buses'         => '1689973849511-e1995810cf7a',
        'bus'           => '1503676685182-2531a01b5b5c',
        'art'           => '1780751987564-79489283d67b',
        'watercolour'   => '1536221993589-9edbbca2c7fc',
        'drawings'      => '1761403942462-04b8b97f1fe9',
        'dance'         => '1764014792668-bc484714744f',
        'dance_group'   => '1774425243355-a6c4858a5e63',
        'music'         => '1788882680471-86ba7f651dac',
        'piano'         => '1769097542113-49f995910fc5',
        'graduation'    => '1785052335692-ee4d5aff9903',
        'prizes'        => '1773212902287-94f6df597e6d',
    ];

    /** Stock portraits for the made-up people. */
    private const PEOPLE = [
        'chairman'   => '1758685734463-98672a67dd1c',
        'founder'    => '1582259181740-fcf033def7c8',
        'cofounder'  => '1679138118375-47f78db3761d',
        'md'         => '1649433658557-54cf58577c68',
        'ed'         => '1758518729459-235dcaadc611',
        'director'   => '1601655781320-205e34c94eb1',
        'manager'    => '1728015401182-1c715f133d5d',
        'principal'  => '1758518729466-827cd8293992',
        'vp'         => '1628726987013-db899232027c',
        'headmistress' => '1646979201169-e83d743bfa8d',
        'sports'     => '1589386417686-0d34b5903d23',
        'admin'      => '1637589267610-6c66fc2a086b',
    ];

    /** A photo's link, cropped to the size the site shows it at. */
    public static function photo(string $key, int $w = 1600, ?int $h = null): string
    {
        $id = self::PHOTOS[$key] ?? self::PEOPLE[$key];

        return "https://images.unsplash.com/photo-{$id}?auto=format&fit=crop&w={$w}" . ($h ? "&h={$h}" : '') . '&q=80';
    }

    /** A person's portrait, cropped on the face. */
    public static function portrait(string $key): string
    {
        return 'https://images.unsplash.com/photo-' . self::PEOPLE[$key] . '?auto=format&fit=crop&crop=faces&w=600&h=750&q=80';
    }

    /** Several photos, one link a line — how a page keeps its More photos. */
    private static function photos(string ...$keys): string
    {
        return implode("\n", array_map(fn ($k) => self::photo($k, 1200, 800), $keys));
    }

    /**
     * The School Profile's texts the About Us pages read: about_school and the
     * usm_ vision, mission, values and goals.
     */
    public static function profile(string $name): array
    {
        return [
            'about_school' => "{$name} is a co-educational English medium school offering education from Nursery to Class XII. "
                . 'Since its founding the school has grown into a community of more than 1,800 students and 120 teachers, '
                . 'known for sound academics, a caring atmosphere and a busy calendar of sports, arts and activities.'
                . "\n\n"
                . 'Our classrooms are bright and digital, our laboratories well equipped, and our teachers trained to give every '
                . 'child individual attention. We believe a school should build character as carefully as it builds knowledge — '
                . 'so honesty, discipline, kindness and curiosity are part of every school day.',
            'usm_vision'  => 'To be a centre of learning where every child discovers their potential and grows into a confident, '
                . 'compassionate and responsible citizen of the world.',
            'usm_mission' => 'To provide joyful, child-centred education of the highest standard; to nurture curiosity, creativity '
                . 'and critical thinking; and to build strong values through a safe, inclusive and caring school community.',
            'usm_values'  => "Integrity — we are honest in word and deed.\n"
                . "Respect — for every person, every culture and our environment.\n"
                . "Excellence — we give our best in all we do.\n"
                . "Compassion — we care for one another and for those in need.\n"
                . 'Responsibility — we own our choices and their consequences.',
            'usm_goals'   => "Every student reading, writing and reasoning at or above grade level.\n"
                . "100% pass results in the CBSE Board examinations, with more students scoring above 90%.\n"
                . "Every child taking part in at least one sport and one art form each year.\n"
                . "Digital literacy and coding for every student from Class III onwards.\n"
                . 'A green, plastic-free campus run with our students.',
        ];
    }

    /** The School Profile's team: [name, designation, portrait key]. */
    public static function team(): array
    {
        return [
            ['Dr. Ramesh Chandra Sharma', 'Chairman', 'chairman'],
            ['Mr. Arjun Mehta', 'Managing Director', 'md'],
            ['Mrs. Neha Kapoor', 'Principal', 'principal'],
            ['Mrs. Anjali Joshi', 'Vice Principal', 'vp'],
            ['Mr. Vikram Singh Rathore', 'Director – Academics', 'director'],
            ['Ms. Priya Nair', 'Headmistress – Primary Wing', 'headmistress'],
            ['Mr. Rohit Malhotra', 'School Manager', 'manager'],
            ['Mrs. Kavita Verma', 'Administrative Officer', 'admin'],
            ['Mr. Karan Thakur', 'Head of Sports', 'sports'],
        ];
    }

    /** Transport routes: [name, vehicle, pickup, drop, monthly fee, seats, stops]. */
    public static function routes(): array
    {
        return [
            ['Route 1 – Civil Lines', 'Bus', '07:00', '14:20', 1200, 42, ['Civil Lines Chowk', 'District Court', 'Collectorate', 'Police Lines']],
            ['Route 2 – Railway Station', 'Bus', '06:50', '14:30', 1300, 42, ['Railway Station', 'Gandhi Park', 'Clock Tower', 'Sadar Bazaar']],
            ['Route 3 – Model Town', 'Bus', '07:05', '14:15', 1100, 36, ['Model Town Gate', 'Shiv Mandir', 'Community Centre']],
            ['Route 4 – Housing Board Colony', 'Bus', '06:45', '14:35', 1400, 42, ['Housing Board Phase 1', 'Phase 2 Market', 'Water Tank', 'Bypass Crossing']],
            ['Route 5 – Old City', 'Van', '07:10', '14:10', 1000, 14, ['Purana Bazaar', 'Jama Masjid Road', 'Kotwali']],
            ['Route 6 – University Road', 'Bus', '06:55', '14:25', 1250, 42, ['University Gate', 'Hospital Chauraha', 'Nehru Nagar', 'Petrol Pump']],
        ];
    }

    /**
     * The session's calendar: [Y-m-d, title, type, description, all day,
     * start, end], for the session starting 1 April of $year. Festivals that
     * move with the lunar calendar are given only for 2026-27.
     */
    public static function events(int $year): array
    {
        $y = $year;
        $n = $year + 1;

        $events = [
            ["{$y}-04-01", 'New Session Begins', 'event', 'Classes for the new session begin. Students report in full uniform by 7:45 AM.', false, '07:45', '13:30'],
            ["{$y}-04-14", 'Ambedkar Jayanti', 'holiday', 'School closed.', true, null, null],
            ["{$y}-04-22", 'Earth Day – Tree Plantation Drive', 'event', 'Each house plants saplings around the campus.', false, '08:00', '10:00'],
            ["{$y}-05-01", 'Labour Day', 'holiday', 'School closed.', true, null, null],
            ["{$y}-05-18", 'Summer Vacation Begins', 'holiday', 'Summer vacation from 18 May to 30 June. Holiday homework on the app.', true, null, null],
            ["{$y}-06-21", 'International Yoga Day', 'sports', 'Yoga session for students and staff on the main ground.', false, '07:00', '08:30'],
            ["{$y}-07-01", 'School Reopens after Summer Vacation', 'event', null, false, '07:45', '13:30'],
            ["{$y}-07-18", 'Parent-Teacher Meeting (Classes I–XII)', 'meeting', 'Discussion of Periodic Test 1 results.', false, '09:00', '12:00'],
            ["{$y}-07-25", 'Investiture Ceremony', 'event', 'The new Student Council takes its oath.', false, '09:00', '11:00'],
            ["{$y}-08-08", 'Inter-House Debate Competition', 'event', null, false, '10:00', '12:30'],
            ["{$y}-08-15", 'Independence Day Celebration', 'event', 'Flag hoisting, march past and cultural programme.', false, '07:30', '10:00'],
            ["{$y}-09-05", "Teachers' Day", 'event', 'Programme by the students of Class XII.', false, '10:00', '12:00'],
            ["{$y}-09-14", 'Hindi Diwas', 'event', 'Poem recitation and essay writing.', false, '09:00', '11:00'],
            ["{$y}-09-21", 'Half-Yearly Examinations Begin', 'exam', 'Half-yearly examinations for Classes I–XII, 21 September to 3 October.', true, null, null],
            ["{$y}-10-02", 'Gandhi Jayanti', 'holiday', 'School closed. Swachhta pledge on 1 October.', true, null, null],
            ["{$y}-10-17", 'Parent-Teacher Meeting – Half-Yearly Results', 'meeting', null, false, '09:00', '12:00'],
            ["{$y}-11-14", "Children's Day", 'event', 'Fun fair and special assembly by the teachers.', false, '08:00', '12:00'],
            ["{$y}-11-21", 'Annual Science Exhibition', 'event', 'Projects and models by Classes VI–XII. Parents are welcome.', false, '09:00', '13:00'],
            ["{$y}-12-05", 'Annual Sports Meet', 'sports', 'Track and field events, march past and prize distribution.', false, '08:00', '14:00'],
            ["{$y}-12-19", 'Annual Day', 'event', 'Cultural evening and prize distribution in the school auditorium.', false, '16:00', '20:00'],
            ["{$y}-12-25", 'Christmas', 'holiday', 'School closed.', true, null, null],
            ["{$y}-12-28", 'Winter Vacation Begins', 'holiday', 'Winter vacation from 28 December to 5 January.', true, null, null],
            ["{$n}-01-06", 'School Reopens after Winter Vacation', 'event', null, false, '08:15', '13:30'],
            ["{$n}-01-26", 'Republic Day Celebration', 'event', 'Flag hoisting and cultural programme.', false, '08:00', '10:30'],
            ["{$n}-02-02", 'Pre-Board Examinations (X & XII) End', 'exam', null, true, null, null],
            ["{$n}-02-17", 'CBSE Board Examinations Begin', 'exam', 'Board examinations for Classes X and XII as per the CBSE date sheet.', true, null, null],
            ["{$n}-03-01", 'Annual Examinations Begin (I–IX & XI)', 'exam', 'Annual examinations, 1 to 14 March.', true, null, null],
            ["{$n}-03-27", 'Result Declaration & Parent-Teacher Meeting', 'meeting', 'Report cards are given to parents.', false, '09:00', '12:00'],
        ];

        if ($year === 2026) {
            $events = array_merge($events, [
                ['2026-08-28', 'Raksha Bandhan', 'holiday', 'School closed.', true, null, null],
                ['2026-09-04', 'Janmashtami', 'holiday', 'School closed.', true, null, null],
                ['2026-10-20', 'Dussehra', 'holiday', 'School closed.', true, null, null],
                ['2026-11-07', 'Diwali Break Begins', 'holiday', 'Diwali break from 7 to 10 November.', true, null, null],
                ['2026-11-24', 'Guru Nanak Jayanti', 'holiday', 'School closed.', true, null, null],
                ['2027-03-22', 'Holi', 'holiday', 'School closed.', true, null, null],
            ]);
        }

        usort($events, fn ($a, $b) => strcmp($a[0], $b[0]));

        return $events;
    }

    /** Extra rows for Rules & Regulations' additional information: [key, value]. */
    public static function rulesInfo(): array
    {
        return [
            ['School Timings (April – September)', '7:45 AM – 1:45 PM'],
            ['School Timings (October – March)', '8:15 AM – 2:15 PM'],
            ['Office Hours', '8:00 AM – 3:00 PM (Monday to Saturday)'],
            ['Uniform', 'Summer uniform from April to October; winter uniform from November to March. Wednesday is house uniform day.'],
            ['Mobile Phones', 'Students may not bring mobile phones or smart watches to school.'],
        ];
    }

    /** The documents each document page lists: slug => [title, date]. */
    public static function documentTitles(string $session): array
    {
        return [
            'fee-structure' => [
                ["Fee Structure {$session}", 'Apr 2026'],
                ['Fee Payment Schedule & Concessions', 'Apr 2026'],
            ],
            'academic-calendar' => [
                ["Academic Calendar {$session}", 'Apr 2026'],
                ["Examination Schedule {$session}", 'Jul 2026'],
            ],
            'disclosures' => array_map(fn ($d) => [$d['title'], 'Apr 2026'], SchoolWebsite::defaultDisclosures()),
        ];
    }

    /**
     * Everything Website Data holds for the site, for a school named $name on
     * $board in session $session ("2026-27").
     */
    public static function content(string $name, string $board, string $session): array
    {
        $next  = (int) substr($session, 0, 4) + 1;
        $nextS = $next . '-' . substr((string) ($next + 1), -2);
        $p     = fn (string $k, int $w = 1600, ?int $h = null) => self::photo($k, $w, $h);

        return [
            // ─── School Details ─────────────────────────────────────────
            'motto'          => 'Vidya Dadati Vinayam',
            'medium'         => 'An English Medium Co-Educational Senior Secondary School',
            'board'          => $board,
            'affiliation_no' => '1234567',
            'school_code'    => '12345',
            'tagline'        => 'Nurturing young minds since 1998 — Nursery to Class XII',
            'page_banner'    => $p('campus', 1920, 520),
            'facebook'       => 'https://www.facebook.com/',
            'instagram'      => 'https://www.instagram.com/',
            'youtube'        => 'https://www.youtube.com/',
            'twitter'        => 'https://x.com/',

            // ─── Home ───────────────────────────────────────────────────
            'hero_slides' => [
                ['image' => $p('building', 1920, 900), 'title' => "Welcome to {$name}", 'subtitle' => 'An English Medium Co-Educational School · Nursery to Class XII'],
                ['image' => $p('kids_class', 1920, 900), 'title' => "Admissions Open {$nextS}", 'subtitle' => 'Nursery to Class IX and XI — apply online today'],
                ['image' => $p('lab_kids', 1920, 900), 'title' => 'Learning by Doing', 'subtitle' => 'Physics, Chemistry, Biology, Computer and Maths labs'],
                ['image' => $p('yoga', 1920, 900), 'title' => 'Healthy Body, Healthy Mind', 'subtitle' => 'Yoga, athletics, football, cricket and more every week'],
                ['image' => $p('dance', 1920, 900), 'title' => 'A Stage for Every Talent', 'subtitle' => 'Dance, music, art and theatre all through the year'],
            ],
            'philosophy' => 'We believe every child is unique and learns in their own way. Our teaching blends the CBSE curriculum with '
                . 'activity-based and experiential learning, so that children understand ideas rather than memorise them — and leave '
                . 'school curious, confident and kind.',
            'cta_heading' => "Admissions Open for Session {$nextS}",
            'cta_text'    => "Give your child the best start. Seats are limited — enquire today and become part of the {$name} family.",

            // ─── About School ───────────────────────────────────────────
            'about_heading' => "Welcome to {$name}",
            'about_text2'   => 'The school stands on a green, secure campus of 5 acres with 64 smart classrooms, separate wings for the '
                . 'pre-primary, primary and senior classes, five laboratories, a library of over 15,000 books, an auditorium and '
                . 'large playgrounds for cricket, football, basketball and athletics.',
            'history_text'  => "{$name} was founded in 1998 by Shri Om Prakash Agarwal with 120 students and 12 teachers in a rented building. "
                . 'In 2004 the school moved to its present campus, and in 2008 it was affiliated to the Central Board of Secondary '
                . "Education up to the Senior Secondary level.\n\n"
                . 'Today the school educates more than 1,800 students. Its alumni study and work across India and abroad as doctors, '
                . 'engineers, civil servants, teachers, entrepreneurs and artists — and many return each year for our Alumni Meet.',

            // ─── Admission (home page band) ─────────────────────────────
            'admission_intro'   => "Registration for Session {$nextS} is open for Nursery to Class IX and Class XI. Admission to Nursery to Class II "
                . 'is through an informal interaction with the child and parents; for Classes III and above an admission test in English, '
                . 'Mathematics and Science is held.',
            'admission_session' => "1 December " . ($next - 1) . " – 31 March {$next}",
            'admission_steps'   => [
                ['title' => 'Register online', 'desc' => 'Fill in the online admission form on this website, or collect a form from the school office.'],
                ['title' => 'Submit documents', 'desc' => 'Bring the documents listed here to the admission office within 7 days.'],
                ['title' => 'Interaction / admission test', 'desc' => 'The date is sent to you by SMS and WhatsApp.'],
                ['title' => 'Confirmation', 'desc' => 'Selected students are informed within a week of the test.'],
                ['title' => 'Fee deposit', 'desc' => 'Deposit the admission fee to confirm the seat.'],
            ],
            'documents_required' => [
                ['text' => 'Birth certificate (Nursery to Class II)'],
                ['text' => 'Transfer certificate from the previous school (Class II onwards)'],
                ['text' => 'Report card of the last class passed'],
                ['text' => "Aadhaar card of the student and both parents"],
                ['text' => 'Four recent passport-size photographs of the student'],
                ['text' => 'Address proof (electricity bill, rent agreement or passport)'],
                ['text' => 'Caste certificate, if applicable'],
                ['text' => 'Medical fitness certificate'],
            ],
            'admission_rules' => [
                ['text' => "The session begins on 1 April; admissions after 31 July are given only on a transfer."],
                ['text' => 'For Nursery the child must be 3 years old on 31 March of the year of admission.'],
                ['text' => 'Admission is confirmed only after every document has been checked and the fee deposited.'],
                ['text' => 'A 10% concession on the tuition fee is given to the second child of the same parents.'],
                ['text' => 'The admission fee is not refundable once admission is confirmed.'],
                ['text' => 'Admission obtained on false information will be cancelled.'],
            ],
            'fee_note' => 'Fees are payable quarterly by the 10th of April, July, October and January — online, by UPI or at the school counter.',

            // ─── Lists ──────────────────────────────────────────────────
            'leadership' => [
                ['photo' => self::portrait('chairman'), 'name' => 'Dr. Ramesh Chandra Sharma', 'role' => 'Chairman',
                    'message' => "Education is the most valuable gift we can give a child. At {$name} we work every day to make that gift complete — sound learning, good values and a healthy, happy childhood."],
                ['photo' => self::portrait('md'), 'name' => 'Mr. Arjun Mehta', 'role' => 'Managing Director',
                    'message' => 'Our investment in smart classrooms, laboratories, sports and safety has one aim: to give our students the opportunities that the best schools in the country offer.'],
                ['photo' => self::portrait('principal'), 'name' => 'Mrs. Neha Kapoor', 'role' => 'Principal',
                    'message' => 'A school is a partnership between students, parents and teachers. Together we help every child find what they love, work hard at it, and grow into a good human being.'],
                ['photo' => self::portrait('vp'), 'name' => 'Mrs. Anjali Joshi', 'role' => 'Vice Principal',
                    'message' => 'We give equal weight to the classroom, the playground and the stage, so that every child discovers their strengths and builds on them.'],
            ],
            'facilities' => [
                ['image' => $p('classroom', 900, 560), 'icon' => '🏫', 'title' => 'Smart Classrooms', 'desc' => '64 airy classrooms with interactive panels, digital content and comfortable furniture.'],
                ['image' => $p('lab_girl', 900, 560), 'icon' => '🔬', 'title' => 'Science Laboratories', 'desc' => 'Separate Physics, Chemistry and Biology labs where students learn by experiment.'],
                ['image' => $p('computer_lab', 900, 560), 'icon' => '💻', 'title' => 'Computer & Robotics Lab', 'desc' => '80 systems with high-speed internet, coding and robotics kits from Class III.'],
                ['image' => $p('library', 900, 560), 'icon' => '📚', 'title' => 'Library', 'desc' => 'Over 15,000 books, magazines and e-resources, with a reading corner for juniors.'],
                ['image' => $p('ball_game', 900, 560), 'icon' => '⚽', 'title' => 'Sports & Playgrounds', 'desc' => 'Cricket, football, basketball, athletics and indoor games with trained coaches.'],
                ['image' => $p('buses', 900, 560), 'icon' => '🚌', 'title' => 'Safe Transport', 'desc' => 'GPS-tracked buses with CCTV, trained drivers and a lady attendant on every route.'],
                ['image' => $p('art', 900, 560), 'icon' => '🎨', 'title' => 'Art, Music & Dance', 'desc' => 'Dedicated studios for art, vocal and instrumental music, and Indian and western dance.'],
                ['image' => $p('yoga', 900, 560), 'icon' => '🧘', 'title' => 'Yoga & Wellness', 'desc' => 'Daily yoga, a medical room with a full-time nurse, and counselling for every child.'],
                ['image' => $p('block', 900, 560), 'icon' => '🛡️', 'title' => 'Safe & Secure Campus', 'desc' => 'CCTV across the campus, guarded gates, visitor passes and RO drinking water on every floor.'],
            ],
            'classes' => [
                ['image' => $p('kids_group', 900, 560), 'title' => 'Nursery', 'age' => '3 – 4 years', 'time' => '8:30 AM – 12:30 PM', 'capacity' => '30 per section'],
                ['image' => $p('kids_floor', 900, 560), 'title' => 'LKG & UKG', 'age' => '4 – 6 years', 'time' => '8:30 AM – 12:30 PM', 'capacity' => '30 per section'],
                ['image' => $p('girls_desk', 900, 560), 'title' => 'Primary (I – V)', 'age' => '6 – 11 years', 'time' => '7:45 AM – 1:30 PM', 'capacity' => '35 per section'],
                ['image' => $p('boys_books', 900, 560), 'title' => 'Middle (VI – VIII)', 'age' => '11 – 14 years', 'time' => '7:45 AM – 1:45 PM', 'capacity' => '40 per section'],
                ['image' => $p('girls_class', 900, 560), 'title' => 'Secondary (IX – X)', 'age' => '14 – 16 years', 'time' => '7:45 AM – 1:45 PM', 'capacity' => '40 per section'],
                ['image' => $p('seniors', 900, 560), 'title' => 'Senior Secondary (XI – XII)', 'age' => '16 – 18 years', 'time' => '7:45 AM – 2:00 PM', 'capacity' => 'Science, Commerce & Humanities'],
            ],
            'gallery' => [
                ['image' => $p('dance', 1200, 900), 'caption' => 'Annual Day – Classical Dance'],
                ['image' => $p('dance_group', 1200, 900), 'caption' => 'Annual Day – Group Performance'],
                ['image' => $p('assembly', 1200, 900), 'caption' => 'Morning Assembly'],
                ['image' => $p('yoga', 1200, 900), 'caption' => 'International Yoga Day'],
                ['image' => $p('track', 1200, 900), 'caption' => 'Annual Sports Meet – 100 m Sprint'],
                ['image' => $p('football', 1200, 900), 'caption' => 'Inter-House Football'],
                ['image' => $p('lab_kids', 1200, 900), 'caption' => 'Science Exhibition'],
                ['image' => $p('microscope', 1200, 900), 'caption' => 'Junior Science Club'],
                ['image' => $p('art', 1200, 900), 'caption' => 'Art & Craft Workshop'],
                ['image' => $p('watercolour', 1200, 900), 'caption' => 'Watercolour Competition'],
                ['image' => $p('music', 1200, 900), 'caption' => 'Music Class'],
                ['image' => $p('library_boy', 1200, 900), 'caption' => 'Library Period'],
                ['image' => $p('classroom', 1200, 900), 'caption' => 'Smart Classroom'],
                ['image' => $p('prizes', 1200, 900), 'caption' => 'Prize Distribution'],
                ['image' => $p('graduation', 1200, 900), 'caption' => 'Class XII Farewell'],
                ['image' => $p('field_day', 1200, 900), 'caption' => 'Field Day – Primary Wing'],
                ['image' => $p('hoops', 1200, 900), 'caption' => 'Fun Games – Pre-Primary'],
                ['image' => $p('building', 1200, 900), 'caption' => 'Our Campus'],
            ],
            'results' => [
                ['year' => '2022 – Class X', 'appeared' => '142', 'passed' => '139'],
                ['year' => '2022 – Class XII', 'appeared' => '118', 'passed' => '115'],
                ['year' => '2023 – Class X', 'appeared' => '151', 'passed' => '150'],
                ['year' => '2023 – Class XII', 'appeared' => '124', 'passed' => '122'],
                ['year' => '2024 – Class X', 'appeared' => '158', 'passed' => '158'],
                ['year' => '2024 – Class XII', 'appeared' => '131', 'passed' => '130'],
                ['year' => '2025 – Class X', 'appeared' => '163', 'passed' => '163'],
                ['year' => '2025 – Class XII', 'appeared' => '137', 'passed' => '136'],
                ['year' => '2026 – Class X', 'appeared' => '170', 'passed' => '170'],
                ['year' => '2026 – Class XII', 'appeared' => '142', 'passed' => '142'],
            ],

            // ─── Text pages: slug => {heading, body, image, photos} ──────
            'pages' => self::pages($name, $board),
        ];
    }

    /** The text pages — the desks, facilities, academics and the rest. */
    private static function pages(string $name, string $board): array
    {
        $p    = fn (string $k, int $w = 1200, ?int $h = 800) => self::photo($k, $w, $h);
        $desk = fn (string $who, string $heading, string $body, string $sign) => [
            'heading' => $heading,
            'body'    => $body . "\n\n" . $sign,
            'image'   => self::portrait($who),
        ];

        return [
            'vision' => ['heading' => 'Our Vision', 'image' => $p('kids_group'),
                'body' => 'To be a centre of learning where every child discovers their potential and grows into a confident, compassionate and responsible citizen of the world.'],
            'mission' => ['heading' => 'Our Mission', 'image' => $p('teacher_board'),
                'body' => 'To provide joyful, child-centred education of the highest standard; to nurture curiosity, creativity and critical thinking; and to build strong values through a safe, inclusive and caring school community.'],
            'campus-life' => ['heading' => 'Campus & School Life', 'image' => $p('assembly'),
                'body' => "A day at {$name} begins with the morning assembly — a prayer, the news, a thought for the day and a performance by one of the classes. "
                    . "Classes run until early afternoon, with time set aside every week for games, library, art, music and clubs.\n\n"
                    . 'Students belong to one of four houses — Aravali, Nilgiri, Shivalik and Vindhya — which compete through the year in sports, debates, '
                    . "quizzes and cultural events. Clubs for science, eco, robotics, literature, photography and heritage meet every Saturday.\n\n"
                    . 'Educational excursions, the annual sports meet, the science exhibition and Annual Day are the highlights of the year that every student looks forward to.',
                'photos' => self::photos('assembly', 'yoga', 'dance_group', 'football', 'music', 'prizes')],

            // Messaging — the desks.
            'chairman' => $desk('chairman', "Chairman's Message",
                "Dear Parents and Students,\n\n"
                . "It gives me great joy to welcome you to {$name}. When we began this journey, our dream was simple — a school where every child is known, "
                . "valued and helped to do their best. That dream still guides everything we do.\n\n"
                . 'We live in a fast-changing world. Our children need not just knowledge, but the ability to think, to adapt, to work with others and to '
                . "stand up for what is right. Our teachers work tirelessly to build these qualities in every classroom.\n\n"
                . 'I thank our parents for the trust they place in us, and I wish every student a bright and happy future.',
                "Dr. Ramesh Chandra Sharma\nChairman"),
            'founder' => $desk('founder', "Founder's Message",
                "When I founded this school in 1998 with a handful of students, I believed that good education should be within reach of every family "
                . "in our town. More than twenty-five years later, it fills my heart to see thousands of our children doing well in every walk of life.\n\n"
                . 'A school is not its buildings but its people. I thank every teacher who has given their best to our students, and every parent who has walked '
                . 'with us. May this school always remain a place of learning, kindness and hope.',
                "Shri Om Prakash Agarwal\nFounder"),
            'co-founder' => $desk('cofounder', "Co-Founder's Message",
                "From the day we started, we wanted our school to feel like a second home — where children are safe, happy and eager to learn. "
                . "Watching our little ones in the pre-primary wing grow into confident young adults is the greatest reward of all.\n\n"
                . 'We will keep listening to our parents and students, and keep making our school better every year.',
                "Mrs. Sunita Agarwal\nCo-Founder"),
            'managing-director' => $desk('md', "Managing Director's Message",
                "Our aim is to give our students the facilities and opportunities of the best schools in the country — smart classrooms, modern laboratories, "
                . "a well-stocked library, safe transport and excellent sports grounds.\n\n"
                . 'Equally, we invest in our teachers: through regular training they bring the latest teaching methods into our classrooms. '
                . 'I invite you to visit our campus and see our school at work.',
                "Mr. Arjun Mehta\nManaging Director"),
            'executive-director' => $desk('ed', "Executive Director's Message",
                "Education today is about preparing children for jobs that do not yet exist. That is why we have introduced coding, robotics, financial "
                . "literacy and life skills alongside the {$board} curriculum.\n\n"
                . 'We measure our success not only in marks, but in the confidence, curiosity and character of our students.',
                "Mrs. Kavita Verma\nExecutive Director"),
            'director' => $desk('director', "Director's Message",
                "Our academic programme is built on strong foundations in the early years, concept-based learning in the middle years and focused preparation "
                . "for board examinations and competitive entrance tests in the senior years.\n\n"
                . 'Regular assessments, remedial classes and personal mentoring make sure that no child is left behind.',
                "Mr. Vikram Singh Rathore\nDirector – Academics"),
            'manager-desk' => $desk('manager', "Manager's Message",
                "Our administration works to make the school experience smooth for every family — from admissions and fees to transport and communication. "
                . "Parents receive attendance, homework, results and notices on the school app, and our office is always happy to help.\n\n"
                . 'Thank you for your cooperation and support.',
                "Mr. Rohit Malhotra\nSchool Manager"),
            'principal-desk' => $desk('principal', "Principal's Message",
                "Dear Parents and Students,\n\n"
                . "At {$name} we believe every child can learn and every child can shine. Our job is to find the spark in each one and help it grow.\n\n"
                . 'We follow the CBSE curriculum with a focus on understanding, activity and enquiry. Equal attention is given to sports, arts, values and '
                . "emotional well-being, because a good education develops the whole person.\n\n"
                . 'I look forward to working with you to make this year a happy and successful one for our students.',
                "Mrs. Neha Kapoor\nPrincipal, M.Sc., M.Ed."),

            // Features & Photogallery.
            'labs' => ['heading' => 'Laboratories', 'image' => $p('lab_bench'),
                'body' => 'The school has separate, well-equipped laboratories for Physics, Chemistry, Biology, Computer Science and Mathematics, '
                    . "as laid down by the {$board}. Every lab has enough equipment for students to work in small groups, under the eye of trained lab assistants.\n\n"
                    . 'Practical work begins in the middle classes, so that by the senior classes students are confident with experiments, observation and recording.',
                'photos' => self::photos('lab_kids', 'microscope', 'lab_girl', 'lab_teacher', 'computer_lab', 'computers')],
            'classrooms' => ['heading' => 'Classrooms', 'image' => $p('classroom'),
                'body' => 'Our 64 classrooms are spacious, well lit and ventilated, each with an interactive smart panel, a whiteboard and display boards for '
                    . "students' work. Pre-primary rooms are colourful and child-friendly, with activity corners and toys.",
                'photos' => self::photos('kids_class', 'class_desks', 'empty_class', 'teacher_board', 'girls_class')],
            'playground' => ['heading' => 'Playground', 'image' => $p('ground'),
                'body' => 'Two large playgrounds host cricket, football and athletics, alongside courts for basketball, volleyball and badminton, '
                    . 'and a separate play area with swings and slides for our youngest students.',
                'photos' => self::photos('ball_game', 'football', 'girls_game', 'hoops')],
            'sports' => ['heading' => 'Sports & Activities', 'image' => $p('football'),
                'body' => 'Every student has games periods every week and can join coaching in cricket, football, basketball, athletics, chess, '
                    . "table tennis, skating, taekwondo and yoga. Our teams take part in CBSE cluster, district and state level tournaments.\n\n"
                    . 'The Annual Sports Meet in December is one of the highlights of the school year.',
                'photos' => self::photos('track', 'yoga', 'field_day', 'girls_game', 'hoops', 'ball_game')],
            'activities' => ['heading' => 'Activities', 'image' => $p('dance'),
                'body' => 'Dance, music, art, theatre, debate, quiz and public speaking are part of the timetable from Nursery to Class XII. '
                    . 'Inter-house competitions, celebrations of national festivals and days, and educational trips round off the year.',
                'photos' => self::photos('dance_group', 'art', 'watercolour', 'drawings', 'music', 'piano')],
            'transport' => ['heading' => 'Transport', 'image' => $p('buses'),
                'body' => 'The school runs its own fleet of buses and vans covering the city and nearby areas. Every vehicle has GPS tracking, CCTV, '
                    . 'a first-aid kit, a fire extinguisher, a trained driver and a lady attendant. Parents can follow the bus on the school app.',
                'photos' => self::photos('bus')],
            'infrastructure' => ['heading' => 'Infrastructure Details', 'image' => $p('campus'),
                'body' => "Total campus area: 5 acres (20,234 sq m)\nBuilt-up area: 9,800 sq m\nNumber of classrooms: 64 (all smart classrooms)\n"
                    . "Laboratories: Physics, Chemistry, Biology, Computer Science, Mathematics\nLibrary: 15,000+ books, 40 journals and magazines\n"
                    . "Auditorium: 600 seats\nPlaygrounds: 2, with courts for basketball, volleyball and badminton\n"
                    . "Internet: 200 Mbps leased line with campus Wi-Fi\nCCTV cameras: 120\nToilets: separate blocks for girls and boys on every floor, with ramps and accessible toilets\n"
                    . 'Drinking water: RO water coolers on every floor',
                'photos' => self::photos('building', 'block', 'library2')],
            'building' => ['heading' => 'Building Photos', 'image' => $p('building'),
                'body' => 'Our campus — the main building, the senior wing, the library and the grounds.',
                'photos' => self::photos('campus', 'block', 'brick', 'library', 'empty_class', 'ground')],

            // Online Admission & Curriculum.
            'rules' => ['heading' => 'Rules & Regulations',
                'body' => 'Every student is expected to follow the rules of the school, listed below. Parents are requested to read them with their '
                    . 'child and to support the school in maintaining discipline.'],
            'programs' => ['heading' => 'Programs', 'image' => $p('girl_uniform'),
                'body' => "Pre-Primary (Nursery – UKG): play-way, activity-based learning that builds language, numbers and social skills.\n\n"
                    . "Primary (I – V): strong foundations in reading, writing and arithmetic, with EVS, computers, art and music.\n\n"
                    . "Middle (VI – VIII): concept-based learning in every subject, with a third language and practical work in labs.\n\n"
                    . "Secondary (IX – X): the {$board} curriculum with focused preparation for the board examinations.\n\n"
                    . 'Senior Secondary (XI – XII): Science (PCM / PCB), Commerce and Humanities streams, with coaching support for JEE, NEET and CUET.'],
            'curriculum' => ['heading' => 'Curriculum', 'image' => $p('teacher_board'),
                'body' => "The school follows the curriculum and assessment scheme of the {$board}, in line with the National Education Policy 2020. "
                    . "Learning is competency-based, with projects, experiments and activities in every subject.\n\n"
                    . "Languages: English, Hindi and Sanskrit.\nCore subjects: Mathematics, Science, Social Science, Computer Science.\n"
                    . "Co-scholastic: Art, Music, Dance, Physical Education, Yoga, Work Education and Value Education.\n\n"
                    . 'Each year has two terms, with periodic tests, half-yearly and annual examinations, and continuous assessment through class work, '
                    . 'projects and portfolios.'],
            'career' => ['heading' => 'Career',
                'body' => "We are always looking for passionate, qualified teachers who love working with children.\n\n"
                    . "Current openings:\nPGT – Physics, Chemistry, Computer Science\nTGT – Mathematics, English, Social Science\n"
                    . "PRT – All subjects\nSports Coach (Football / Athletics)\n\n"
                    . 'Teachers must hold the qualifications laid down by the CBSE. Apply with the form below, or email your CV to the school office.'],
        ];
    }
}
