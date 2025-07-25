<?php
// This file handles DB upgrades when the plugin version changes.

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade code for gradereport_rubrics plugin.
 *
 * @param int $oldversion The plugin version being upgraded from.
 * @return bool success
 */
function xmldb_gradereport_rubrics_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Example: If the previous version is less than 2025072500, create the table.
    if ($oldversion < 2025072500) {
        // Define table rubric_grade_edits.
        $table = new xmldb_table('rubric_grade_edits');

        // Define fields.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('submissionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('criterionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('oldscore', XMLDB_TYPE_NUMBER, '10,2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('newscore', XMLDB_TYPE_NUMBER, '10,2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('comment', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('editorid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Add keys and indexes.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('rubricedit_userid_idx', XMLDB_INDEX_NOTUNIQUE, ['userid']);
        $table->add_index('rubricedit_submission_idx', XMLDB_INDEX_NOTUNIQUE, ['submissionid']);

        // Only create if it doesn't exist yet.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Mark the upgrade as done by setting the new version.
        upgrade_plugin_savepoint(true, 2025072500, 'gradereport', 'rubrics');
    }

    return true;
}
