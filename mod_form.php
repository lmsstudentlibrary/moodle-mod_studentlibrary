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

use mod_forum\local\factories\vault;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/locallib.php');
/**
 * Module instance settings form.
 *
 * @package    mod_studentlibrary
 * @copyright  2020 itsup.biz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_studentlibrary_mod_form extends moodleform_mod {
    /**
     * Defines forms elements
     *
     * @return void
     */
    public function definition() {
        global $CFG, $PAGE, $USER, $SESSION;
        $serverapi = mod_studentlibrary_getmodconfig('serverapi');
        $orgid = get_config('mod_studentlibrary', 'studentlibrary_idorg');
        $agrid = get_config('mod_studentlibrary', 'studentlibrary_norg');
        if (substr($serverapi, -1) !== '/') {
            $serverapi = $serverapi . '/';
        }
        // We get the organization's session. Получаем сессию организации.
        $ssro = mod_studentlibrary_getssro($serverapi, $orgid, $agrid);
        // Getting the user's session. Получаем сессию пользователя.
        $ssrp = mod_studentlibrary_getssrp(
            $serverapi,
            $ssro,
            $USER->id,
            str_replace(' ', '_', $USER->lastname),
            str_replace(' ', '_', $USER->firstname)
        );
        // We get a set of books. Получаем набор книг.
        $kitslist = mod_studentlibrary_getkitslist($serverapi, $ssrp);
        $mform = $this->_form;
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', "Название", ['size' => '64']);
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'studentlibrary:studentlibraryname', 'mod_studentlibrary');
        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor();
        }
        $PAGE->requires->css('/mod/studentlibrary/css/style.css');
        if (!empty($SESSION->lang)) {
            if ($SESSION->lang !== null) {
                $lang = $SESSION->lang;
            } else {
                $lang = 'ru';
            }
        } else {
            $lang = 'ru';
        }
        $PAGE->requires->js_call_amd('mod_studentlibrary/find_form', 'init', [
            $serverapi,
            get_string('studentlibrary:kits_select', 'mod_studentlibrary'),
            get_string('studentlibrary:div_select', 'mod_studentlibrary'),
            get_string('studentlibrary:headerContent', 'mod_studentlibrary'),
            get_string('studentlibrary:find_material', 'mod_studentlibrary'),
            get_string('studentlibrary:add_material', 'mod_studentlibrary'),
            get_string('studentlibrary:search_bar', 'mod_studentlibrary'),
            get_string('studentlibrary:link_to_the_kit', 'mod_studentlibrary'),
            $lang,
            get_string('studentlibrary:id_error_booke_not_faind', 'mod_studentlibrary'),
            get_string('studentlibrary:id_error_booke', 'mod_studentlibrary'),
            get_string('studentlibrary:placeholder_s', 'mod_studentlibrary'),

        ]);
        $courseid = optional_param('course', 0, PARAM_INT);
        $section = optional_param('section', 0, PARAM_INT);
        if ($courseid !== 0) {
            $mform->addElement(
                'button',
                'search_button',
                get_string('studentlibrary:search_button', 'mod_studentlibrary'),
                [
                    'id' => 'search_button',
                    'data-courseid' => $courseid,
                    'data-token' => '',
                    'data-section' => $section,
                    'data-service' => '',
                    'ssr_o' => $ssro,
                    'ssr_p' => $ssrp,
                    'kitsList' => implode(",", $kitslist),
                    ]
                );
        }
        $mform->addElement(
            'text',
            'booke',
            get_string('studentlibrary:bookid', 'mod_studentlibrary'),
            [
                'size' => '64',
                'ssr_o' => $ssro,
                'ssr_p' => $ssrp,
                ]
        );
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('booke', PARAM_TEXT);
        } else {
            $mform->setType('booke', PARAM_CLEANHTML);
        }
        $div = '<div><a target="_blank" href="https://www.studentlibrary.ru/ru/pages/plagin.html">';
        $div .= get_string('studentlibrary:instruction', 'mod_studentlibrary') . '</a></div>';
        $mform->addElement('html', $div);
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}
