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
 * Course Profesores filter - main filter class (Moodle 5.0+ namespaced version).
 *
 * This filter replaces the {courseprofesores} tag with a list of
 * course profesores grouped by role, showing avatar, profile link,
 * and message link for each profesor.
 *
 * @package    filter_courseprofesores
 * @copyright  2026 Daniel Ferrada
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_courseprofesores;

/**
 * Course Profesores text filter class.
 *
 * Implements the new namespaced filter API required by MDL-82427 (Moodle 4.5+/5.0+).
 * The class must extend \core_filters\text_filter.
 *
 * @package    filter_courseprofesores
 * @copyright  2026 Daniel Ferrada
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class text_filter extends \core_filters\text_filter {
    /** @var array Request-level cache of profesores per course. */
    protected static array $profesorescache = [];

    /** @var array|null Request-level cache of plugin settings. */
    protected static ?array $settingscache = null;

    /** @var array|null Request-level cache of role lists. */
    protected static ?array $rolecache = null;

    /**
     * Load plugin settings into static cache.
     */
    protected function load_settings(): void {
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
            'showavatars'          => get_config('filter_courseprofesores', 'showavatars') !== '0',
            'showdepartment'       => get_config('filter_courseprofesores', 'showdepartment') !== '0',
            'showinstitution'      => get_config('filter_courseprofesores', 'showinstitution') !== '0',
            'showmessagelink'      => get_config('filter_courseprofesores', 'showmessagelink') !== '0',
            'showonlinestatus'     => get_config('filter_courseprofesores', 'showonlinestatus') !== '0',
            'showparticipantslink' => get_config('filter_courseprofesores', 'showparticipantslink') !== '0',
            'displaystyle'         => preg_replace(
                '/[^a-z0-9_-]/i',
                '',
                get_config('filter_courseprofesores', 'displaystyle') ?: 'cards'
            ),
            'accentcolor'          => preg_replace(
                '/[^a-z0-9_-]/i',
                '',
                get_config('filter_courseprofesores', 'accentcolor') ?: 'default'
            ),
            'cardcolor'            => preg_replace(
                '/[^a-z0-9_-]/i',
                '',
                get_config('filter_courseprofesores', 'cardcolor') ?: 'default'
            ),
            'customaccentcolor'    => self::sanitize_color(
                get_config('filter_courseprofesores', 'customaccentcolor'),
                '#0f6cbf'
            ),
            'customcardcolor'      => self::sanitize_color(
                get_config('filter_courseprofesores', 'customcardcolor'),
                '#120ef2'
            ),
            'customcardbordercolor' => self::sanitize_color(
                get_config('filter_courseprofesores', 'customcardbordercolor'),
                '#0e0bca'
            ),
            'customcardtextcolor'  => self::sanitize_color(
                get_config('filter_courseprofesores', 'customcardtextcolor'),
                '#ffffff'
            ),
            'customcardtextsecondarycolor' => self::sanitize_color(
                get_config('filter_courseprofesores', 'customcardtextsecondarycolor'),
                '#ffffff'
            ),
            'customcardbuttoncolor' => self::sanitize_color(
                get_config('filter_courseprofesores', 'customcardbuttoncolor'),
                '#ffffff'
            ),
            'customcardbuttonhovercolor' => self::sanitize_color(
                get_config('filter_courseprofesores', 'customcardbuttonhovercolor'),
                '#ffffff'
            ),
            'customcardshadowcolor' => self::sanitize_color(
                get_config('filter_courseprofesores', 'customcardshadowcolor'),
                '#120ef2'
            ),
            'rolesincluded'        => $rolesarray,
        ];
    }

    /**
     * Apply the filter to the given text.
     *
     * @param string $text    The text to filter.
     * @param array  $options Filter options.
     * @return string The filtered text.
     */
    public function filter($text, array $options = []): string {
        global $COURSE, $PAGE, $SITE;

        if (empty($text) || is_object($text)) {
            return $text;
        }

        // Decode HTML entities before checking, in case the editor converted `{` and `}`.
        if (
            strpos($text, '{courseprofesores}') === false &&
            strpos($text, '&#123;courseprofesores&#125;') === false
        ) {
            return $text;
        }

        // Ensure consistent tag format for later replacement.
        $text = str_replace('&#123;courseprofesores&#125;', '{courseprofesores}', $text);

        $course = $COURSE ?? $SITE;

        // Skip site-level context.
        if ($course->id == SITEID) {
            return str_replace('{courseprofesores}', '', $text);
        }

        $coursecontext = \context_course::instance($course->id);

        // Check if user can view profesores - allow anyone with the specific capability
        // or any user enrolled in the course. This ensures students can always see
        // their teachers even if the capability hasn't been propagated to the
        // student role due to a previous upgrade gap.
        if (
            !has_capability('filter/courseprofesores:viewprofesores', $coursecontext) &&
            !is_enrolled($coursecontext)
        ) {
            return str_replace('{courseprofesores}', '', $text);
        }

        $profesores    = $this->get_course_profesores($course->id, $coursecontext);

        if (empty($profesores)) {
            return str_replace('{courseprofesores}', '', $text);
        }

        $output = $this->render_profesores($profesores, $course);
        return str_replace('{courseprofesores}', $output, $text);
    }

    /**
     * Get all profesores for a course grouped by role.
     *
     * @param int      $courseid      The course ID.
     * @param \context $coursecontext The course context.
     * @return array Array of profesores grouped by role.
     */
    protected function get_course_profesores(int $courseid, \context $coursecontext): array {
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

        // If no teachers found in course context, check parent contexts in bulk.
        if (empty($records)) {
            $parentcontextids = $coursecontext->get_parent_context_ids();
            $parentcontextids = array_reverse($parentcontextids);
            array_shift($parentcontextids); // Remove the course context id.

            if (!empty($parentcontextids)) {
                $records = $this->get_best_profesores_from_parents($parentcontextids, $relevantroles);
            }
        }

        $grouped   = [];
        $roleorder = ['editingteacher' => 1, 'teacher' => 2, 'manager' => 3];

        foreach ($records as $record) {
            $roleshortname = $record->roleshortname;

            if (!isset($grouped[$roleshortname])) {
                $role = self::$rolecache[$record->roleid];
                $grouped[$roleshortname] = [
                    'shortname' => $roleshortname,
                    'name'      => role_get_name($role, $coursecontext),
                    'sortorder' => $roleorder[$roleshortname] ?? (99 + $record->roleid),
                    'users'     => [],
                ];
            }

            if (!isset($grouped[$roleshortname]['users'][$record->id])) {
                $grouped[$roleshortname]['users'][$record->id] = [
                    'id'                => $record->id,
                    'firstname'         => $record->firstname,
                    'lastname'          => $record->lastname,
                    'firstnamephonetic' => $record->firstnamephonetic ?? '',
                    'lastnamephonetic'  => $record->lastnamephonetic ?? '',
                    'middlename'        => $record->middlename ?? '',
                    'alternatename'     => $record->alternatename ?? '',
                    'email'             => $record->email,
                    'picture'           => $record->picture,
                    'imagealt'          => $record->imagealt,
                    'username'          => $record->username,
                    'department'        => $record->department,
                    'institution'       => $record->institution,
                    'fullname'          => fullname($record),
                ];
            }
        }

        usort($grouped, fn($a, $b) => $a['sortorder'] - $b['sortorder']);

        self::$profesorescache[$cachekey] = $grouped;
        return $grouped;
    }

    /**
     * Get profesores from a specific context.
     *
     * @param \context $context       The context.
     * @param array    $relevantroles Array of relevant role IDs.
     * @return array Array of profesor records.
     */
    protected function get_profesores_from_context(\context $context, array $relevantroles): array {
        global $DB;

        [$rolesql, $roleparams] = $DB->get_in_or_equal($relevantroles, SQL_PARAMS_NAMED);

        $sql = "SELECT u.id, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic,
                       u.middlename, u.alternatename, u.email, u.picture, u.imagealt,
                       u.username, u.department, u.institution,
                       r.id AS roleid, r.name AS rolename, r.shortname AS roleshortname
                  FROM {role_assignments} ra
                  JOIN {user} u ON u.id = ra.userid
                  JOIN {role} r ON r.id = ra.roleid
                 WHERE ra.contextid = :contextid
                   AND ra.roleid $rolesql
                   AND u.deleted = :deleted
                   AND u.suspended = :suspended
              ORDER BY r.sortorder ASC, u.lastname ASC, u.firstname ASC";

        $params = array_merge([
            'contextid' => $context->id,
            'deleted' => 0,
            'suspended' => 0,
        ], $roleparams);
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Get the best set of profesores from a list of parent contexts in bulk.
     *
     * @param array $parentcontextids Array of context IDs to check.
     * @param array $relevantroles    Array of relevant role IDs.
     * @return array Array of records from the closest context that has teachers.
     */
    protected function get_best_profesores_from_parents(array $parentcontextids, array $relevantroles): array {
        global $DB;

        [$ctxsql, $ctxparams]   = $DB->get_in_or_equal($parentcontextids, SQL_PARAMS_NAMED);
        [$rolesql, $roleparams] = $DB->get_in_or_equal($relevantroles, SQL_PARAMS_NAMED);

        $sql = "SELECT ra.contextid, u.id, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic,
                       u.middlename, u.alternatename, u.email, u.picture, u.imagealt,
                       u.username, u.department, u.institution,
                       r.id AS roleid, r.name AS rolename, r.shortname AS roleshortname
                  FROM {role_assignments} ra
                  JOIN {user} u ON u.id = ra.userid
                  JOIN {role} r ON r.id = ra.roleid
                 WHERE ra.contextid $ctxsql
                   AND ra.roleid $rolesql
                   AND u.deleted = :deleted
                   AND u.suspended = :suspended
              ORDER BY r.sortorder ASC, u.lastname ASC, u.firstname ASC";

        $params     = array_merge($ctxparams, $roleparams, [
            'deleted' => 0,
            'suspended' => 0,
        ]);
        $allrecords = $DB->get_records_sql($sql, $params);

        if (empty($allrecords)) {
            return [];
        }

        $groupedbycontext = [];
        foreach ($allrecords as $record) {
            $groupedbycontext[$record->contextid][$record->id] = $record;
        }

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
     * @param array     $profesores Profesores grouped by role.
     * @param \stdClass $course     The course object.
     * @return string HTML output.
     */
    protected function render_profesores(array $profesores, \stdClass $course): string {
        global $USER, $PAGE, $DB;

        $this->load_settings();

        $displaystyle   = self::$settingscache['displaystyle'];
        $accentcolor    = self::$settingscache['accentcolor'];
        $cardcolor      = self::$settingscache['cardcolor'];
        $containerclass = 'filter-courseprofesores-container';
        if ($displaystyle !== 'cards') {
            $containerclass .= ' display-style-' . $displaystyle;
        }
        if ($accentcolor !== 'default') {
            $containerclass .= ' accent-color-' . $accentcolor;
        }
        if ($cardcolor !== 'default') {
            $containerclass .= ' card-color-' . $cardcolor;
        }

        // Emit the custom colour CSS (once per request) when a custom scheme is selected.
        $html = $this->get_custom_css();
        $html .= '<div class="' . $containerclass . '">';

        static $messagingenabled = null;
        if ($messagingenabled === null) {
            global $CFG;
            $messagingenabled = !empty($CFG->messaging);
        }

        // Collect all profesor IDs (excluding the current user) once. They are
        // reused by the unread-messages and last-access queries below.
        $allprofesorids = [];
        foreach ($profesores as $rolegroup) {
            foreach ($rolegroup['users'] as $profesor) {
                if ($profesor['id'] != $USER->id) {
                    $allprofesorids[$profesor['id']] = $profesor['id'];
                }
            }
        }

        $unreadreceived = [];
        $unreadsent = [];
        if ($messagingenabled && self::$settingscache['showmessagelink']) {
            if (!empty($allprofesorids)) {
                [$insql, $inparams] = $DB->get_in_or_equal($allprofesorids, SQL_PARAMS_NAMED, 'prof');
                $sql = "SELECT mcm2.userid AS profesorid,
                               SUM(CASE WHEN m.useridfrom = mcm2.userid THEN 1 ELSE 0 END) AS count_received,
                               SUM(CASE WHEN m.useridfrom = mcm1.userid THEN 1 ELSE 0 END) AS count_sent
                          FROM {messages} m
                          JOIN {message_conversation_members} mcm1
                            ON mcm1.conversationid = m.conversationid AND mcm1.userid = :studentid
                          JOIN {message_conversation_members} mcm2
                            ON mcm2.conversationid = m.conversationid AND mcm2.userid $insql
                         WHERE (
                             (m.useridfrom = mcm2.userid AND NOT EXISTS (
                                 SELECT 1 FROM {message_user_actions} mua
                                  WHERE mua.messageid = m.id
                                    AND mua.userid = mcm1.userid
                                    AND mua.action = :readaction1
                             ))
                             OR
                             (m.useridfrom = mcm1.userid AND NOT EXISTS (
                                 SELECT 1 FROM {message_user_actions} mua
                                  WHERE mua.messageid = m.id
                                    AND mua.userid = mcm2.userid
                                    AND mua.action = :readaction2
                             ))
                         )
                      GROUP BY mcm2.userid";
                $params = array_merge($inparams, [
                    'studentid'   => $USER->id,
                    'readaction1' => \core_message\api::MESSAGE_ACTION_READ,
                    'readaction2' => \core_message\api::MESSAGE_ACTION_READ,
                ]);
                $records = $DB->get_records_sql($sql, $params);
                foreach ($records as $record) {
                    $unreadreceived[$record->profesorid] = (int) $record->count_received;
                    $unreadsent[$record->profesorid] = (int) $record->count_sent;
                }
            }
        }

        // Fetch the last course access of every profesor in a single query,
        // used to render the online status indicator below.
        $lastaccesses = [];
        if (self::$settingscache['showonlinestatus'] && !empty($allprofesorids)) {
            [$lasql, $laparams] = $DB->get_in_or_equal($allprofesorids, SQL_PARAMS_NAMED, 'la');
            $lastaccesses = $DB->get_records_sql_menu(
                "SELECT userid, timeaccess
                   FROM {user_lastaccess}
                  WHERE courseid = :courseid
                    AND userid $lasql",
                array_merge(['courseid' => $course->id], $laparams)
            );
        }

        foreach ($profesores as $rolegroup) {
            if (empty($rolegroup['users'])) {
                continue;
            }

            $escapedrolename = s($rolegroup['name']);
            $stringkey = 'role_' . $rolegroup['shortname'];
            if (get_string_manager()->string_exists($stringkey, 'filter_courseprofesores')) {
                $roletitle = get_string($stringkey, 'filter_courseprofesores', $escapedrolename);
            } else {
                $roletitle = $escapedrolename;
            }

            $html .= '<div class="profesores-role-group">';
            $html .= '<h4 class="profesores-role-title">' . $roletitle . '</h4>';
            $html .= '<div class="profesores-list">';

            foreach ($rolegroup['users'] as $profesor) {
                $user       = (object) $profesor;
                $profileurl = new \moodle_url('/user/view.php', ['id' => $user->id, 'course' => $course->id]);
                $countreceived = $unreadreceived[$user->id] ?? 0;
                $countsent = $unreadsent[$user->id] ?? 0;

                $html .= '<div class="profesor-card">';

                if ($countreceived > 0 || $countsent > 0) {
                    $html .= '<div class="profesor-badges">';
                    if ($countreceived > 0) {
                        $html .= '<div class="profesor-badge badge-received" title="' .
                            get_string('unreadmessages_received', 'filter_courseprofesores') . '">';
                        $html .= '<i class="icon fa fa-commenting-o fa-fw" aria-hidden="true"></i>';
                        $html .= '<span class="badge-count">' . $countreceived . '</span>';
                        $html .= '</div>';
                    }
                    if ($countsent > 0) {
                        $html .= '<div class="profesor-badge badge-sent" title="' .
                            get_string('unreadmessages_sent', 'filter_courseprofesores') . '">';
                        $html .= '<i class="icon fa fa-paper-plane-o fa-fw" aria-hidden="true"></i>';
                        $html .= '<span class="badge-count">' . $countsent . '</span>';
                        $html .= '</div>';
                    }
                    $html .= '</div>';
                }

                if (self::$settingscache['showavatars']) {
                    $userpicture        = new \user_picture($user);
                    $userpicture->size  = 1;
                    $pictureurl         = $userpicture->get_url($PAGE)->out(false);

                    $html .= '<div class="profesor-avatar">';
                    $html .= '<a href="' . $profileurl->out(false) . '" title="' .
                        get_string('viewprofile', 'filter_courseprofesores') . '">';
                    $html .= '<img src="' . s($pictureurl) . '" alt="' . s($user->fullname) . '" class="userpicture" />';
                    $html .= '</a>';
                    $html .= '</div>';
                }

                $html .= '<div class="profesor-info">';
                $html .= '<a href="' . $profileurl->out(false) . '" class="profesor-name">' . s($user->fullname) . '</a>';

                // Online status / last access indicator (not shown on the current user's own card).
                if (self::$settingscache['showonlinestatus'] && $USER->id != $user->id && !empty($lastaccesses[$user->id])) {
                    $html .= $this->render_online_status((int) $lastaccesses[$user->id]);
                }

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
                            $messagelink = new \moodle_url('/message/index.php', ['id' => $user->id]);
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

        if (self::$settingscache['showparticipantslink']) {
            $coursecontext = \context_course::instance($course->id);
            if (has_capability('moodle/course:viewparticipants', $coursecontext)) {
                $participantsurl = new \moodle_url('/user/index.php', ['id' => $course->id]);
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

    /**
     * Render the online status / last access indicator for a profesor.
     *
     * A profesor is considered online when their last access to the course
     * happened within the last 5 minutes, matching the threshold used by
     * the core Online Users block.
     *
     * @param int $timeaccess Timestamp of the profesor's last access to the course.
     * @return string HTML output.
     */
    protected function render_online_status(int $timeaccess): string {
        $now = time();
        $isonline = ($timeaccess >= $now - 300);

        if ($isonline) {
            $statustext  = get_string('online', 'filter_courseprofesores');
            $statusclass = 'status-online';
            $icon        = 'fa-circle';
        } else {
            $statustext  = get_string('lastaccess', 'filter_courseprofesores', format_time($now - $timeaccess));
            $statusclass = 'status-offline';
            $icon        = 'fa-clock-o';
        }

        $html  = '<div class="profesor-status ' . $statusclass . '">';
        $html .= '<i class="icon fa ' . $icon . ' fa-fw" aria-hidden="true"></i>';
        $html .= '<span class="profesor-status-text">' . $statustext . '</span>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Build the inline <style> block for the custom colour schemes.
     *
     * The style is emitted only once per request (guarded by a static flag) and
     * only when the accent and/or card colour is set to "custom". The configured
     * colour-picker values drive the CSS custom properties, mirroring the built-in
     * orange/blue/pink presets: solid colours are used directly, while the
     * translucent tokens (secondary text, buttons, shadows, hover border) apply a
     * fixed alpha to the chosen base colour so the "premium" look is preserved.
     *
     * @return string The <style> block, or an empty string when nothing to emit.
     */
    protected function get_custom_css(): string {
        static $emitted = false;
        if ($emitted) {
            return '';
        }
        $emitted = true;

        $this->load_settings();

        $css = '';

        if (self::$settingscache['accentcolor'] === 'custom') {
            $brand      = self::$settingscache['customaccentcolor'];
            $brandhover = self::darken_color($brand, 0.82);
            $css .= '.filter-courseprofesores-container.accent-color-custom {';
            $css .= '--brand-color: ' . $brand . ';';
            $css .= '--brand-color-hover: ' . $brandhover . ';';
            $css .= '}';
        }

        if (self::$settingscache['cardcolor'] === 'custom') {
            $bg            = self::$settingscache['customcardcolor'];
            $border        = self::$settingscache['customcardbordercolor'];
            $text          = self::$settingscache['customcardtextcolor'];
            $textsecondary = self::rgba(self::$settingscache['customcardtextsecondarycolor'], 0.8);
            $btnbg         = self::rgba(self::$settingscache['customcardbuttoncolor'], 0.15);
            $btnbghover    = self::rgba(self::$settingscache['customcardbuttonhovercolor'], 0.3);
            $shadow        = self::$settingscache['customcardshadowcolor'];
            $borderhover   = self::rgba($text, 0.5);

            $prefix = '.filter-courseprofesores-container.card-color-custom';

            $css .= $prefix . ' .profesor-card {';
            $css .= '--card-bg: ' . $bg . ';';
            $css .= '--card-border: ' . $border . ';';
            $css .= '--text-primary: ' . $text . ';';
            $css .= '--text-secondary: ' . $textsecondary . ';';
            $css .= '--btn-bg: ' . $btnbg . ';';
            $css .= '--btn-bg-hover: ' . $btnbghover . ';';
            $css .= '--card-shadow: 0 4px 15px -2px ' . self::rgba($shadow, 0.4) . ';';
            $css .= '--card-shadow-hover: 0 10px 30px -4px ' . self::rgba($shadow, 0.5) . ';';
            $css .= '}';

            $css .= $prefix . ' .profesor-card:hover {';
            $css .= 'border-color: ' . $borderhover . ';';
            $css .= '}';

            $css .= $prefix . ' .profesor-name {';
            $css .= 'color: ' . $text . ';';
            $css .= '}';

            $css .= $prefix . ' .profesor-name:hover {';
            $css .= 'color: ' . $textsecondary . ';';
            $css .= '}';
        }

        if ($css === '') {
            return '';
        }

        return '<style>' . $css . '</style>';
    }

    /**
     * Validate a colour-picker value, returning a safe #rrggbb (or #rgb) string.
     *
     * @param mixed  $value   The stored config value.
     * @param string $default Fallback returned when the value is not a valid hex colour.
     * @return string A safe hex colour string.
     */
    protected static function sanitize_color($value, string $default): string {
        $value = trim((string) $value);
        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value)) {
            return $value;
        }
        return $default;
    }

    /**
     * Convert a #rrggbb (or #rgb) hex colour into an [r, g, b] triplet.
     *
     * @param string $hex A hex colour string (already sanitised).
     * @return array{0: int, 1: int, 2: int} The red, green and blue components.
     */
    protected static function hex_to_rgb(string $hex): array {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Build an rgba() CSS value from a hex colour and an alpha channel.
     *
     * @param string $hex   A hex colour string (already sanitised).
     * @param float  $alpha The alpha channel, between 0 and 1.
     * @return string The rgba() CSS value.
     */
    protected static function rgba(string $hex, float $alpha): string {
        [$r, $g, $b] = self::hex_to_rgb($hex);
        return 'rgba(' . $r . ', ' . $g . ', ' . $b . ', ' . rtrim(rtrim(sprintf('%.2F', $alpha), '0'), '.') . ')';
    }

    /**
     * Return a darker shade of the given hex colour, scaling each channel.
     *
     * @param string $hex    A hex colour string (already sanitised).
     * @param float  $factor The multiplier applied to each channel (0-1).
     * @return string The darkened #rrggbb colour.
     */
    protected static function darken_color(string $hex, float $factor): string {
        [$r, $g, $b] = self::hex_to_rgb($hex);
        return sprintf(
            '#%02x%02x%02x',
            (int) round($r * $factor),
            (int) round($g * $factor),
            (int) round($b * $factor)
        );
    }
}
