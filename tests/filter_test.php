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

namespace filter_courseprofesores;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/filter/courseprofesores/filter.php');

/**
 * Unit tests for the courseprofesores filter.
 *
 * @package    filter_courseprofesores
 * @copyright  2026 Daniel Ferrada
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \filter_courseprofesores
 * @group      filter_courseprofesores
 */
final class filter_test extends \advanced_testcase {
    /** @var \filter_courseprofesores The filter instance. */
    protected $filter;

    /**
     * Set up test.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->filter = new text_filter(\context_system::instance(), []);
    }

    /**
     * Test that text without the tag is returned unchanged.
     */
    public function test_filter_text_without_tag(): void {
        $text = 'Hello World';
        $result = $this->filter->filter($text);
        $this->assertEquals('Hello World', $result);
    }

    /**
     * Test that empty text returns empty.
     */
    public function test_filter_empty_text(): void {
        $text = '';
        $result = $this->filter->filter($text);
        $this->assertEquals('', $result);
    }

    /**
     * Test that tag is removed on front page (SITEID).
     */
    public function test_tag_removed_on_frontpage(): void {
        global $COURSE;

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $originalcourse = $COURSE;
        $COURSE = get_site();

        $text = 'Welcome {courseprofesores} to our site';
        $result = $this->filter->filter($text);

        $COURSE = $originalcourse;

        $this->assertStringNotContainsString('{courseprofesores}', $result);
        $this->assertStringContainsString('Welcome  to our site', $result);
    }

    /**
     * Test that tag is replaced with profesores list.
     */
    public function test_tag_replaced_with_profesores(): void {
        global $COURSE;

        $course = $this->getDataGenerator()->create_course(['fullname' => 'Test Course']);
        $teacher = $this->getDataGenerator()->create_user([
            'firstname' => 'John',
            'lastname' => 'Doe',
        ]);
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');

        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $this->setUser($student);

        $text = 'Your profesores: {courseprofesores}';
        $context = \context_course::instance($course->id);

        $originalcourse = $COURSE;
        $COURSE = $course;
        $result = $this->filter->filter($text, ['context' => $context]);
        $COURSE = $originalcourse;

        $this->assertStringNotContainsString('{courseprofesores}', $result);
        $this->assertStringContainsString('John', $result);
        $this->assertStringContainsString('Doe', $result);
        $this->assertStringContainsString('filter-courseprofesores-container', $result);
    }

    /**
     * Test that multiple roles are grouped correctly.
     */
    public function test_profesores_grouped_by_role(): void {
        global $COURSE;

        $course = $this->getDataGenerator()->create_course();

        $teacher1 = $this->getDataGenerator()->create_user(['firstname' => 'Alice']);
        $teacher2 = $this->getDataGenerator()->create_user(['firstname' => 'Bob']);
        $editingteacher = $this->getDataGenerator()->create_user(['firstname' => 'Charlie']);

        $this->getDataGenerator()->enrol_user($teacher1->id, $course->id, 'teacher');
        $this->getDataGenerator()->enrol_user($teacher2->id, $course->id, 'teacher');
        $this->getDataGenerator()->enrol_user($editingteacher->id, $course->id, 'editingteacher');

        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $this->setUser($student);

        $text = '{courseprofesores}';
        $context = \context_course::instance($course->id);

        $originalcourse = $COURSE;
        $COURSE = $course;
        $result = $this->filter->filter($text, ['context' => $context]);
        $COURSE = $originalcourse;

        $this->assertStringContainsString('Alice', $result);
        $this->assertStringContainsString('Bob', $result);
        $this->assertStringContainsString('Charlie', $result);
        $this->assertStringContainsString('profesores-role-group', $result);
    }

    /**
     * Test that deleted users are not shown.
     */
    public function test_deleted_users_not_shown(): void {
        global $COURSE;

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user(['firstname' => 'Deleted', 'deleted' => 1]);

        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $this->setUser($student);

        $text = '{courseprofesores}';
        $context = \context_course::instance($course->id);

        $originalcourse = $COURSE;
        $COURSE = $course;
        $result = $this->filter->filter($text, ['context' => $context]);
        $COURSE = $originalcourse;

        $this->assertStringNotContainsString('Deleted', $result);
    }

    /**
     * Test that participants link is shown when user has capability.
     */
    public function test_participants_link_shown_for_enrolled_users(): void {
        global $COURSE;

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');

        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $this->setUser($student);

        $text = '{courseprofesores}';
        $context = \context_course::instance($course->id);

        $originalcourse = $COURSE;
        $COURSE = $course;
        $result = $this->filter->filter($text, ['context' => $context]);
        $COURSE = $originalcourse;

        $this->assertStringContainsString('participants-link', $result);
        $this->assertStringContainsString('user/index.php', $result);
    }

    /**
     * Test that message link is included.
     */
    public function test_message_link_included(): void {
        global $COURSE;

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');

        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $this->setUser($student);

        $text = '{courseprofesores}';
        $context = \context_course::instance($course->id);

        $originalcourse = $COURSE;
        $COURSE = $course;
        $result = $this->filter->filter($text, ['context' => $context]);
        $COURSE = $originalcourse;

        $this->assertStringContainsString('message-link', $result);
        $this->assertStringContainsString('message/index.php', $result);
    }

    /**
     * Test that profile link is included.
     */
    public function test_profile_link_included(): void {
        global $COURSE;

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');

        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $this->setUser($student);

        $text = '{courseprofesores}';
        $context = \context_course::instance($course->id);

        $originalcourse = $COURSE;
        $COURSE = $course;
        $result = $this->filter->filter($text, ['context' => $context]);
        $COURSE = $originalcourse;

        $this->assertStringContainsString('user/view.php', $result);
    }

    /**
     * Test that tag is replaced when empty course (no profesores).
     */
    public function test_empty_course_no_profesores(): void {
        global $COURSE;

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $this->setUser($student);

        $text = 'Profesores: {courseprofesores} End';
        $context = \context_course::instance($course->id);

        $result = $this->filter->filter($text, ['context' => $context]);

        $this->assertStringNotContainsString('{courseprofesores}', $result);
        $this->assertStringContainsString('Profesores:  End', $result);
    }
}
