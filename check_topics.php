<?php
require_once(__DIR__ . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/lib/tablelib.php');

// Setup the Moodle context
$systemcontext = context_system::instance();
require_login();

global $USER, $DB;

// Get all users from the database
$all_users = $DB->get_records_sql('SELECT * FROM {user}');

// Display all users with their strong and weak topics
if (!empty($all_users)) {
    echo '<h2>All Users and Recommended Topics</h2>';
    foreach ($all_users as $user) {
        $user_id = $user->id;
        
        // Get the recommended topics for each user
        $recommended_topics = $DB->get_records_sql(
            'SELECT * FROM {recommended_topics} WHERE user_id = :user_id',
            ['user_id' => $user_id]
        );
        
        // Display user information
        echo '<p>User: ' . $user->username . '</p>';
        
        // Display recommended topics for the user
        if (!empty($recommended_topics)) {
            foreach ($recommended_topics as $topic) {
                echo '<p>Strong Topic: ' . $topic->strong_topic . '</p>';
                echo '<p>Weak Topic: ' . $topic->weak_topic . '</p>';
            }
        } else {
            echo '<p>No recommended topics found for this user.</p>';
        }
        
        echo '<hr>';
    }
} else {
    echo '<p>No users found.</p>';
}
?>
