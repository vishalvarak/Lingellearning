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
 * This file contains the Activity modules block.
 *
 * @package    block_activities
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 defined('MOODLE_INTERNAL') || die();
 require_once($CFG->libdir . '/filelib.php');


class block_activities extends block_list {
    public function init() {
        $this->title = get_string('activities', 'block_activities');
    }
    // The PHP tag and the curly bracket for the class definition
    // will only be closed after there is another function added in the next section.


    public function applicable_formats() {
        return array(
            'course-view' => true,
            'site' => false,
            'mod' => false,
            'my' => false,
        );
    }

    public function specialization() {
        if (isset($this->config)) {
            if (empty($this->config->title)) {
                $this->title = get_string('activities', 'block_activities');
            } else {
                $this->title = $this->config->title;
            }

            if (empty($this->config->text)) {
                $this->config->text = get_string('blockstring', 'block_activities');
            }
        }
    }

    public function get_content() {
        global $CFG, $DB, $OUTPUT, $USER;
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $course = $this->page->course;

         require_once($CFG->dirroot.'/course/lib.php');

        $modinfo = get_fast_modinfo($course);
        $modfullnames = array();

        $archetypes = array();

        foreach ($modinfo->cms as $cm) {
            // Exclude activities that aren't visible or have no view link (e.g. label). Account for folder being displayed inline.
            if (!$cm->uservisible || (!$cm->has_view() && strcmp($cm->modname, 'folder') !== 0)) {
                continue;
            }
            if (array_key_exists($cm->name, $modfullnames)) {
                continue;
            }
            if (!array_key_exists($cm->modname, $archetypes)) {
                $archetypes[$cm->modname] = plugin_supports('mod', $cm->modname, FEATURE_MOD_ARCHETYPE, MOD_ARCHETYPE_OTHER);
            }
            if ($archetypes[$cm->modname] == MOD_ARCHETYPE_RESOURCE) {
                if (!array_key_exists('resources', $modfullnames)) {
                    $modfullnames['resources'] = get_string('resources');
                }
            } else {
                $sql = "SELECT u.username AS 'User', c.shortname AS 'Course', m.name AS Activitytype, CASE WHEN m.name = 'assign'";
                $sql .= "THEN (SELECT name FROM mdl_assign WHERE id = cm.instance) WHEN m.name = 'assignment' THEN";
                $sql .= "(SELECT name FROM mdl_assignment  WHERE id = cm.instance)";
                $sql .= "WHEN m.name = 'book'        THEN (SELECT name FROM mdl_book        WHERE id = cm.instance)";
                $sql .= "WHEN m.name = 'chat'        THEN (SELECT name FROM mdl_chat        WHERE id = cm.instance)";
                $sql .= "WHEN m.name = 'choice'      THEN (SELECT name FROM mdl_choice      WHERE id = cm.instance)";
                $sql .= "WHEN m.name = 'data'        THEN (SELECT name FROM mdl_data        WHERE id = cm.instance)";
                $sql .= "WHEN m.name = 'feedback'    THEN (SELECT name FROM mdl_feedback    WHERE id = cm.instance)";
                $sql .= "WHEN m.name = 'folder'      THEN (SELECT name FROM mdl_folder      WHERE id = cm.instance)";
                $sql .= "WHEN m.name = 'forum'       THEN (SELECT name FROM mdl_forum       WHERE id = cm.instance)";
                $sql .= "WHEN m.name = 'glossary'    THEN (SELECT name FROM mdl_glossary    WHERE id = cm.instance)";
                $sql .= "WHEN m.name = 'h5pactivity' THEN (SELECT name FROM mdl_h5pactivity WHERE id = cm.instance)";
                $sql .= "WHEN m.name = 'imscp'       THEN (SELECT name FROM mdl_imscp       WHERE id = cm.instance)";
                $sql .= "WHEN m.name = 'label'       THEN (SELECT name FROM mdl_label       WHERE id = cm.instance)";
                $sql .= "WHEN m.name = 'lesson'      THEN (SELECT name FROM mdl_lesson      WHERE id = cm.instance)";
                $sql .= "WHEN m.name = 'lti'         THEN (SELECT name FROM mdl_lti         WHERE id = cm.instance)";
                $sql .= "WHEN m.name = 'page'        THEN (SELECT name FROM mdl_page        WHERE id = cm.instance)";
                $sql .= "WHEN m.name = 'quiz'        THEN (SELECT name FROM mdl_quiz        WHERE id = cm.instance)";
                $sql .= "WHEN m.name = 'resource'    THEN (SELECT name FROM mdl_resource    WHERE id = cm.instance)";
                $sql .= "WHEN m.name = 'scorm'       THEN (SELECT name FROM mdl_scorm       WHERE id = cm.instance)";
                $sql .= "WHEN m.name = 'survey'      THEN (SELECT name FROM mdl_survey      WHERE id = cm.instance)";
                $sql .= "WHEN m.name = 'url'         THEN (SELECT name FROM mdl_url         WHERE id = cm.instance)";
                $sql .= "WHEN m.name = 'wiki'        THEN (SELECT name FROM mdl_wiki        WHERE id = cm.instance)";
                $sql .= "WHEN m.name = 'workshop'    THEN (SELECT name FROM mdl_workshop    WHERE id = cm.instance)";
                $sql .= "ELSE 'Other activity' END AS Activityname, # cm.section AS Coursesection,
                CASE WHEN cm.completion = 0 THEN '0 None' WHEN cm.completion = 1 THEN '1 Self'";
                $sql .= "WHEN cm.completion = 2 THEN '2 Auto' END AS Activtycompletiontype, CASE WHEN cmc.completionstate";
                $sql .= "= 0 THEN 'In Progress' WHEN cmc.completionstate = 1 THEN '1'";
                $sql .= "WHEN cmc.completionstate = 2 THEN 'Completed with Pass'";
                $sql .= "WHEN cmc.completionstate = 3 THEN 'Completed with Fail' ELSE 'Unknown' END AS 'Progress',";
                $sql .= "DATE_FORMAT(FROM_UNIXTIME(cmc.timemodified), '%Y-%m-%d %H:%i') AS 'When'";
                $sql .= "FROM mdl_course_modules_completion cmc JOIN mdl_user u ON cmc.userid = u.id JOIN mdl_course_modules cm  ON cmc.coursemoduleid = cm.id
                JOIN mdl_course c ON cm.course = c.id JOIN mdl_modules m ON cm.module = m.id
                WHERE u.id = $USER->id and cm.id = $cm->id
                ORDER BY u.username";

                $result = $DB->get_records_sql($sql);
                $completionstatus = 0;
                if (count($result) != 0) {
                    foreach ($result as $res) {
                        $completionstatus = $res->progress;
                    }
                }

                $filteredmodules = new stdClass;
                $filteredmodules->completion = $completionstatus;
                $filteredmodules->id = $cm->id;
                $filteredmodules->name = $cm->name;
                $filteredmodules->modname = $cm->modname;
                $filteredmodules->date = date('d-M-Y', $cm->added);
                $modfullnames[$cm->name] = $filteredmodules;
            }
        }

        $completed = ' - Completed';
        $empty = '';

        foreach ($modfullnames as $name => $modfullname) {
            $compstatus = ($modfullname->completion === '1') ? $completed : $empty;
            if ($modname === 'resources') {
                $this->content->items[] = $modfullname->id.' - '.'<a style="color:black;" href="'.$CFG->wwwroot.
                '/course/resources.php?id='.$course->id.'">'.$modfullname->name.'</a>'.' - '.$modfullname->date.$compstatus;
            } else {
                $this->content->items[] = $modfullname->id.' - '.'<a style="color:black;" <a href="'.$CFG->wwwroot.
                '/mod/'.$modfullname->modname.'/view.php?id='.$modfullname->id.'">'.$modfullname->name.
                '</a>'.' - '.$modfullname->date.$compstatus;
            }
        }

        if (! empty($this->config->text)) {
            $this->content->text = $this->config->text;
        }

        return $this->content;
    }

}
