<?php
// grade/report/rubrics/detail_features.php
// Detail view with editable scores and ability to save changes. 

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->libdir . '/filelib.php');

// Required parameters
$courseid   = required_param('id', PARAM_INT);
$activityid = required_param('activityid', PARAM_INT);
$userid     = required_param('userid', PARAM_INT);
$download   = optional_param('download', 0, PARAM_INT);

global $DB, $USER;
$course = get_course($courseid);
$cm     = get_coursemodule_from_id('assign', $activityid, $courseid, false, MUST_EXIST);

require_login($course);
$context = context_module::instance($cm->id);
require_capability('mod/assign:grade', $context);


// get submission 
$submission = $DB->get_record('assign_submission', [
    'userid'     => $userid,
    'assignment' => $cm->instance,
    'latest'     => 1
], '*', IGNORE_MISSING);
if (!$submission) {
    print_error('nosubmission', 'gradereport_rubrics');
}


// file download
if ($download) {
    $stored = get_file_storage()->get_file_by_id($download);
    if (!$stored) {
        throw new moodle_exception('invalidfile', 'error');
    }
    send_stored_file($stored, 0, 0, true);
}

// get user who submitted
$user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
$fullname = fullname($user);

// Page setup
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/grade/report/rubrics/detail_features.php', [
    'id'         => $courseid,
    'activityid' => $activityid,
    'userid'     => $userid
]));
$PAGE->set_title('Submission Detail');
$PAGE->set_heading(format_string($course->fullname));


// header output
echo $OUTPUT->header();

//Submitted by 
echo html_writer::tag('h5', get_string('submittedby', 'gradereport_rubrics') . ': ' . s($fullname));

// DOWNLOAD BUTTON 
echo html_writer::tag('h4', get_string('submittedfiles', 'gradereport_rubrics'));
$fs    = get_file_storage();
$files = $fs->get_area_files($context->id, 'assignsubmission_file', 'submission_files', $submission->id, 'sortorder', false);
foreach ($files as $f) {
    if ($f->is_directory()) continue;
    $dl = new moodle_url('/grade/report/rubrics/detail_features.php', ['id'=>$courseid,'activityid'=>$activityid,'userid'=>$userid,'download'=>$f->get_id()]);
    echo html_writer::link($dl, $f->get_filename(), ['class'=>'btn btn-sm btn-outline-secondary mb-1']);
}

echo html_writer::tag('h4', 'Rubric Evaluation');

// === RATING BOX ===
// This shows overall rubric feedback and per-criterion scores + comments.

echo html_writer::start_div('rubric-breakdown-container');

// --- GET OVERALL GRADE ---
$assigngrade = $DB->get_record('assign_grades', [
    'assignment' => $cm->instance,
    'userid' => $userid
], '*', IGNORE_MISSING);

if ($assigngrade) {
    echo html_writer::tag('h4', get_string('rubricbreakdown', 'gradereport_rubrics'), ['class' => 'rubric-heading']);

    echo html_writer::start_div('rubric-summary-box');

    echo html_writer::tag('div', 'Rubric Grade: ' . number_format($assigngrade->grade, 2), ['class' => 'rubric-grade']);

    // Get overall feedback from assignfeedback_comments table
    $feedback = $DB->get_record('assignfeedback_comments', [
        'grade' => $assigngrade->id
    ], '*', IGNORE_MISSING);

    if ($feedback && !empty($feedback->commenttext)) {
        echo html_writer::div(
            format_text($feedback->commenttext, $feedback->commentformat),
            'rubric-general-feedback'
        );
    }

    echo html_writer::end_div(); // rubric-summary-box


    if ($assigngrade) {

        // 2. Get the rubric fillings for this instance
        $fillings = $DB->get_records('gradingform_rubric_fillings', ['instanceid' => $assigngrade->id]);

        // 3. Load all rubric levels and criteria
        $levels = $DB->get_records('gradingform_rubric_levels');
        $criteria = $DB->get_records('gradingform_rubric_criteria');

        $level_lookup = [];
        foreach ($levels as $lvl) {
            $level_lookup[$lvl->id] = $lvl;
        }

        $criterion_lookup = [];
        foreach ($criteria as $crit) {
            $criterion_lookup[$crit->id] = $crit;
        }

        // 4. Output each criterion
        echo html_writer::start_div('rubric-criteria-container');

        foreach ($fillings as $fill) {
            $criterion = $criterion_lookup[$fill->criterionid] ?? null;
            $level = $level_lookup[$fill->levelid] ?? null;

            if ($criterion && $level) {
                echo html_writer::start_div('rubric-criterion-box');

                echo html_writer::tag('h5', format_string($criterion->description), ['class' => 'criterion-title']);

                echo html_writer::div(
                    'Score: ' . number_format($level->score, 2) . ' – ' . format_string($level->definition),
                    'criterion-score'
                );

                if (!empty($fill->remark)) {
                    echo html_writer::div(
                        'Comment: ' . format_text($fill->remark, $fill->remarkformat),
                        'criterion-comment'
                    );
                }

                echo html_writer::end_div(); // rubric-criterion-box
            }
        }

        echo html_writer::end_div(); // rubric-criteria-container

    } else {
        echo html_writer::div('No finalized rubric grading found.', 'alert alert-warning');
    }

} else {
    echo html_writer::div('No grade data found for this user.', 'alert alert-danger');
}

echo html_writer::end_div(); // rubric-breakdown-container



echo $OUTPUT->footer();
