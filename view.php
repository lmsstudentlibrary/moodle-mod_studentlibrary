

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

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/locallib.php');
$id = optional_param('id', 0, PARAM_INT);
$cm             = get_coursemodule_from_id('studentlibrary', $id, 0, false, MUST_EXIST);
$course         = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$moduleinstance = $DB->get_record('studentlibrary', ['id' => $cm->instance], '*', MUST_EXIST);
require_login($course, true, $cm);
$modulecontext = context_module::instance($cm->id);
$event = \mod_studentlibrary\event\course_module_viewed::create([
    'objectid' => $moduleinstance->id,
    'context' => $modulecontext,
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('studentlibrary', $moduleinstance);
$event->trigger();
$PAGE->set_url('/mod/studentlibrary/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);
$PAGE->requires->css('/mod/studentlibrary/css/style.css');
echo $OUTPUT->header();
$content = $moduleinstance->intro . "<br>";
$content .= get_lib_url($moduleinstance->booke, $moduleinstance->ised);
echo $OUTPUT->box($content, "generalbox center clearfix");
echo $OUTPUT->footer();
