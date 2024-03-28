<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Extend navigation for custom page plugin.
 *
 * @param navigation_node $root The navigation node to extend.
 * @param stdClass|null $course The course object.
 * @param stdClass|null $module The module object.
 * @param cm_info|null $cm The course module object.
 */
function local_custompage_extend_navigation($root, $course = null, $module = null, $cm = null) {
    if ($root instanceof global_navigation) {
        $url = new moodle_url('/local/custompage/teachertable.php');
        $node = $root->add(get_string('teachernode', 'local_custompage'), $url, navigation_node::TYPE_CUSTOM, null, null, new pix_icon('i/custompage', ''));
    }
}
// function local_custompage_extend_navigation($root, $course = null, $module = null, $cm = null) {
//     if ($root instanceof global_navigation) {
//         // Add a new navigation node next to "Site administration"
//         $node = $root->add(get_string('student_progress', 'local_custompage'), new moodle_url('/local/custompage/student_progress.php'), navigation_node::TYPE_CUSTOM, null, 'student_progress');
        
//         // Set the custom class to style the navigation item if needed
//         // $node->add_class('custom-class');

//         // Set the selected state if the current page is the Student Progress page
//         global $PAGE;
//         if ($PAGE->url->compare(new moodle_url('/local/custompage/student_progress.php'))) {
//             $node->make_active();
//         }
//     }
// }