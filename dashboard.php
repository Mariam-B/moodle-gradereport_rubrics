<?php
require_once(__DIR__ . '/../../../config.php');

$activityid = required_param('activityid', PARAM_INT);
$courseid = required_param('id', PARAM_INT); // Moodle grade reports use ?id=courseid

$course=get_course($courseid);
$cm = get_coursemodule_from_id(null, $activityid, $courseid, false, MUST_EXIST);

require_login($courseid);
$context = context_course::instance($courseid);


$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/grade/report/rubrics/dashboard.php', ['id' => $courseid]));
$PAGE->set_title('Teacher Dashboard');
$PAGE->set_heading('Teacher Dashboard');

echo $OUTPUT->header();
echo $OUTPUT->heading("Teacher’s Dashboard");
echo html_writer::div("Course: " . format_string($course->fullname), 'mb-1');
echo html_writer::div("Assignment: " . format_string($cm->name), 'mb-3');

$numsubmitted = "..."; // Get from assign_submission
$numgraded = "...";    // Get from grade_items or feedback table
$average = "...";      // From grade_grades or your plugin’s stored scores
echo html_writer::div("
  <div class='row'>
    <div class='col'>Submitted: $numsubmitted</div>
    <div class='col'>Graded: $numgraded</div>
    <div class='col'>Average: $average%</div>
  </div>", 'mb-4');

echo $OUTPUT->footer();