<?php
require_once(__DIR__ . '/config.php');


require_once('/Users/srimoy/Desktop/DWBlocal/course/format/classes/output/local/content/section/header.php');
require_once($CFG->libdir . '/outputrenderers.php');

require_once($CFG->dirroot . '/course/format/lib.php');
require_once($CFG->libdir . '/modinfolib.php');

$course_id = 2; 
$section = 6; 

$course = get_course($course_id);
$modinfo = get_fast_modinfo($course);


$sectioninfo = $modinfo->get_section_info($section_id);

$sectionformatoptions = array('modinfo' => $modinfo);
$format = course_get_format($course, $sectionformatoptions);
// var_dump($header);
// die;
$data->title = $output->section_title_without_link($section, $course);


$header = new core_courseformat\output\local\content\section\header($format, $sectioninfo);
$dummy_page = new core_renderer(new moodle_page(), 'dummy_target');
[$complete, $total] = $header->calculate_section_stats();

echo "Complete: $complete, Total: $total<br>";

$progress_percentage = ($total > 0) ? round(($complete / $total) * 100, 2) : 0;


echo "Progress percentage: $progress_percentage%<br>";


$data = $header->export_for_template($dummy_page);$prog = $data->prog;


echo "Progress: $prog<br>";
?>
