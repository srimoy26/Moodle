<?php

define('CLI_SCRIPT', true);


require_once(dirname(__FILE__) . '/../../../../config.php');

// Your JSON data
$json_data = '{
    "name": "Student_demo",
    "email": "benesse@test.com",
    "benesseId": "3000000101",
    "currentClassroom": {
        "id": 7,
        "name": "Section A",
        "year": 2023,
        "grade": 10
    },
    "activated": true,
    "assessments": [
        {
            "id": 6,
            "title": "Grade 10 2nd Cycle 2023",
            "school_name": "DPS-R",
            "courses": {
                "math": {
                    "id_number": "2023_1st_Cycle_M_8_UK000002",
                    "weak_topic": "Number Sytem",
                    "strong_topic": "Statistics"
                },
                "science": {
                    "id_number": "2023_1st_Cycle_S_8_UK000002",
                    "weak_topic": "Electricity",
                    "strong_topic": "Food"
                }
            },
            "schools": [
                1
            ]
        },
        {
            "id": 6,
            "title": "Grade 10 2nd Cycle 2023",
            "school_name": "DPS-R",
            "courses": {
                "math": {
                    "id_number": "2023_1st_Cycle_M_8_UK000002",
                    "weak_topic": "Conic Sections",
                    "strong_topic": "Vector"
                },
                "science": {
                    "id_number": "2023_1st_Cycle_S_8_UK000002",
                    "weak_topic": "Electricity",
                    "strong_topic": "Food"
                }
            }
        },
        {
            "id": 2,
            "title": "Grade 9 Autumn 2022",
            "schools": [
                1
            ]
        },
        {
            "id": 1,
            "title": "Grade 9 Spring 2022",
            "schools": [
                1
            ]
        }
    ],
    "schools": [
        {
            "id": 1,
            "name": "Sample School A",
            "code": "Sample School A",
            "assessmentHistories": [
                {
                    "assessmentId": 1,
                    "branch": 1,
                    "history": 1
                },
                {
                    "assessmentId": 2,
                    "branch": 1,
                    "history": 2
                },
                {
                    "assessmentId": 3,
                    "branch": 1,
                    "history": 3
                },
                {
                    "assessmentId": 4,
                    "branch": 2,
                    "history": 1
                },
                {
                    "assessmentId": 5,
                    "branch": 3,
                    "history": 1
                },
                {
                    "assessmentId": 6,
                    "branch": 1,
                    "history": 4
                },
                {
                    "assessmentId": 7,
                    "branch": 1,
                    "history": 4
                }
            ]
        }
    ],
    "studentId": 1,
    "teacherId": null,
    "isAdmin": false,
    "isTeacher": false,
    "isManager": false,
    "isEmployeeAdmin": false,
    "isFirstSetting": true
}
'; // Insert your JSON data here

$data = json_decode($json_data, true);

global $DB, $USER;

$current_user_id = $USER->id;

// Loop over assessments
foreach ($data['assessments'] as $assessment) {
    // Retrieve course information
    $math_course = $assessment['courses']['math'];
    $science_course = $assessment['courses']['science'];

    // Query Moodle database for course ID
    $math_course_id = $DB->get_field('course', 'id', ['id_number' => $math_course['id_number']]);
    $science_course_id = $DB->get_field('course', 'id', ['id_number' => $science_course['id_number']]);

    // Query course sections for strong and weak topics
    $math_strong_section_id = $DB->get_field('course_sections', 'id', ['course' => $math_course_id, 'name' => $math_course['strong_topic']]);
    $math_weak_section_id = $DB->get_field('course_sections', 'id', ['course' => $math_course_id, 'name' => $math_course['weak_topic']]);
    $science_strong_section_id = $DB->get_field('course_sections', 'id', ['course' => $science_course_id, 'name' => $science_course['strong_topic']]);
    $science_weak_section_id = $DB->get_field('course_sections', 'id', ['course' => $science_course_id, 'name' => $science_course['weak_topic']]);

    // Store data in recommended_fields table
    $recommended_topic_math = new stdClass();
    $recommended_topic_math->course_id = $math_course_id;
    $recommended_topic_math->user_id = $current_user_id; // Use the current user's ID
    $recommended_topic_math->subject = 'MATH';
    $recommended_topic_math->weak_topic = $math_weak_section_id;
    $recommended_topic_math->strong_topic = $math_strong_section_id;

    $recommended_topic_science = new stdClass();
    $recommended_topic_science->course_id = $science_course_id;
    $recommended_topic_science->user_id = $current_user_id; // Use the current user's ID
    $recommended_topic_science->subject = 'SCIENCE';
    $recommended_topic_science->weak_topic = $science_weak_section_id;
    $recommended_topic_science->strong_topic = $science_strong_section_id;

    $DB->insert_record('recommended_fields', $recommended_topic_math);
    $DB->insert_record('recommended_fields', $recommended_topic_science);
}
