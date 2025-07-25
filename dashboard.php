<?php
// === Moodle Setup and Imports ===
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once(__DIR__ . '/classes/dashboardlib.php');
use gradereport_rubrics\dashboardlib;
// === Required Parameters (for context) ===
$activityid = required_param('activityid', PARAM_INT); // assignment ID
$courseid = required_param('id', PARAM_INT); // course ID
$course = get_course($courseid); // course object
$cm = get_coursemodule_from_id(null, $activityid, $courseid, false, MUST_EXIST); // course module object
// === Login and Context Setup ===
require_login($courseid);
$context = context_course::instance($courseid);
// === Moodle Page Settings ===
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/grade/report/rubrics/dashboard.php', ['id' => $courseid]));
$PAGE->set_title('Teacher Dashboard');
$PAGE->set_heading('Teacher Dashboard');
// === Page Header ===
echo $OUTPUT->header();
echo html_writer::start_div('dashboard-header');

echo html_writer::tag('h1', "Teacher’s Dashboard", ['class' => 'dashboard-title']);

echo html_writer::start_tag('div', ['class' => 'dashboard-subinfo']);
echo html_writer::tag('p', "📘 Course: " . format_string($course->fullname), ['class' => 'dashboard-subtext']);
echo html_writer::tag('p', "📄 Assignment: " . format_string($cm->name), ['class' => 'dashboard-subtext']);
echo html_writer::end_tag('div');

echo html_writer::end_div();

// === Load Chart Logic ===

//include_once(__DIR__ . '/criteria_chart.php');
//use gradereport_rubrics\chartdata;
//$criteriascores = chartdata::get_criteria_score_data($course, $cm);


// === Load External Libraries ===
echo html_writer::tag('script', '', [
    'type' => 'text/javascript',
    'src' => 'https://cdn.jsdelivr.net/npm/chart.js'
]); //charts

echo html_writer::tag('link', '', [
    'rel' => 'stylesheet',
    'type' => 'text/css',
    'href' => new moodle_url('/grade/report/rubrics/assets/datatables/datatables.min.css')
]); //tables

echo html_writer::tag('script', '', [
    'type' => 'text/javascript',
    'src' => new moodle_url('/grade/report/rubrics/assets/datatables/datatables.min.js')
]); //tables

echo html_writer::tag('script', '', [
    'type' => 'text/javascript',
    'src' => new moodle_url('/grade/report/rubrics/assets/datatables/datatables.js')
]); //tables

echo html_writer::tag('link', '', [
    'rel' => 'stylesheet',
    'type' => 'text/css',
    'src' => new moodle_url('/grade/report/rubrics/assets/datatables/datatables.css')
]); //tables

echo html_writer::tag('link', '', [
    'rel' => 'stylesheet',
    'type' => 'text/css',
    'href' => new moodle_url('/grade/report/rubrics/assets/datatables/custom.css')
]); //tables (my custom adjustments)

// === Assignment Stats (Top Overview) ===
$search = optional_param('search', '', PARAM_TEXT);
$rows = dashboardlib::get_student_table_data($course, $cm, $search);
$data = dashboardlib::get_assignment_data($course, $cm);

echo html_writer::div("
  <div class='dashboard_stats'>
    <div class='overall_specs_container'>
      <div class='row'>Submitted: {$data['submitted']}</div>
      <div class='row'>Graded:   {$data['graded']}</div>
      <div class='row'>Average:  {$data['average']}%</div>
    </div>
    <div class='chart_container'>
      <canvas id='criteriaChart'></canvas>
    </div>
  </div>
", '');

// Wrap the entire chart logic in one container for CSS:
echo html_writer::start_div('dashboard-chart-wrapper');
  include_once(__DIR__ . '/criteria_chart.php');
echo html_writer::end_div();

// === Student Table ===
$tabledata = dashboardlib::get_student_table_data($course, $cm, $search);
$table = new html_table();
$table->head = ['Student ID', 'Student Name', 'Status', 'Grade', 'Details'];
$table->data = [];


foreach ($tabledata as $row) {


    $table->data[] = [
        $row['student_id'],
        $row['fullname'],
        $row['status'],
        $row['grade'],
        $row['button']  // <-- replace raw feedback text with your button
    ];
}

echo html_writer::table($table);

// === DataTables Initialization Script ===
echo html_writer::script("
    document.addEventListener('DOMContentLoaded', function() {
        let table = document.querySelector('table.generaltable');
        if (table) {
            new DataTable(table, {
                dom: '<\"d-flex justify-content-between align-items-center mb-3\"fB>rtip',
                buttons: [
                    {
                        extend: 'collection',
                        text: '<img src=\"/grade/report/rubrics/assets/datatables/download.png\">',
                        buttons: ['copy', 'csv', 'pdf']
                    }
                ],
                language: {
                    search: '',
                    searchPlaceholder: 'Search by name or student ID'
                },
                paging: true,
                ordering: true
            });
        }
    });
");

// === Footer ===
echo $OUTPUT->footer();
