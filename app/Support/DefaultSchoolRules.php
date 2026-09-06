<?php

namespace App\Support;

/**
 * A starting set of school rules and regulations.
 *
 * Schools open their Rules & Regulations screen on these instead of a blank
 * form: every section is generic enough for any school to publish as-is, and
 * specific enough to be worth keeping. Each is edited or deleted like any
 * other section once saved — nothing here overrides what a school has written.
 */
class DefaultSchoolRules
{
    /**
     * @return array<int, array{head: string, desc: string}>
     */
    public static function sections(): array
    {
        return [
            [
                'head' => 'Admission and Enrolment',
                'desc' => 'Admission is granted on the basis of the seats available in a class, the '
                    . 'admission test or interaction where applicable, and the submission of every '
                    . 'required document. Parents must provide the transfer certificate from the '
                    . 'previous school, the birth certificate, and address and identity proof at the '
                    . 'time of admission. Information given in the admission form must be accurate; '
                    . 'admission secured on false or incomplete information may be cancelled at any '
                    . 'stage. Once admission is confirmed, the student and the parents accept the '
                    . 'rules of the school in full.',
            ],
            [
                'head' => 'School Timings and Punctuality',
                'desc' => 'Students must reach the school before the first bell and be present in the '
                    . 'assembly. A student arriving after the gate closes may be marked late and, on '
                    . 'repeated occasions, sent home in the care of a parent. Leaving the campus '
                    . 'during school hours is not allowed without a written request from the parent '
                    . 'and a gate pass issued by the office. Parents are requested to collect their '
                    . 'children promptly at dispersal, as the school cannot supervise students after '
                    . 'the closing bell.',
            ],
            [
                'head' => 'Attendance and Leave',
                'desc' => 'A minimum of seventy-five percent attendance in the academic year is '
                    . 'required to be eligible for promotion and for the final examinations. Leave '
                    . 'must be applied for in advance through a written application; leave taken '
                    . 'without intimation is treated as absence. In case of illness lasting more than '
                    . 'three days, a medical certificate must be submitted on the day the student '
                    . 'returns. Students suffering from a contagious illness must stay at home until '
                    . 'they are declared fit, for the safety of everyone in the class.',
            ],
            [
                'head' => 'Uniform and Personal Grooming',
                'desc' => 'The prescribed uniform, including shoes, socks, belt and tie, is to be worn '
                    . 'neatly on all working days, and the games uniform only on the days assigned for '
                    . 'it. Hair must be kept clean and tidy; nails must be trimmed. Jewellery, '
                    . 'expensive accessories, tattoos and coloured hair are not permitted. Students '
                    . 'who arrive out of uniform without prior permission may not be allowed to attend '
                    . 'class until a parent is informed.',
            ],
            [
                'head' => 'Identity Card',
                'desc' => 'Every student is issued an identity card that must be carried on all school '
                    . 'days, on school transport, and at every school event held outside the campus. '
                    . 'The card is required to enter the campus, to borrow from the library, and to '
                    . 'appear for examinations. A lost card must be reported to the office at once and '
                    . 'a duplicate obtained on payment of the prescribed charge. The card belongs to '
                    . 'the school and must be returned when the student leaves.',
            ],
            [
                'head' => 'Discipline and Code of Conduct',
                'desc' => 'Students are expected to be courteous to teachers, staff, visitors and one '
                    . 'another, both inside the campus and outside it while in uniform. Rude language, '
                    . 'dishonesty, cheating, forging a parent\'s signature and damaging the good name '
                    . 'of the school are serious offences. Depending on the gravity of the misconduct, '
                    . 'the school may issue a warning, call the parents, suspend the student, or in '
                    . 'the most serious cases ask for withdrawal. The decision of the school '
                    . 'authorities in matters of discipline is final.',
            ],
            [
                'head' => 'Anti-Bullying and Anti-Ragging',
                'desc' => 'Bullying, ragging, teasing, physical intimidation, exclusion of a classmate '
                    . 'and harassment of any kind — in person or online — will not be tolerated. Any '
                    . 'student who is bullied, or who witnesses bullying, must report it immediately '
                    . 'to a class teacher, counsellor or any member of staff; every complaint is '
                    . 'treated confidentially. Confirmed cases invite strict action including '
                    . 'suspension, and the parents of both students are involved from the outset. The '
                    . 'school also treats discrimination on the basis of gender, religion, caste, '
                    . 'language or ability as a disciplinary offence.',
            ],
            [
                'head' => 'Classroom Conduct and Homework',
                'desc' => 'Students must come to class with the textbooks, notebooks and material '
                    . 'required for the day\'s timetable. Homework and assignments are to be completed '
                    . 'and submitted on the date given; repeated failure to submit work is reported to '
                    . 'the parents. Classrooms are to be left clean, and no student may leave the room '
                    . 'between periods without the teacher\'s permission. Disturbing a class, whether '
                    . 'by talking, moving about or using unauthorised material, is a disciplinary '
                    . 'matter.',
            ],
            [
                'head' => 'Examinations and Assessment',
                'desc' => 'Assessment is continuous through the year and includes class tests, unit '
                    . 'tests, projects, practical work and the term examinations. A student absent '
                    . 'from an examination without a medical reason is not usually given a re-test, '
                    . 'and the paper is marked as missed. Any form of unfair means in an examination '
                    . 'results in the paper being cancelled and the parents being called. Report cards '
                    . 'are issued only to parents, and results are declared on the dates announced by '
                    . 'the school.',
            ],
            [
                'head' => 'Fee Payment',
                'desc' => 'Fees are payable by the due date announced for each term or instalment, '
                    . 'through the modes the school accepts. A late fee may be charged after the due '
                    . 'date, and continued default can lead to the student\'s name being struck off '
                    . 'the rolls. Fees once paid are not refundable or transferable except where the '
                    . 'school\'s refund policy expressly provides for it. A student with dues '
                    . 'outstanding may not be permitted to appear for examinations or to receive the '
                    . 'transfer certificate.',
            ],
            [
                'head' => 'Care of School Property',
                'desc' => 'The building, furniture, laboratory equipment, library books, sports '
                    . 'material, computers and school vehicles are held in trust for every student and '
                    . 'must be treated with care. Writing on walls or desks, damaging fittings and '
                    . 'tampering with electrical or safety equipment are punishable offences. The cost '
                    . 'of any loss or damage, whether wilful or careless, is recovered from the '
                    . 'student concerned. Students are also expected to keep the campus clean and to '
                    . 'use the bins provided.',
            ],
            [
                'head' => 'Mobile Phones and Electronic Devices',
                'desc' => 'Mobile phones, smart watches, cameras and other personal electronic devices '
                    . 'are not to be brought to school unless the school has given written permission. '
                    . 'A device found in use is confiscated and returned only to the parent, and '
                    . 'repeated instances invite disciplinary action. Where a device is permitted for '
                    . 'a specific academic purpose, it must be used only as instructed by the teacher. '
                    . 'Photographing or recording students or staff on the campus without permission '
                    . 'is strictly prohibited.',
            ],
            [
                'head' => 'Health, Safety and Medical Care',
                'desc' => 'Parents must inform the school in writing of any medical condition, allergy '
                    . 'or medication that concerns their child, and keep the emergency contact numbers '
                    . 'up to date. First aid is available on the campus; in an emergency the school '
                    . 'will arrange medical help and inform the parents immediately. Students must '
                    . 'follow the safety instructions given during fire drills, laboratory work and '
                    . 'games. No student may bring medicines to school except with the knowledge of '
                    . 'the school and a note from the parent.',
            ],
            [
                'head' => 'School Transport and Bus Conduct',
                'desc' => 'Students using the school transport must board and alight only at the stop '
                    . 'assigned to them and must remain seated while the vehicle is moving. Putting '
                    . 'any part of the body out of the window, shouting, littering and damaging the '
                    . 'vehicle are not allowed, and the driver and attendant are to be obeyed at all '
                    . 'times. Any change of route or stop must be requested in writing and takes '
                    . 'effect only after approval. Misconduct on the bus can lead to the transport '
                    . 'facility being withdrawn without a refund.',
            ],
            [
                'head' => 'Library, Laboratory and Computer Room',
                'desc' => 'Silence is to be maintained in the library, and books must be returned by '
                    . 'the due date; a lost or damaged book must be replaced or paid for. Laboratories '
                    . 'and the computer room may be entered only in the presence of the teacher in '
                    . 'charge, and equipment is to be handled strictly as instructed. Apparatus, '
                    . 'chemicals and software must not be taken out of the room or tampered with. '
                    . 'Students must follow the safety rules displayed in each room without exception.',
            ],
            [
                'head' => 'Communication with Parents',
                'desc' => 'The school communicates through the school app, circulars, the diary and '
                    . 'parent-teacher meetings, and parents are expected to read and acknowledge them. '
                    . 'Attendance at parent-teacher meetings is important, as the progress and conduct '
                    . 'of the student are discussed in person. Parents who wish to meet a teacher '
                    . 'should take a prior appointment through the office rather than visit a '
                    . 'classroom directly. Any change of address, phone number or email must be '
                    . 'reported to the office so that records remain accurate.',
            ],
            [
                'head' => 'Prohibited Items and Substances',
                'desc' => 'Tobacco, alcohol, drugs and any intoxicating substance are absolutely '
                    . 'forbidden on the campus, on school transport and at school events. Weapons, '
                    . 'sharp objects, crackers, inflammable material and any item that can endanger '
                    . 'others are equally forbidden. Objectionable literature, and content of that '
                    . 'nature on any device, is treated as a serious offence. Bringing large sums of '
                    . 'money or valuables to school is discouraged; the school is not responsible for '
                    . 'their loss.',
            ],
            [
                'head' => 'Withdrawal and Transfer Certificate',
                'desc' => 'A student may be withdrawn only on a written application by the parent, '
                    . 'submitted with the notice period the school prescribes. The transfer '
                    . 'certificate is issued after all dues are cleared and all school property, '
                    . 'including library books and the identity card, is returned. Fees for the term '
                    . 'in progress remain payable irrespective of the date of withdrawal. The school '
                    . 'also reserves the right to ask for the withdrawal of a student whose conduct or '
                    . 'attendance remains unsatisfactory despite written warnings.',
            ],
        ];
    }
}
