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
defined('MOODLE_INTERNAL') || die();

/**
 * Viewing a book.
 *
 * @param string $book Book id.
 * @return string Retutn context book page.
 */
function get_lib_url($book) {
    require_once(__DIR__ . '/../../config.php');
    global $DB, $USER, $PAGE,  $SESSION;
    if (!empty($SESSION->lang)) {
        if ($SESSION->lang !== null) {
            $lang = $SESSION->lang;
        } else {
            $lang = 'ru';
        }
    } else {
        $lang = 'ru';
    }
    $serverapi = get_mod_config('serverapi');
    $orgid = $DB->get_record('config', ['name' => 'studentlibrary_idorg'])->value;
    $agrid = $DB->get_record('config', ['name' => 'studentlibrary_norg'])->value;
    if (substr($serverapi, -1) !== '/') {
        $serverapi = $serverapi . '/';
    }
    // We get the organization's session. Получаем сессию организации.
    $ssro = getssro($serverapi, $orgid, $agrid);
    $url = $PAGE->url;
    $scheme = $url->get_scheme();
    $host = $url->get_host();
    $port = $url->get_port();
    if (strlen($port) > 0) {
        $urlapi = $scheme . '://' . $host . ':' . $port . '/mod/studentlibrary/set_grade.php';
    } else {
        $urlapi = $scheme . '://' . $host . '/mod/studentlibrary/set_grade.php';
    }
    // Generating an apikey. Генерируем apikey.
    $userid = $USER->id;
    $courseid = $PAGE->course->id;
    $moduleid = required_param('id', PARAM_INT);
    $timecreated = time();
    $apikey = hash('sha256', $orgid . $agrid . $userid . $courseid . $moduleid . $timecreated); /* 64 */
    $result = new stdClass();
    $result->course = $courseid;
    $result->module = $moduleid;
    $result->user = $userid;
    $result->timecreated = $timecreated;
    $result->apikey = $apikey;
    // Seconds per day. Секунд в дне.
    $unixday = 86400;
    // Deleting old records. Удаляем старые записи.
    if ($DB->record_exists('studentlibrary_apikey', ['course' => $courseid, 'module' => $moduleid, 'user' => $userid])) {
        $table = 'studentlibrary_apikey';
        $select = 'course = :course AND module = :module AND user = :user AND timecreated < :timecreated';
        $param = ['course' => $courseid, 'module' => $moduleid, 'user' => $userid, 'timecreated' => ($timecreated - $unixday)];
        $DB->delete_records_select($table, $select, $param);
    }
    // Getting the user's session. Получаем сессию пользователя.
    $ssrp = getssrp($serverapi, $ssro, $USER->id, str_replace(' ', '_', $USER->lastname), str_replace(' ', '_', $USER->firstname));
    if (strtolower(explode("/", $book)[0]) === 'switch_kit') {
        // We get a link to the Kit. Получаем ссылку на Комплект.
        $getaccesurl = '';
        $bookid = explode("/", $book)[1];
        $getaccesurl = $serverapi . "db";
        $getaccesurloprions = [
            "SSr" => $ssro,
            "guide" => "session",
            "cmd" => "solve",
            "action" => "seamless_access",
            "id" => $USER->id,
            "value.kit_id" => $bookid,
        ];
        $getaccesurl = $getaccesurl . '?' . 'SSr=' . $ssro . '&guide=session&cmd=solve&action=seamless_access&id=';
        $getaccesurl .= $USER->id . '&value.kit_id=' . $bookid;
        if ($getaccesurl != '') {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $getaccesurl);
            // We get a session. Получаем сессию.
            $rezxml = curl_exec($ch);
            curl_close($ch);
            $xml = simplexml_load_string($rezxml);
            $json = json_encode($xml);
            $array = json_decode($json, true);
            $bookitem = buildswitchkit($serverapi, $ssro, $bookid, $array["url"], $lang);
        }
    } else {
        // We get a link to the book. Получаем ссылку на книгу.
        $getaccesurl = '';
        $bookid = explode("/", $book)[1];
        for ($i = 2; $i < count(explode("/", $book)); $i++) {
            $bookid = $bookid . "/" . explode("/", $book)[$i];
        }
        $getaccesurl = $serverapi . "db";
        if (explode("/", $book)[0] === 'doc') {
            $getaccesurloprions = [
                "SSr" => $ssro,
                "guide" => "session",
                "cmd" => "solve",
                "action" => "seamless_access",
                "id" => $USER->id,
                "value.doc_id" => $bookid,
                "apikey" => $apikey,
                "url_api" => $urlapi,
            ];
        } else {
            $getaccesurloprions = [
                "SSr" => $ssro,
                "guide" => "session",
                "cmd" => "solve",
                "action" => "seamless_access",
                "id" => $USER->id,
                "value.book_id" => $bookid,
                "apikey" => $apikey,
                "url_api" => $urlapi,
            ];
        }
        if ($getaccesurl != '') {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $getaccesurl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($getaccesurloprions));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-type: application/json',
                'Accept: application/json',
            ]);
            $ssrxml = curl_exec($ch);
            curl_close($ch);
            $xml = simplexml_load_string($ssrxml);
            $json = json_encode($xml);
            $array = json_decode($json, true);
            $url = $array["url"];
            $DB->insert_record('studentlibrary_apikey', $result, false); /* записываем ключ */
        }
        $bookitem = buildbook($serverapi,  $ssrp, $book, $url);
    }
    return $bookitem;
}

/**
 * Build book page.
 *
 * @param string $server url server.
 * @param string $ssr user ssr.
 * @param string $bookid Book id.
 * @param string $url api url.
 * @return string Retutn context book card.
 */
function buildbook($server, $ssr, $bookid,  $url) {
    global $CFG, $SESSION;
    require_once($CFG->dirroot . '/course/moodleform_mod.php');
    require_once(__DIR__ . '/lib.php');
    if (explode("/", $bookid)[0] === 'book') {
        $bookid = explode("/", $bookid)[1];
        $getsesionurl = $server . 'db?SSr=' . $ssr . '&guide=book&cmd=data&id=' . $bookid . '&img_src_form=b64&on_cdata=1';
    } else if (explode("/", $bookid)[0] === 'doc') {
        $bookid = getbookidbydocid($server, $ssr, explode("/", $bookid)[1]);
        $getsesionurl = $server . 'db?SSr=' . $ssr . '&guide=book&cmd=data&id=' . $bookid . '&img_src_form=b64&on_cdata=1';
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $getsesionurl);
    // We get a session. Получаем сессию.
    $ssrxml = curl_exec($ch);
    curl_close($ch);
    $xml = simplexml_load_string($ssrxml, null, LIBXML_NOCDATA);
    $json = json_encode($xml);
    $array = json_decode($json, true);
    $metavar = $array['meta']['var'];
    $chapters = $array['chapters']['chapter'];
    $authors = '';
    $annotation = '';
    $publisher = '';
    $year = '';
    for ($i = 0; $i < count($metavar); $i++) {
        if ($metavar[$i]['@attributes']['name'] === 'authors') {
            if ($xml->xpath('/book/meta/var[@name="authors"]/string[@language="' . $SESSION->lang . '"]')) {
                $authors = $xml->xpath('/book/meta/var[@name="authors"]/string[@language="' . $SESSION->lang . '"]')[0];
            } else if ($xml->xpath('/book/meta/var[@name="authors"]/string[@language="ru"]')) {
                $authors = $xml->xpath('/book/meta/var[@name="authors"]/string[@language="ru"]')[0];
            } else if ($xml->xpath('/book/meta/var[@name="authors"]/string[@language="en"]')) {
                $authors = $xml->xpath('/book/meta/var[@name="authors"]/string[@language="en"]')[0];
            }
        }
        if ($metavar[$i]['@attributes']['name'] === 'annotation') {
            if ($xml->xpath('/book/meta/var[@name="annotation"]/string[@language="' . $SESSION->lang . '"]')) {
                $annotation = $xml->xpath('/book/meta/var[@name="annotation"]/string[@language="' . $SESSION->lang . '"]')[0];
            } else if ($xml->xpath('/book/meta/var[@name="annotation"]/string[@language="ru"]')) {
                $annotation = $xml->xpath('/book/meta/var[@name="annotation"]/string[@language="ru"]')[0];
            } else if ($xml->xpath('/book/meta/var[@name="annotation"]/string[@language="en"]')) {
                $annotation = $xml->xpath('/book/meta/var[@name="annotation"]/string[@language="en"]')[0];
            }
        }
        if ($metavar[$i]['@attributes']['name'] === 'year') {
            if ($xml->xpath('/book/meta/var[@name="year"]/string[@language="' . $SESSION->lang . '"]')) {
                $year = $xml->xpath('/book/meta/var[@name="year"]/string[@language="' . $SESSION->lang . '"]')[0];
            } else if ($xml->xpath('/book/meta/var[@name="year"]/string[@language="ru"]')) {
                $year = $xml->xpath('/book/meta/var[@name="year"]/string[@language="ru"]')[0];
            } else if ($xml->xpath('/book/meta/var[@name="year"]/string[@language="en"]')) {
                $year = $xml->xpath('/book/meta/var[@name="year"]/string[@language="en"]')[0];
            }
        }
        if ($metavar[$i]['@attributes']['name'] === 'publisher') {
            if ($xml->xpath('/book/meta/var[@name="publisher"]/string[@language="' . $SESSION->lang . '"]')) {
                $publisher = $xml->xpath('/book/meta/var[@name="publisher"]/string[@language="' . $SESSION->lang . '"]')[0];
            } else if ($xml->xpath('/book/meta/var[@name="publisher"]/string[@language="ru"]')) {
                $publisher = $xml->xpath('/book/meta/var[@name="publisher"]/string[@language="ru"]')[0];
            } else if ($xml->xpath('/book/meta/var[@name="publisher"]/string[@language="en"]')) {
                $publisher = $xml->xpath('/book/meta/var[@name="publisher"]/string[@language="en"]')[0];
            }
        }
    }
    $chaptername = '';
    if (isset($chapters)) {
        for ($i = 0; $i < count($chapters); $i++) {
            if ($chapters[$i]['@attributes']['id'] === explode("/", $bookid)[1]) {
                $chaptername .= ' ' . $chapters[$i]['string'];
            }
        }
    }
    if ($chaptername !== '') {
        $chaptername .= ' ' . get_string('studentlibrary:page', 'mod_studentlibrary') . ' ' . explode("/", $bookid)[2];
    }
    $publisher = getpublisher($ssr, $publisher, $server);
    if (isset($array['meta']['attachments']['cash']['attach'][0])) {
        $imgsrc = $array['meta']['attachments']['cash']['attach'][0]['@attributes']['src'];
    } else {
        $imgsrc = $array['meta']['attachments']['cash']['attach']['@attributes']['src'];
    }
    $booklistitem = '';
    $booklistitem .= '<label class="radio-card">';
    $booklistitem .= '<div class="card-content-detail-wrapper">';
    if ($xml->xpath('/book/title/string[@language="' . $SESSION->lang . '"]')) {
        $booklistitem .= '<div class="titleH1">';
        $booklistitem .= $xml->xpath('/book/title/string[@language="' . $SESSION->lang . '"]')[0] . '</div>';
    } else if ($xml->xpath('/book/title/string[@language="ru"]')) {
        $booklistitem .= '<div class="titleH1">' . $xml->xpath('/book/title/string[@language="ru"]')[0] . '</div>';
    } else if ($xml->xpath('/book/title/string[@language="en"]')) {
        $booklistitem .= '<div class="titleH1">' . $xml->xpath('/book/title/string[@language="en"]')[0] . '</div>';
    }
    $booklistitem .= '<div class="titleH2">' . $chaptername . '</div>';
    $booklistitem .= '<div class="card-props">';
    $booklistitem .= '<div class="cover">';
    $booklistitem .= '<img src=' . $imgsrc . '></img>';
    $booklistitem .= '</div>';
    $booklistitem .= '<div class="props-list">';
    $booklistitem .= '<dl class="main-props">';
    $booklistitem .= '<dt class="ng-star-inserted">' . get_string('studentlibrary:authors', 'mod_studentlibrary');
    $booklistitem .= ':</dt><dd class="ng-star-inserted authors">' . $authors . '</dd>';
    $booklistitem .= '<dt class="ng-star-inserted">' . get_string('studentlibrary:publisher', 'mod_studentlibrary');
    $booklistitem .= ':</dt><dd class="ng-star-inserted publisher">' . $publisher . '</dd>';
    $booklistitem .= '<dt class="ng-star-inserted">' . get_string('studentlibrary:year', 'mod_studentlibrary');
    $booklistitem .= ':</dt><dd class="ng-star-inserted year">' . $year . '</dd>';
    $booklistitem .= '<dt class="ng-star-inserted"><div class="read_button"><a href="' . $url . '" target="_blank" class="btn btn-primary" >';
    $booklistitem .= get_string('studentlibrary:read', 'mod_studentlibrary');
    $booklistitem .= '</a></div></dt><dd class="ng-star-inserted"></dd>';
    $booklistitem .= '</dl>';
    $booklistitem .= '<div class="doc_name"></div>';
    $booklistitem .= '</div>';
    $booklistitem .= '<div class="annotation"><p class="annotation_title">';
    $booklistitem .= get_string('studentlibrary:annotation', 'mod_studentlibrary') . ':</p>';
    if ($annotation) {
        if (isset($annotation['p'])) {
            for ($p = 0; $p < count($annotation['p']); $p++) {
                $booklistitem .= '<p>' . $annotation['p'][$p] . '</p>';
            }
        } else if (isset($annotation['div'])) {
            $booklistitem .= '<div>' . $annotation['div'] . '</div>';
        } else {
            $booklistitem .= '<div>' . $annotation . '</div>';
        }
    }
    $booklistitem .= '</div>';
    $booklistitem .= '</div>';
    $booklistitem .= '</div>';
    $booklistitem .= '</label>';
    return $booklistitem;
}

/**
 * Getting a list of publishers
 * @param string $ssr user ssr.
 * @param string $getpublisherurl url API.
 * @param string $server server url.
 * @return string Retutn publishers name.
 */
function getpublisher($ssr, $getpublisherurl, $server) {
    $getpublisherurl = $server . 'db?SSr=' . $ssr . '&guide=publishers&cmd=data&id=' . $getpublisherurl . '&build_in_data=1&on_cdata=0';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $getpublisherurl);
    // We get a session. Получаем сессию.
    $publisherxml = curl_exec($ch);
    curl_close($ch);
    $xml = simplexml_load_string($publisherxml);
    $json = json_encode($xml);
    $array = json_decode($json, true);
    $publishersfield = $array['field'];
    if ($publishersfield) {
        for ($i = 0; $i < count($publishersfield); $i++) {
            if ($publishersfield[$i]['@attributes']['id'] === 'name') {
                $publishers = $publishersfield[$i]['string'];
            }
        }
    } else {
        $publishers = '';
    }
    return ($publishers);
}
/**
 * Getting book Id by docId.
 * @param string $ssr user ssr.
 * @param string $bookid book chapter id.
 * @param string $server server url.
 * @return string Retutn publishers name.
 */
function getbookidbydocid($server, $ssr, $bookid) {
    $masterbookdataurl = $server . 'db?SSr=' . $ssr . '&guide=doc&cmd=data&id=' . $bookid . '&tag=master_book_data';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $masterbookdataurl);
    // We get the book data by chapter. Получаем данные книги по главе.
    $rezxmlstring = curl_exec($ch);
    curl_close($ch);
    $xml = simplexml_load_string($rezxmlstring);
    $result = $xml->xpath('/doc/book');
    if (isset($result[0])) {
        return $result[0]->attributes()->id;
    } else {
        return null;
    }
}

/**
 * Getting an organization session id.
 * @param string $agrid arg id.
 * @param string $orgid org id.
 * @param string $serverapi server url.
 * @return string Retutn organization session id.
 */
function getssro($serverapi, $orgid, $agrid) {
    // We get the organization's session. Получаем сессию организации.
    $getsesionurl = $serverapi . "join?org_id=" . $orgid . "&agr_id=" . $agrid . "&app=plugin_moodle";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $getsesionurl);
    // We get a session. Получаем сессию.
    $ssrxml = curl_exec($ch);
    curl_close($ch);
    $xml = simplexml_load_string($ssrxml);
    $json = json_encode($xml);
    $array = json_decode($json, true);
    $ssro = $array["code"];
    return $ssro;
}

/**
 * Getting an personal session id.
 * @param string $serverapi serverapi.
 * @param string $ssro ssro.
 * @param string $userid userid.
 * @param string $userlastname userlastname.
 * @param string $userfirstname userfirstname.
 * @return string Retutn personal session id.
 */
function getssrp($serverapi, $ssro, $userid, $userlastname, $userfirstname) {
    // Getting the user's session. Получаем сессию пользователя.
    $getsesionurl = $serverapi . "db?SSr=" . $ssro . "&guide=session&cmd=solve&action=seamless_access&id=";
    $getsesionurl .= $userid . '&value.FamilyName.ru=' . $userlastname . '&value.NameAndFName.ru=' . $userfirstname;
    $ch2 = curl_init();
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch2, CURLOPT_URL, $getsesionurl);
    // We get a session. Получаем сессию.
    $ssrxml = curl_exec($ch2);
    curl_close($ch2);
    $xml = simplexml_load_string($ssrxml);
    $json = json_encode($xml);
    $array = json_decode($json, true);
    $ssrp = $array["code"];
    return $ssrp;
}

/**
 * Getting kits list.
 * @param string $serverapi serverapi.
 * @param string $ssrp ssrp.
 * @return array Retutn kits list.
 */
function getkitslist($serverapi, $ssrp) {
    // We get a set of books. Получаем список книг.
    $kitsurl = $serverapi . "db?SSr=" . $ssrp . "&guide=sengine&cmd=sel&tag=all_agreement_kits";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $kitsurl);
    $kitslistxml = curl_exec($ch);
    curl_close($ch);
    $xml = simplexml_load_string($kitslistxml);
    $json = json_encode($xml);
    $array = json_decode($json, true);
    $allagreementkits = $array["all_agreement_kits"];
    $kitslist = [];
    foreach ($allagreementkits as $kits) {
        $kitid = $kits["@attributes"]["id"];
        array_push($kitslist, $kitid);
    }
    return $kitslist;
}

/**
 * Building a switch kit.
 * @param string $server server.
 * @param string $ssr ssr.
 * @param string $kit_id kit id.
 * @param string $url url.
 * @param string $lang lang.
 * @return string Retutn switch kit.
 */
function buildswitchkit($server, $ssr, $kit_id, $url, $lang) {
    // We get a set of books. Получаем список книг.
    $kitdataurl = $server . "db?SSr=" . $ssr . "&guide=sengine&cmd=sel&tag=kit_content&kit=" . $kit_id;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $kitdataurl);
    $kitdataxml = curl_exec($ch);
    curl_close($ch);
    $xml = simplexml_load_string($kitdataxml, null, LIBXML_NOCDATA);
    $kitname = '';
    if ($xml->xpath('/document/name/string[@language="' . $lang . '"]')) {
        $kitname = $xml->xpath('/document/name/string[@language="' . $lang . '"]');
    } else if ($xml->xpath('/document/name/string[@language="ru"]')) {
        $kitname = $xml->xpath('/name/string[@language="ru"]');
    } else if ($xml->xpath('/document/name/string[@language="en"]')) {
        $kitname = $xml->xpath('/document/name/string[@language="en"]');
    }
    $ret = '<div class="titleH2"><p>'. get_string('studentlibrary:link_to_the_kit', 'mod_studentlibrary');
    $ret .= '<a target="_blank" href="' . $url . '">' . $kitname[0] . '</a></p></div>';
    return $ret;
}
