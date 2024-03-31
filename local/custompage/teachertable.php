<?php
require_once('/Users/srimoy/Desktop/moodle/config.php');

global $DB, $USER;


$userid = $USER->id;


$sql = "SELECT * FROM {role} AS er 
        JOIN {role_assignments} AS era ON era.roleid = er.id 
        WHERE era.userid = ?";
$params = array($userid);
$is_teacher = $DB->record_exists_sql($sql, $params);

if ($is_teacher) {

    $recommended_topics = $DB->get_records_sql('SELECT DISTINCT course_id FROM {recommended_topics}');

    if (!empty($recommended_topics)) {
        foreach ($recommended_topics as $topic) {
            $course_id = $topic->course_id;

        
            $course = $DB->get_record('course', array('id' => $course_id));

            if ($course) {
               
                $idnumber = $course->idnumber;

             
                $assessment_name = str_replace('_', ' ', $idnumber);

                $user_school_info = $DB->get_records('user_school_info', array('course_id' => $course_id));
                $usernames = $DB->get_records_menu('user', null, '', 'id, username');

                foreach ($user_school_info as $user) {
                  
                    $weakest_topic = $DB->get_field('recommended_topics', 'weak_topic', ['user_id' => $user->user_id]);
                    $user->weakest_topic = $weakest_topic ? $weakest_topic : 'N/A';

                 
                    $username = isset($usernames[$user->user_id]) ? $usernames[$user->user_id] : 'N/A';
                    $user->username = $username;

                    
                    $course = new stdClass();
                    $course->id = $course_id;
                    $progress_percentage = \core_completion\progress::get_course_progress_percentage($course, $user->user_id);
                    $user->progress_percentage = $progress_percentage !== null ? number_format($progress_percentage, 2) . '%' : 'N/A';

                 
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

            
                $data = json_encode($user_school_info, JSON_HEX_QUOT | JSON_HEX_TAG);

            
                $jsFilePath = '/Users/srimoy/Desktop/moodle/local/custompage/teachertable.js';
                file_put_contents($jsFilePath, "var userData = $data;");

                $html = file_get_contents(__DIR__ . '/teachertable.html');

                echo $html;
            } else {
              
                echo "Course record not found for course ID $course_id.";
            }
        }
    } else {
     
        echo "There are no recommended topics.";
    }
} else {
    
    echo "You are not authorized to access this page.";
}
?>
