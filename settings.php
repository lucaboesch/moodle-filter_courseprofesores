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
 * Settings for the courseprofesores filter.
 *
 * @package    filter_courseprofesores
 * @copyright  2026 Daniel Ferrada
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading(
        'filter_courseprofesores/generalsettings',
        get_string('settingsheading', 'filter_courseprofesores'),
        ''
    ));

    $settings->add(new admin_setting_configcheckbox(
        'filter_courseprofesores/showavatars',
        get_string('showavatars', 'filter_courseprofesores'),
        get_string('showavatars_desc', 'filter_courseprofesores'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'filter_courseprofesores/showdepartment',
        get_string('showdepartment', 'filter_courseprofesores'),
        get_string('showdepartment_desc', 'filter_courseprofesores'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'filter_courseprofesores/showinstitution',
        get_string('showinstitution', 'filter_courseprofesores'),
        get_string('showinstitution_desc', 'filter_courseprofesores'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'filter_courseprofesores/showmessagelink',
        get_string('showmessagelink', 'filter_courseprofesores'),
        get_string('showmessagelink_desc', 'filter_courseprofesores'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'filter_courseprofesores/showonlinestatus',
        get_string('showonlinestatus', 'filter_courseprofesores'),
        get_string('showonlinestatus_desc', 'filter_courseprofesores'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'filter_courseprofesores/showparticipantslink',
        get_string('showparticipantslink', 'filter_courseprofesores'),
        get_string('showparticipantslink_desc', 'filter_courseprofesores'),
        1
    ));

    $roleoptions = [];
    if ($roles = get_all_roles()) {
        foreach ($roles as $role) {
            $roleoptions[$role->shortname] = role_get_name($role, context_system::instance());
        }
    }

    $settings->add(new admin_setting_configmulticheckbox(
        'filter_courseprofesores/rolesincluded',
        get_string('rolesincluded', 'filter_courseprofesores'),
        get_string('rolesincluded_desc', 'filter_courseprofesores'),
        ['editingteacher' => 1, 'teacher' => 1, 'manager' => 1],
        $roleoptions
    ));

    $styleoptions = [
        'cards' => get_string('displaystyle_cards', 'filter_courseprofesores'),
        'list' => get_string('displaystyle_list', 'filter_courseprofesores'),
        'compact' => get_string('displaystyle_compact', 'filter_courseprofesores'),
    ];

    $settings->add(new admin_setting_configselect(
        'filter_courseprofesores/displaystyle',
        get_string('displaystyle', 'filter_courseprofesores'),
        '',
        'cards',
        $styleoptions
    ));


    $coloroptions = [
        'default' => get_string('color_default', 'filter_courseprofesores'),
        'orange' => get_string('color_orange', 'filter_courseprofesores'),
        'blue' => get_string('color_blue', 'filter_courseprofesores'),
        'pink' => get_string('color_pink', 'filter_courseprofesores'),
        'custom' => get_string('customaccentcolor', 'filter_courseprofesores'),
    ];

    $settings->add(new admin_setting_configselect(
        'filter_courseprofesores/accentcolor',
        get_string('accentcolor', 'filter_courseprofesores'),
        get_string('accentcolor_desc', 'filter_courseprofesores'),
        'default',
        $coloroptions
    ));

    $cardcoloroptions = [
        'default' => get_string('cardcolor_default', 'filter_courseprofesores'),
        'orange' => get_string('cardcolor_orange', 'filter_courseprofesores'),
        'blue' => get_string('cardcolor_blue', 'filter_courseprofesores'),
        'pink' => get_string('cardcolor_pink', 'filter_courseprofesores'),
        'custom' => get_string('customcardcolor', 'filter_courseprofesores'),
    ];

    $settings->add(new admin_setting_configselect(
        'filter_courseprofesores/cardcolor',
        get_string('cardcolor', 'filter_courseprofesores'),
        get_string('cardcolor_desc', 'filter_courseprofesores'),
        'default',
        $cardcoloroptions
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'filter_courseprofesores/customaccentcolor',
        get_string('customaccentcolor', 'filter_courseprofesores'),
        get_string('customaccentcolor_desc', 'filter_courseprofesores'),
        '#0f6cbf'
    ));

    $settings->hide_if(
        'filter_courseprofesores/customaccentcolor',
        'filter_courseprofesores/accentcolor',
        'neq',
        'custom'
    );

    $settings->add(new admin_setting_configcolourpicker(
        'filter_courseprofesores/customcardcolor',
        get_string('customcardcolor', 'filter_courseprofesores'),
        get_string('customcardcolor_desc', 'filter_courseprofesores'),
        '#120ef2'
    ));

    $settings->hide_if(
        'filter_courseprofesores/customcardcolor',
        'filter_courseprofesores/cardcolor',
        'neq',
        'custom'
    );

    $settings->add(new admin_setting_configcolourpicker(
        'filter_courseprofesores/customcardbordercolor',
        get_string('customcardbordercolor', 'filter_courseprofesores'),
        get_string('customcardbordercolor_desc', 'filter_courseprofesores'),
        '#0e0bca'
    ));

    $settings->hide_if(
        'filter_courseprofesores/customcardbordercolor',
        'filter_courseprofesores/cardcolor',
        'neq',
        'custom'
    );

    $settings->add(new admin_setting_configcolourpicker(
        'filter_courseprofesores/customcardtextcolor',
        get_string('customcardtextcolor', 'filter_courseprofesores'),
        get_string('customcardtextcolor_desc', 'filter_courseprofesores'),
        '#ffffff'
    ));

    $settings->hide_if(
        'filter_courseprofesores/customcardtextcolor',
        'filter_courseprofesores/cardcolor',
        'neq',
        'custom'
    );

    $settings->add(new admin_setting_configcolourpicker(
        'filter_courseprofesores/customcardtextsecondarycolor',
        get_string('customcardtextsecondarycolor', 'filter_courseprofesores'),
        get_string('customcardtextsecondarycolor_desc', 'filter_courseprofesores'),
        '#ffffff'
    ));

    $settings->hide_if(
        'filter_courseprofesores/customcardtextsecondarycolor',
        'filter_courseprofesores/cardcolor',
        'neq',
        'custom'
    );

    $settings->add(new admin_setting_configcolourpicker(
        'filter_courseprofesores/customcardbuttoncolor',
        get_string('customcardbuttoncolor', 'filter_courseprofesores'),
        get_string('customcardbuttoncolor_desc', 'filter_courseprofesores'),
        '#ffffff'
    ));

    $settings->hide_if(
        'filter_courseprofesores/customcardbuttoncolor',
        'filter_courseprofesores/cardcolor',
        'neq',
        'custom'
    );

    $settings->add(new admin_setting_configcolourpicker(
        'filter_courseprofesores/customcardbuttonhovercolor',
        get_string('customcardbuttonhovercolor', 'filter_courseprofesores'),
        get_string('customcardbuttonhovercolor_desc', 'filter_courseprofesores'),
        '#ffffff'
    ));

    $settings->hide_if(
        'filter_courseprofesores/customcardbuttonhovercolor',
        'filter_courseprofesores/cardcolor',
        'neq',
        'custom'
    );

    $settings->add(new admin_setting_configcolourpicker(
        'filter_courseprofesores/customcardshadowcolor',
        get_string('customcardshadowcolor', 'filter_courseprofesores'),
        get_string('customcardshadowcolor_desc', 'filter_courseprofesores'),
        '#120ef2'
    ));

    $settings->hide_if(
        'filter_courseprofesores/customcardshadowcolor',
        'filter_courseprofesores/cardcolor',
        'neq',
        'custom'
    );
}
