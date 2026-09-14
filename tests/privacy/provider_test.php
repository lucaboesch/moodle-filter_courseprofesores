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
 * Unit tests for the privacy provider of filter_courseprofesores.
 *
 * @package    filter_courseprofesores
 * @copyright  2026 Daniel Ferrada
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_courseprofesores\privacy;

use core_privacy\local\metadata\null_provider;
use core_privacy\manager;

/**
 * Test that the privacy provider correctly implements the null provider interface.
 *
 * @copyright  2026 Daniel Ferrada
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \filter_courseprofesores\privacy\provider
 * @group      filter_courseprofesores
 */
final class provider_test extends \advanced_testcase {
    /**
     * Test that the provider class is registered and implements null_provider.
     */
    public function test_provider_implements_null_provider(): void {
        $classname = manager::get_provider_classname_for_component('filter_courseprofesores');
        $this->assertSame('filter_courseprofesores\\privacy\\provider', $classname);
        $this->assertTrue(class_exists('\\' . $classname));

        $reflectionclass = new \ReflectionClass($classname);
        $this->assertTrue($reflectionclass->implementsInterface(null_provider::class));
    }

    /**
     * Test that the null provider returns the expected reason string.
     */
    public function test_get_reason(): void {
        $this->assertSame('privacy:metadata', provider::get_reason());
    }
}
