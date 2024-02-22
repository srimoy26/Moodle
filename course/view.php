<?php

//  Display the course home page.

    require_once('../config.php');
    require_once('lib.php');
    require_once($CFG->libdir.'/completionlib.php');
    

    redirect_if_major_upgrade_required();

    $id          = optional_param('id', 0, PARAM_INT);
    $name        = optional_param('name', '', PARAM_TEXT);
    $edit        = optional_param('edit', -1, PARAM_BOOL);
    $hide        = optional_param('hide', 0, PARAM_INT);
    $show        = optional_param('show', 0, PARAM_INT);
    $idnumber    = optional_param('idnumber', '', PARAM_RAW);
    $sectionid   = optional_param('sectionid', 0, PARAM_INT);
    $section     = optional_param('section', 0, PARAM_INT);
    $move        = optional_param('move', 0, PARAM_INT);
    $marker      = optional_param('marker',-1 , PARAM_INT);
    $switchrole  = optional_param('switchrole',-1, PARAM_INT); // Deprecated, use course/switchrole.php instead.
    $return      = optional_param('return', 0, PARAM_LOCALURL);

    $params = array();
    if (!empty($name)) {
        $params = array('shortname' => $name);
    } else if (!empty($idnumber)) {
        $params = array('idnumber' => $idnumber);
    } else if (!empty($id)) {
        $params = array('id' => $id);
    }else {
        throw new \moodle_exception('unspecifycourseid', 'error');
    }

    $course = $DB->get_record('course', $params, '*', MUST_EXIST);

    $urlparams = array('id' => $course->id);

    // Sectionid should get priority over section number
    if ($sectionid) {
        $section = $DB->get_field('course_sections', 'section', array('id' => $sectionid, 'course' => $course->id), MUST_EXIST);
    }
    if ($section) {
        $urlparams['section'] = $section;
    }

    $PAGE->set_url('/course/view.php', $urlparams); // Defined here to avoid notices on errors etc

    // Prevent caching of this page to stop confusion when changing page after making AJAX changes
    $PAGE->set_cacheable(false);

    context_helper::preload_course($course->id);
    $context = context_course::instance($course->id, MUST_EXIST);

    // Remove any switched roles before checking login
    if ($switchrole == 0 && confirm_sesskey()) {
        role_switch($switchrole, $context);
    }

    require_login($course);

    // Switchrole - sanity check in cost-order...
    $reset_user_allowed_editing = false;
    if ($switchrole > 0 && confirm_sesskey() &&
        has_capability('moodle/role:switchroles', $context)) {
        // is this role assignable in this context?
        // inquiring minds want to know...
        $aroles = get_switchable_roles($context);
        if (is_array($aroles) && isset($aroles[$switchrole])) {
            role_switch($switchrole, $context);
            // Double check that this role is allowed here
            require_login($course);
        }
        // reset course page state - this prevents some weird problems ;-)
        $USER->activitycopy = false;
        $USER->activitycopycourse = NULL;
        unset($USER->activitycopyname);
        unset($SESSION->modform);
        $USER->editing = 0;
        $reset_user_allowed_editing = true;
    }

    //If course is hosted on an external server, redirect to corresponding
    //url with appropriate authentication attached as parameter
    if (file_exists($CFG->dirroot .'/course/externservercourse.php')) {
        include $CFG->dirroot .'/course/externservercourse.php';
        if (function_exists('extern_server_course')) {
            if ($extern_url = extern_server_course($course)) {
                redirect($extern_url);
            }
        }
    }


    require_once($CFG->dirroot.'/calendar/lib.php');    /// This is after login because it needs $USER

    // Must set layout before gettting section info. See MDL-47555.
    $PAGE->set_pagelayout('course');
    $PAGE->add_body_class('limitedwidth');

    if ($section and $section > 0) {

        // Get section details and check it exists.
        $modinfo = get_fast_modinfo($course);
        $coursesections = $modinfo->get_section_info($section, MUST_EXIST);




        // Check user is allowed to see it.
        if (!$coursesections->uservisible) {
            // Check if coursesection has conditions affecting availability and if
            // so, output availability info.
            if ($coursesections->visible && $coursesections->availableinfo) {
                $sectionname     = get_section_name($course, $coursesections);
                $message = get_string('notavailablecourse', '', $sectionname);
                redirect(course_get_url($course), $message, null, \core\output\notification::NOTIFY_ERROR);
            } else {
                // Note: We actually already know they don't have this capability
                // or uservisible would have been true; this is just to get the
                // correct error message shown.
                require_capability('moodle/course:viewhiddensections', $context);
            }
        }
    }

    // Fix course format if it is no longer installed
    $format = course_get_format($course);
    $course->format = $format->get_format();
    $PAGE->requires->js_call_amd('core_course/tab', 'Tab');
  

    $PAGE->set_pagetype('course-view-' . $course->format);
    $PAGE->set_other_editing_capability('moodle/course:update');
    $PAGE->set_other_editing_capability('moodle/course:manageactivities');
    $PAGE->set_other_editing_capability('moodle/course:activityvisibility');
    if (course_format_uses_sections($course->format)) {
        $PAGE->set_other_editing_capability('moodle/course:sectionvisibility');
        $PAGE->set_other_editing_capability('moodle/course:movesections');
    }

    // Preload course format renderer before output starts.
    // This is a little hacky but necessary since
    // format.php is not included until after output starts
    $format->get_renderer($PAGE);

    if ($reset_user_allowed_editing) {
        // ugly hack
        unset($PAGE->_user_allowed_editing);
    }

    if (!isset($USER->editing)) {
        $USER->editing = 0;
    }
    if ($PAGE->user_allowed_editing()) {
        if (($edit == 1) and confirm_sesskey()) {
            $USER->editing = 1;
            // Redirect to site root if Editing is toggled on frontpage
            if ($course->id == SITEID) {
                redirect($CFG->wwwroot .'/?redirect=0');
            } else if (!empty($return)) {
                redirect($CFG->wwwroot . $return);
            } else {
                $url = new moodle_url($PAGE->url, array('notifyeditingon' => 1));
                redirect($url);
            }
        } else if (($edit == 0) and confirm_sesskey()) {
            $USER->editing = 0;
            if(!empty($USER->activitycopy) && $USER->activitycopycourse == $course->id) {
                $USER->activitycopy       = false;
                $USER->activitycopycourse = NULL;
            }
            // Redirect to site root if Editing is toggled on frontpage
            if ($course->id == SITEID) {
                redirect($CFG->wwwroot .'/?redirect=0');
            } else if (!empty($return)) {
                redirect($CFG->wwwroot . $return);
            } else {
                redirect($PAGE->url);
            }
        }

        if (has_capability('moodle/course:sectionvisibility', $context)) {
            if ($hide && confirm_sesskey()) {
                set_section_visible($course->id, $hide, '0');
                redirect($PAGE->url);
            }

            if ($show && confirm_sesskey()) {
                set_section_visible($course->id, $show, '1');
                redirect($PAGE->url);
            }
        }

        if (!empty($section) && !empty($move) &&
                has_capability('moodle/course:movesections', $context) && confirm_sesskey()) {
            $destsection = $section + $move;
            if (move_section_to($course, $section, $destsection)) {
                if ($course->id == SITEID) {
                    redirect($CFG->wwwroot . '/?redirect=0');
                } else {
                    if ($format->get_course_display() == COURSE_DISPLAY_MULTIPAGE) {
                        redirect(course_get_url($course));
                    } else {
                        redirect(course_get_url($course, $destsection));
                    }
                }
            } else {
                echo $OUTPUT->notification('An error occurred while moving a section');
            }
        }
    } else {
        $USER->editing = 0;
    }

    $SESSION->fromdiscussion = $PAGE->url->out(false);


    if ($course->id == SITEID) {
        // This course is not a real course.
        redirect($CFG->wwwroot .'/?redirect=0');
    }

    // Determine whether the user has permission to download course content.
    $candownloadcourse = \core\content::can_export_context($context, $USER);

    // We are currently keeping the button here from 1.x to help new teachers figure out
    // what to do, even though the link also appears in the course admin block.  It also
    // means you can back out of a situation where you removed the admin block. :)
    if ($PAGE->user_allowed_editing()) {
        $buttons = $OUTPUT->edit_button($PAGE->url);
        $PAGE->set_button($buttons);
    }

    $editingtitle = '';
    if ($PAGE->user_is_editing()) {
        // Append this to the page title's lang string to get its equivalent when editing mode is turned on.
        $editingtitle = 'editing';
    }

    // If viewing a section, make the title more specific
    if ($section and $section > 0 and course_format_uses_sections($course->format)) {
        $sectionname = get_string('sectionname', "format_$course->format");
        $sectiontitle = get_section_name($course, $section);
        $PAGE->set_title(get_string('coursesectiontitle' . $editingtitle, 'moodle', array(
            'course' => $course->fullname, 'sectiontitle' => $sectiontitle, 'sectionname' => $sectionname)
        ));
    } else {
        $PAGE->set_title(get_string('coursetitle' . $editingtitle, 'moodle', array('course' => $course->fullname)));
    }

    $PAGE->set_heading($course->fullname);
    echo $OUTPUT->header();

    $PAGE->requires->js_call_amd('core_course/view', 'init');

class UserTopicsDisplay {
    
    public function __construct() {
        
    }

    // public function displayUserTopics($user) {
    //     $userId = $this->getUserId($user);
    //     $strongTopics = $this->get_user_strong_topics($userId);
    //     $weakTopics = $this->get_user_weak_topics($userId);
    
    //     echo '<div class="topic-tabs" style="display: flex; flex-direction: column; align-items: center; width: 342px;">';
    //     echo '<h3 style="color: #333; font-size: 20px;margin-right:10rem;">Select Courses</h3>';
    //     echo '<div style="display: flex; width: 100%;">'; // Wrap buttons in a container for flex layout
    //     echo '<button id="weakTopicsBtn" class="tablink active" style="flex: 1; width: 169px; height: 42px; background-color: #007bff; border: none; color: #fff; padding: 10px 20px; font-size: 16px; cursor: pointer; border-radius: 16px 0 0 16px;" onclick="openTab(event, \'weakTopics\')">Weak Topics</button>';
    //     echo '<button id="allTopicsBtn" class="tablink" style="flex: 1; width: 169px; height: 42px; background-color: #f2f2f2; border: none; color: #333; padding: 10px 20px; font-size: 16px; cursor: pointer; border-radius: 0 16px 16px 0;" onclick="openTab(event, \'allTopics\')">All Topics</button>';
    //     echo '</div>'; // End of buttons container
    //     echo '</div>';
        

        
        

    //     // Display weak topics
    //     echo '<div id="weakTopics" class="tabcontent">';
    //     // Display weak topics content...
    //     echo '</div>';
    
    //     // Display all topics
    //     echo '<div id="allTopics" class="tabcontent">';
    //     // Display all topics content...
    //     echo '</div>';
    
    //     // JavaScript function to switch tabs
    //     echo '<script>';
    //     echo 'function openTab(evt, tabName) {';
    //     echo '  var i, tabcontent, tablinks;';
    //     echo '  tabcontent = document.getElementsByClassName("tabcontent");';
    //     echo '  for (i = 0; i < tabcontent.length; i++) {';
    //     echo '    tabcontent[i].style.display = "none";';
    //     echo '  }';
    //     echo '  tablinks = document.getElementsByClassName("tablink");';
    //     echo '  for (i = 0; i < tablinks.length; i++) {';
    //     echo '    tablinks[i].className = tablinks[i].className.replace(" active", "");';
    //     echo '  }';
    //     echo '  document.getElementById(tabName).style.display = "block";';
    //     echo '  evt.currentTarget.className += " active";';
    //     echo '}';
    //     echo '</script>';
       
        
    // }


    public function displayUserTopics($user) {
       
        global $DB, $USER;
        $userId = $this->getUserId($user);
        $strongTopics = $this->get_user_strong_topics($userId);
        $weakTopics = $this->get_user_weak_topics($userId);
        $weakSectionId = intval($DB->get_field('recommended_topics', 'weak_section_id', ['user_id' => $USER->id, 'weak_section_id' => reset($weakTopics)->weak_section_id]));
      
        $activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'weakTopics';
   var_dump($weakSectionId);
       
        $js_function = <<<JS
<script>
function handleWeakButtonClick() {
    const weakButton = document.getElementById("weakButton");
    const allButton = document.getElementById('allButton');
    
    weakButton.style.color = "blue";
    weakButton.style.border = "2px solid blue";
    allButton.style.color = "#4f5e71";
    allButton.style.border = "none";
    var weakSectionId = '$weakSectionId';
    
   
    window.location.href = `http://moodle.test/course/view.php?id=2&section=${weakSectionId}`;
    
}

function handleAllButtonClick() {
    const weakButton = document.getElementById("weakButton");
    const allButton = document.getElementById('allButton');

    weakButton.style.color = "#4f5e71";
    weakButton.style.border = "none";
    allButton.style.color = "blue";
    allButton.style.border = "2px solid blue";
    
    // Prevent the default behavior of the button (page refresh)
    event.preventDefault();
    
    // Your redirection logic
    window.location.href = "http://moodle.test/course/view.php?id=2";
    var currentUrl = window.location.href;
if (currentUrl === "http://moodle.test/course/view.php?id=2") {
        // Adjust button styles accordingly
        weakButton.style.border = "none";
        allButton.style.border = "2px solid blue";}
    
}



</script>
JS;

echo $js_function;
        echo'<h5 id=$weakTopics>Select Topics</h5>';
        echo '<div class="button-container" style="width: 342px; display: inline-block; border-radius: 20px; overflow: hidden; border: 1px solid #bac7d5; background: white;">';

        echo '<button id="weakButton" class="filterButton" style="width: 169px; padding: 10px 20px; font-size: 14px; cursor: pointer; border: 2px solid blue; margin: 0; color: blue; font-weight: 600; font-family: Poppins; background-color: white; border-radius: 20px 20px 20px 20px;" onclick="handleWeakButtonClick()">Weak Topics</button>';
        echo '<button id="allButton" class="filterButton" style="width: 169px; padding: 10px 20px; font-size: 14px; cursor: pointer; border: none; margin: 0; color: #4f5e71; font-weight: 600; font-family: Poppins; background-color: white; border-radius: 20px 20px 20px 20px;" onclick="handleAllButtonClick()">All Topics</button>';
            echo'</div>';

    
    
// if ($activeTab === 'weakTopics') {
//     echo '<div class="tabcontent">';
//     foreach ($weakTopics as $weakTopic) {
//     }
//     echo '</div>';
// }

    
// if ($activeTab === 'allTopics') {
//     echo '<div class="tabcontent">';
//     foreach ($strongTopics as $strongTopic) {
//     }
//     echo '</div>';
// }

    }
    
    
    

    
    
    
    
    
    
    
    
    
    
    

    private function getUserId($user) {
        
        return $user->id;
    }

    private function get_user_strong_topics($user_id) {
        global $DB;

        $sql = "SELECT strong_topic FROM {recommended_topics} WHERE user_id = :user_id";
        $params = array('user_id' => $user_id);

        return $DB->get_records_sql($sql, $params);
    }

    private function get_user_weak_topics($user_id) {
        global $DB;

        $sql = "SELECT weak_section_id FROM {recommended_topics} WHERE user_id = :user_id";
        $params = array('user_id' => $user_id);

        return $DB->get_records_sql($sql, $params);
    }
}



$userTopicsDisplay = new UserTopicsDisplay();
$userTopicsDisplay->displayUserTopics($USER);


    if ($USER->editing == 1) {

        // MDL-65321 The backup libraries are quite heavy, only require the bare minimum.
        require_once($CFG->dirroot . '/backup/util/helper/async_helper.class.php');

        if (async_helper::is_async_pending($id, 'course', 'backup')) {
            echo $OUTPUT->notification(get_string('pendingasyncedit', 'backup'), 'warning');
        }
    }

    // Course wrapper start.
    echo html_writer::start_tag('div', array('class'=>'course-content'));

    // make sure that section 0 exists (this function will create one if it is missing)
    course_create_sections_if_missing($course, 0);

    // get information about course modules and existing module types
    // format.php in course formats may rely on presence of these variables
    $modinfo = get_fast_modinfo($course);
    $modnames = get_module_types_names();
    $modnamesplural = get_module_types_names(true);
    $modnamesused = $modinfo->get_used_module_names();
    $mods = $modinfo->get_cms();
    $sections = $modinfo->get_section_info_all();

    // CAUTION, hacky fundamental variable defintion to follow!
    // Note that because of the way course fromats are constructed though
    // inclusion we pass parameters around this way..
    $displaysection = $section;

    // Include course AJAX
    include_course_ajax($course, $modnamesused);

    // Include the actual course format.
    require($CFG->dirroot .'/course/format/'. $course->format .'/format.php');
    // Content wrapper end.

    echo html_writer::end_tag('div');
    
    // Trigger course viewed event.
    // We don't trust $context here. Course format inclusion above executes in the global space. We can't assume
    // anything after that point.
    
    course_view(context_course::instance($course->id), $section);

    // If available, include the JS to prepare the download course content modal.
    if ($candownloadcourse) {
        $PAGE->requires->js_call_amd('core_course/downloadcontent', 'init');
    }

    // Load the view JS module if completion tracking is enabled for this course.
    $completion = new completion_info($course);
    if ($completion->is_enabled()) {
        $PAGE->requires->js_call_amd('core_course/view', 'init');
    }



    

    echo $OUTPUT->footer();
