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

// Course_module ID, or.
$id = optional_param('id', 0, PARAM_INT);

// ... module instance id.
$l = optional_param('l', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('lanebs', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('lanebs', ['id' => $cm->instance], '*', MUST_EXIST);
} else if ($l) {
    $moduleinstance = $DB->get_record('lanebs', ['id' => $l], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $moduleinstance->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('lanebs', $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    throw new moodle_exception('missingidandcmid', 'mod_lanebs');
}

require_login($course, true, $cm);

if (isset($_GET['apikey'])) {
    // Checking that the key is in the database. Проверяю что ключ есть в базе.
    $apikeyexists = $DB->record_exists('studentlibrary_apikey', ['apikey' => $_GET['apikey']]);
    if ($apikeyexists) {
        $apikeydata = $DB->get_record('studentlibrary_apikey', ['apikey' => $_GET['apikey']], '*', MUST_EXIST);
        $result = new stdClass();
        $result->course = $apikeydata->course;
        $result->module = $apikeydata->module;
        $result->userid = $apikeydata->user;
        $result->total = $_GET['total'];
        $result->score = $_GET['score'];
        $result->report = $_GET['report'];
        $result->modified = time();
        $DB->delete_records('studentlibrary_apikey', ['apikey' => $_GET['apikey']], '*', MUST_EXIST);
        echo('{"status":"ok"}');
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
