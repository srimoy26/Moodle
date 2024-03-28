<?php
require_once('/Users/srimoy/Desktop/moodle/config.php');

global $DB, $PAGE,$USER;

$userid = $USER->id;
$sql = "SELECT * FROM {role} AS er 
        JOIN {role_assignments} AS era ON era.roleid = er.id 
        WHERE era.userid = ?";
$params = array($userid);
$rec = $DB->get_record_sql($sql, $params);
$showitem;
//     var_dump($rec
// );
//     die;


$jsFilePath = '/Users/srimoy/Desktop/moodle/local/custompage/teachertable.js';
$existingData = file_exists($jsFilePath) ? file_get_contents($jsFilePath) : '';

$user_school_info = $DB->get_records('user_school_info');
$usernames = $DB->get_records_menu('user', null, '', 'id, username');
$recommended_topics = [];

foreach ($user_school_info as $user) {
    $sql = "SELECT weak_topic FROM {recommended_topics} WHERE user_id = ?";
    $weakest_topic = $DB->get_field_sql($sql, array($user->user_id));
    $recommended_topics[$user->user_id] = $weakest_topic;
    $weakest_topic = isset($recommended_topics[$user->user_id]) ? $recommended_topics[$user->user_id] : 'N/A';

    $username = isset($usernames[$user->user_id]) ? $usernames[$user->user_id] : 'N/A';
    $user->username = $username;
    $user->weakest_topic = $weakest_topic;

    $course_id = 2;

    $course = new stdClass();
    $course->id = $course_id;
    $progress_percentage = \core_completion\progress::get_course_progress_percentage($course, $user->user_id);

    $progress_percentage_display = $progress_percentage !== null ? number_format($progress_percentage, 2) . '%' : 'N/A';
    $user->progress_percentage = $progress_percentage_display;

    $sql_weak_section_progress = "SELECT rt.weak_section_id AS section_id, s.name AS section_name,
                                       SUM(CASE WHEN cmc.completionstate = 1 THEN 1 ELSE 0 END) / COUNT(cm.id) * 100 AS progress_percentage
                                FROM {recommended_topics} rt
                                JOIN {course_sections} s ON rt.weak_section_id = s.id
                                LEFT JOIN {course_modules} cm ON s.id = cm.section
                                LEFT JOIN {course_modules_completion} cmc ON cm.id = cmc.coursemoduleid AND cmc.userid = rt.user_id
                                WHERE rt.user_id = ?
                                GROUP BY rt.weak_section_id, s.name";

    $weak_section_progress = $DB->get_record_sql($sql_weak_section_progress, array($user->user_id));

    $user->weak_section_progress_percentage = isset($weak_section_progress->progress_percentage) ? number_format($weak_section_progress->progress_percentage, 2) . '%' : 'N/A';
}


// $data = json_encode($user_school_info, JSON_HEX_QUOT | JSON_HEX_TAG);


// file_put_contents($jsFilePath, "var userData = $data;");

$html = file_get_contents(__DIR__ . '/teachertable.html');

echo $html;
?>
