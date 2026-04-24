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
 * Course Profesores filter - main filter class.
 *
 * This filter replaces the {courseprofesores} tag with a list of
 * course profesores grouped by role, showing avatar, profile link,
 * and message link for each profesor.
 *
 * @package    filter_courseprofesores
 * @copyright  2026 Daniel Ferrada
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_courseprofesores extends moodle_text_filter {
    /** @var array Request-level cache of profesores per course. */
    protected static $profesorescache = [];

    /** @var array Request-level cache of plugin settings. */
    protected static $settingscache = null;

    /** @var array Request-level cache of role lists. */
    protected static $rolecache = null;

    /**
     * Load plugin settings into static cache.
     */
    protected function load_settings() {
        if (self::$settingscache !== null) {
            return;
        }

        $rolesincludedconfig = get_config('filter_courseprofesores', 'rolesincluded');
        $rolesarray = [];
        if (!empty($rolesincludedconfig)) {
            $roles = explode(',', $rolesincludedconfig);
            foreach ($roles as $r) {
                if (!empty($r)) {
                    $rolesarray[$r] = 1;
                }
            }
        } else {
            // Default roles if nothing is configured.
            $rolesarray = ['editingteacher' => 1, 'teacher' => 1];
        }

        self::$settingscache = [
            'showavatars' => get_config('filter_courseprofesores', 'showavatars') !== '0',
            'showdepartment' => get_config('filter_courseprofesores', 'showdepartment') !== '0',
            'showinstitution' => get_config('filter_courseprofesores', 'showinstitution') !== '0',
            'showmessagelink' => get_config('filter_courseprofesores', 'showmessagelink') !== '0',
            'showparticipantslink' => get_config('filter_courseprofesores', 'showparticipantslink') !== '0',
            'displaystyle' => preg_replace('/[^a-z0-9_-]/i', '', get_config('filter_courseprofesores', 'displaystyle') ?: 'cards'),
            'rolesincluded' => $rolesarray,
        ];
    }

    /**
     * Apply the filter to the given text.
     *
     * @param string $text The text to filter.
     * @param array $options Filter options.
     * @return string The filtered text.
     */
    public function filter($text, array $options = []) {
        global $COURSE, $PAGE, $SITE;

        if (empty($text) || is_object($text)) {
            return $text;
        }

        // Decode HTML entities before checking, in case the editor converted `{` and `}` to `&#123;` and `&#125;`.
        if (strpos($text, '{courseprofesores}') === false && strpos($text, '&#123;courseprofesores&#125;') === false) {
            return $text;
        }

        // Ensure consistent tag format for later replacement.
        $text = str_replace('&#123;courseprofesores&#125;', '{courseprofesores}', $text);

        $course = $COURSE ?? $SITE;

        // Skip site-level context.
        if ($course->id == SITEID) {
            $text = str_replace('{courseprofesores}', '', $text);
            return $text;
        }

        $coursecontext = context_course::instance($course->id);

        $profesores = $this->get_course_profesores($course->id, $coursecontext);

        if (empty($profesores)) {
            $text = str_replace('{courseprofesores}', '', $text);
            return $text;
        }

        $output = $this->render_profesores($profesores, $course);

        $text = str_replace('{courseprofesores}', $output, $text);

        return $text;
    }

    /**
     * Get all profesores for a course grouped by role.
     *
     * @param int $courseid The course ID.
     * @param context $coursecontext The course context.
     * @return array Array of profesores grouped by role.
     */
    protected function get_course_profesores($courseid, $coursecontext) {
        global $DB;

        $cachekey = $courseid . '-' . $coursecontext->id;
        if (isset(self::$profesorescache[$cachekey])) {
            return self::$profesorescache[$cachekey];
        }

        $this->load_settings();

        // Load roles once per request.
        if (self::$rolecache === null) {
            self::$rolecache = get_all_roles();
        }

        $relevantroles = [];
        foreach (self::$rolecache as $role) {
            if (!empty(self::$settingscache['rolesincluded'][$role->shortname])) {
                $relevantroles[] = $role->id;
            }
        }

        if (empty($relevantroles)) {
            self::$profesorescache[$cachekey] = [];
            return [];
        }

        // Try to get teachers from the course context first.
        $records = $this->get_profesores_from_context($coursecontext, $relevantroles);

        // If no teachers found in course context, check parent contexts (e.g. category or system) in bulk.
        if (empty($records)) {
            $parentcontextids = $coursecontext->get_parent_context_ids();
            // Variable $parentcontextids is [systemid, ..., categoryid, coursecontextid].
            // We want to check from course upwards, excluding course itself.
            $parentcontextids = array_reverse($parentcontextids);
            array_shift($parentcontextids); // Remove the course context id.

            if (!empty($parentcontextids)) {
                $records = $this->get_best_profesores_from_parents($parentcontextids, $relevantroles);
            }
        }

        $grouped = [];
        $roleorder = ['editingteacher' => 1, 'teacher' => 2, 'manager' => 3];

        foreach ($records as $record) {
            $roleshortname = $record->roleshortname;

            if (!isset($grouped[$roleshortname])) {
                $role = self::$rolecache[$record->roleid];
                $grouped[$roleshortname] = [
                    'shortname' => $roleshortname,
                    'name' => role_get_name($role, $coursecontext),
                    'sortorder' => $roleorder[$roleshortname] ?? (99 + $record->roleid),
                    'users' => [],
                ];
            }

            // Ensure we don't add the same user twice if they have multiple roles (unlikely in this query but safe).
            if (!isset($grouped[$roleshortname]['users'][$record->id])) {
                $grouped[$roleshortname]['users'][$record->id] = [
                    'id' => $record->id,
                    'firstname' => $record->firstname,
                    'lastname' => $record->lastname,
                    'firstnamephonetic' => $record->firstnamephonetic,
                    'lastnamephonetic' => $record->lastnamephonetic,
                    'middlename' => $record->middlename,
                    'alternatename' => $record->alternatename,
                    'email' => $record->email,
                    'picture' => $record->picture,
                    'imagealt' => $record->imagealt,
                    'username' => $record->username,
                    'department' => $record->department,
                    'institution' => $record->institution,
                    'fullname' => fullname($record),
                ];
            }
        }

        usort($grouped, function ($a, $b) {
            return $a['sortorder'] - $b['sortorder'];
        });

        self::$profesorescache[$cachekey] = $grouped;
        return $grouped;
    }

    /**
     * Get profesores from a specific context.
     *
     * @param context $context The context.
     * @param array $relevantroles Array of relevant role IDs.
     * @return array Array of profesor records.
     */
    protected function get_profesores_from_context($context, $relevantroles) {
        global $DB;

        [$rolesql, $roleparams] = $DB->get_in_or_equal($relevantroles, SQL_PARAMS_NAMED);

        $sql = "SELECT u.id, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic,
                       u.middlename, u.alternatename, u.email, u.picture, u.imagealt, u.username,
                       u.department, u.institution,
                       r.id AS roleid, r.name AS rolename, r.shortname AS roleshortname
                  FROM {role_assignments} ra
                  JOIN {user} u ON u.id = ra.userid
                  JOIN {role} r ON r.id = ra.roleid
                 WHERE ra.contextid = :contextid
                   AND ra.roleid $rolesql
                   AND u.deleted = :deleted
              ORDER BY r.sortorder ASC, u.lastname ASC, u.firstname ASC";

        $params = array_merge(['contextid' => $context->id, 'deleted' => 0], $roleparams);

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Get the best set of profesores from a list of parent contexts in bulk.
     *
     * @param array $parentcontextids Array of context IDs to check.
     * @param array $relevantroles Array of relevant role IDs.
     * @return array Array of records from the closest context that has teachers.
     */
    protected function get_best_profesores_from_parents($parentcontextids, $relevantroles) {
        global $DB;

        [$ctxsql, $ctxparams] = $DB->get_in_or_equal($parentcontextids, SQL_PARAMS_NAMED);
        [$rolesql, $roleparams] = $DB->get_in_or_equal($relevantroles, SQL_PARAMS_NAMED);

        $sql = "SELECT ra.contextid, u.id, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic,
                       u.middlename, u.alternatename, u.email, u.picture, u.imagealt, u.username,
                       u.department, u.institution,
                       r.id AS roleid, r.name AS rolename, r.shortname AS roleshortname
                  FROM {role_assignments} ra
                  JOIN {user} u ON u.id = ra.userid
                  JOIN {role} r ON r.id = ra.roleid
                 WHERE ra.contextid $ctxsql
                   AND ra.roleid $rolesql
                   AND u.deleted = :deleted
              ORDER BY r.sortorder ASC, u.lastname ASC, u.firstname ASC";

        $params = array_merge($ctxparams, $roleparams, ['deleted' => 0]);
        $allrecords = $DB->get_records_sql($sql, $params);

        if (empty($allrecords)) {
            return [];
        }

        // Group by context for easy retrieval.
        $groupedbycontext = [];
        foreach ($allrecords as $record) {
            $groupedbycontext[$record->contextid][$record->id] = $record;
        }

        // Iterate through parents in order (closest first) and return the first one that matches.
        foreach ($parentcontextids as $pid) {
            if (isset($groupedbycontext[$pid])) {
                return $groupedbycontext[$pid];
            }
        }

        return [];
    }

    /**
     * Render the profesores list as HTML.
     *
     * @param array $profesores Profesores grouped by role.
     * @param stdClass $course The course object.
     * @return string HTML output.
     */
    protected function render_profesores($profesores, $course) {
        global $USER, $PAGE, $DB;

        $this->load_settings();

        $displaystyle = self::$settingscache['displaystyle'];
        $containerclass = 'filter-courseprofesores-container';
        if ($displaystyle !== 'cards') {
            $containerclass .= ' display-style-' . $displaystyle;
        }

        $html = '<div class="' . $containerclass . '">';

        // Bulk query: collect all profesor IDs and get unread message counts in one query.
        // This avoids N+1 queries inside the loop (Moodle best practice).
        static $messagingenabled = null;
        if ($messagingenabled === null) {
            global $CFG;
            $messagingenabled = !empty($CFG->messaging);
        }

        $unreadcounts = [];
        if ($messagingenabled && self::$settingscache['showmessagelink']) {
            $allprofesorids = [];
            foreach ($profesores as $rolegroup) {
                foreach ($rolegroup['users'] as $profesor) {
                    if ($profesor['id'] != $USER->id) {
                        $allprofesorids[] = $profesor['id'];
                    }
                }
            }

            if (!empty($allprofesorids)) {
                [$insql, $inparams] = $DB->get_in_or_equal($allprofesorids, SQL_PARAMS_NAMED, 'prof');
                $sql = "SELECT mcm2.userid AS profesorid, COUNT(m.id) AS unreadcount
                          FROM {messages} m
                          JOIN {message_conversation_members} mcm1
                            ON mcm1.conversationid = m.conversationid AND mcm1.userid = :studentid
                          JOIN {message_conversation_members} mcm2
                            ON mcm2.conversationid = m.conversationid AND mcm2.userid $insql
                         WHERE m.useridfrom = :senderid
                           AND NOT EXISTS (
                               SELECT 1 FROM {message_user_actions} mua
                                WHERE mua.messageid = m.id
                                  AND mua.userid = mcm2.userid
                                  AND mua.action = :readaction
                           )
                      GROUP BY mcm2.userid";
                $params = array_merge($inparams, [
                    'studentid' => $USER->id,
                    'senderid' => $USER->id,
                    'readaction' => \core_message\api::MESSAGE_ACTION_READ,
                ]);
                $records = $DB->get_records_sql($sql, $params);
                foreach ($records as $record) {
                    $unreadcounts[$record->profesorid] = (int) $record->unreadcount;
                }
            }
        }

        foreach ($profesores as $rolegroup) {
            if (empty($rolegroup['users'])) {
                continue;
            }

            // XSS Protection: Escape role name before passing to get_string.
            $escapedrolename = s($rolegroup['name']);
            $roletitle = get_string('role_' . $rolegroup['shortname'], 'filter_courseprofesores', $escapedrolename);

            $html .= '<div class="profesores-role-group">';
            $html .= '<h4 class="profesores-role-title">' . $roletitle . '</h4>';
            $html .= '<div class="profesores-list">';

            foreach ($rolegroup['users'] as $profesor) {
                $user = (object) $profesor;

                $profileurl = new moodle_url('/user/view.php', ['id' => $user->id, 'course' => $course->id]);

                $unreadcount = $unreadcounts[$user->id] ?? 0;

                $html .= '<div class="profesor-card">';

                if ($unreadcount > 0) {
                    $html .= '<div class="profesor-unread-indicator" title="' .
                        get_string('unreadmessages', 'filter_courseprofesores') . '">';
                    $html .= '<i class="icon fa fa-comment-o fa-fw" aria-hidden="true"></i>';
                    $html .= '<span class="unread-count">' . $unreadcount . '</span>';
                    $html .= '</div>';
                }

                if (self::$settingscache['showavatars']) {
                    $userpicture = new user_picture($user);
                    $userpicture->size = 1;

                    // Moodle 4.x get_url expects a moodle_page object.
                    $pictureurl = $userpicture->get_url($PAGE)->out(false);

                    $html .= '<div class="profesor-avatar">';
                    $html .= '<a href="' . $profileurl->out(false) . '" title="' .
                        get_string('viewprofile', 'filter_courseprofesores') . '">';
                    $html .= '<img src="' . s($pictureurl) . '" alt="' . s($user->fullname) . '" class="userpicture" />';
                    $html .= '</a>';
                    $html .= '</div>';
                }

                $html .= '<div class="profesor-info">';
                $html .= '<a href="' . $profileurl->out(false) . '" class="profesor-name">' . s($user->fullname) . '</a>';

                $showdetails = (self::$settingscache['showdepartment'] && !empty($user->department)) ||
                    (self::$settingscache['showinstitution'] && !empty($user->institution));

                if ($showdetails) {
                    $details = [];
                    if (self::$settingscache['showdepartment'] && !empty($user->department)) {
                        $details[] = s($user->department);
                    }
                    if (self::$settingscache['showinstitution'] && !empty($user->institution)) {
                        $details[] = s($user->institution);
                    }
                    $html .= '<div class="profesor-details">' . implode(', ', $details) . '</div>';
                }

                if (self::$settingscache['showmessagelink']) {
                    if ($messagingenabled && $USER->id != $user->id) {
                        if (\core_message\api::can_send_message($USER->id, $user->id)) {
                            $messagelink = new moodle_url('/message/index.php', ['id' => $user->id]);
                            $html .= '<div class="profesor-actions">';
                            $html .= '<a href="' . $messagelink->out(false) .
                                '" class="profesor-action-link message-link" title="' .
                                get_string('sendmessage', 'filter_courseprofesores') . '">';
                            $html .= '<i class="icon fa fa-envelope fa-fw" aria-hidden="true"></i> ';
                            $html .= get_string('sendmessage', 'filter_courseprofesores');
                            $html .= '</a>';
                            $html .= '</div>';
                        }
                    }
                }

                $html .= '</div>'; // End info.
                $html .= '</div>'; // End card.
            }

            $html .= '</div>'; // End list.
            $html .= '</div>'; // End group.
        }

        // Add footer link if enabled.
        if (self::$settingscache['showparticipantslink']) {
            $coursecontext = context_course::instance($course->id);
            if (has_capability('moodle/course:viewparticipants', $coursecontext)) {
                $participantsurl = new moodle_url('/user/index.php', ['id' => $course->id]);
                $html .= '<div class="profesores-footer">';
                $html .= '<a href="' . $participantsurl->out(false) . '" class="participants-link">';
                $html .= '<i class="icon fa fa-users fa-fw" aria-hidden="true"></i> ';
                $html .= get_string('viewparticipants', 'filter_courseprofesores');
                $html .= '</a>';
                $html .= '</div>';
            }
        }

        $html .= '</div>'; // End container.

        return $html;
    }
}
