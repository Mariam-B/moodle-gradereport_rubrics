<?php
namespace gradereport_rubrics;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper class providing data for charts on the teacher dashboard.
 */
class chartdata {
    /**
     * Gathers total rubric scores for each criterion across all submissions in the assignment.
     *
     * @param \stdClass $course Moodle course object.
     * @param \stdClass $cm     Course module (assignment) object.
     * @return array Associative array [criterion description => total score].
     */
    public static function get_criteria_score_data($course, $cm) {
    global $DB;

    // Get rubric setup
    $contextmodule = \context_module::instance($cm->id);
    $manager = get_grading_manager($contextmodule, 'mod_assign', 'submissions');
    $controller = $manager->get_active_controller();
    if (!($controller instanceof \gradingform_rubric_controller)) {
        return [];
    }

    $definition = $controller->get_definition();
    $criteria = $definition->rubric_criteria;

    // Map criterion ID → label
    $criteria_by_id = [];
    $scores = [];
    foreach ($criteria as $crit) {
        $criteria_by_id[$crit['id']] = $crit['description'];
        $scores[$crit['description']] = 0.0;
    }

    // Get latest submissions
    $submissions = $DB->get_records('assign_submission', [
        'assignment' => $cm->instance,
        'latest' => 1
    ]);
    if (empty($submissions)) return $scores;

    $submissionids = array_keys($submissions);

    // Get grading instances with status = 1 (finalized only!)
    list($in_sql, $params) = $DB->get_in_or_equal($submissionids, SQL_PARAMS_NAMED);
    $grading_instances = $DB->get_records_select(
        'grading_instances',
        "itemid $in_sql AND status = 1",
        $params
    );
    if (empty($grading_instances)) return $scores;

    $instanceids = array_keys($grading_instances);

    // Get all rubric fillings linked to those final grading instances
    list($in_sql2, $params2) = $DB->get_in_or_equal($instanceids, SQL_PARAMS_NAMED);
    $fills = $DB->get_records_select('gradingform_rubric_fillings', "instanceid $in_sql2", $params2);

    // Load all rubric levels
    $levels = $DB->get_records('gradingform_rubric_levels');
    $level_lookup = [];
    foreach ($levels as $lvl) {
        $level_lookup[$lvl->id] = [
            'criterionid' => $lvl->criterionid,
            'score' => (float)$lvl->score
        ];
    }

    // Map fills: one per submission + criterion (latest only)
    $latest_fillings = []; // [submissionid][criterionid] = fill
    foreach ($fills as $fill) {
        $submissionid = $grading_instances[$fill->instanceid]->itemid;
        $criterionid = $fill->criterionid;

        if (!isset($latest_fillings[$submissionid])) {
            $latest_fillings[$submissionid] = [];
        }

        // Only keep latest fill per criterion per submission
        if (
            !isset($latest_fillings[$submissionid][$criterionid]) ||
            $fill->id > $latest_fillings[$submissionid][$criterionid]->id
        ) {
            $latest_fillings[$submissionid][$criterionid] = $fill;
        }
    }

    // Final summing
    foreach ($latest_fillings as $submission_fills) {
        foreach ($submission_fills as $fill) {
            $levelid = $fill->levelid;
            if (!isset($level_lookup[$levelid])) continue;

            $criterionid = $level_lookup[$levelid]['criterionid'];
            $score = $level_lookup[$levelid]['score'];
            $desc = $criteria_by_id[$criterionid] ?? null;

            if ($desc !== null) {
                $scores[$desc] += $score;
            }
        }
    }

    return $scores;
}



}

