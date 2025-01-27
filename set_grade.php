<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Plugin administration pages are defined here.
 *
 * @package     mod_studentlibrary
 * @category    admin
 * @copyright   2025 <plagin@geotar.ru>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Get data. Получение данных.
require_once(__DIR__ . '/../../config.php');
global $DB, $CFG;
$apikey = optional_param('apikey', '', PARAM_TEXT);
$total = optional_param('total', -1, PARAM_INT);
$score = optional_param('score', -1 , PARAM_INT);
$report = optional_param('report', '', PARAM_URL);
if ( !empty($apikey) && !empty($total) && !empty($score) && !empty($report) ) {
    // Checking that the key is in the database. Проверяю что ключ есть в базе.
    $apikeyexists = $DB->record_exists('studentlibrary_apikey', ['apikey' => $apikey]);
    if ($apikeyexists) {
        $apikeydata = $DB->get_record('studentlibrary_apikey', ['apikey' => $apikey], '*', MUST_EXIST);
        $cm = get_coursemodule_from_id('studentlibrary', $apikeydata->module, 0, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $apikeydata->course], '*', MUST_EXIST);
        require_login($course, true, $cm);
        $result = new stdClass();
        $result->course = $apikeydata->course;
        $result->module = $apikeydata->module;
        $result->userid = $apikeydata->user;
        $result->total = $total;
        $result->score = $score;
        $result->report = $report;
        $result->modified = time();
        $lastinsertid = $DB->insert_record('studentlibrary_results', $result, false);
        $DB->delete_records('studentlibrary_apikey', ['apikey' => $apikey], '*', MUST_EXIST);
        echo ('{"status":"ok"}');
        $urltogo = $CFG->wwwroot . '/mod/studentlibrary/view.php?id=' . $apikeydata->module;
        redirect($urltogo);
    } else {
        $urltogo = $CFG->wwwroot . '/';
        redirect($urltogo);
    }
} else {
    $urltogo = $CFG->wwwroot . '/';
    redirect($urltogo);
}
