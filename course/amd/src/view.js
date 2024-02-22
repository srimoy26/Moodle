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
 * JS module for the course homepage.
 *
 * @module      core_course/view
 * @copyright   2021 Jun Pataleta <jun@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as CourseEvents from 'core_course/events';

/**
 * Whether the event listener has already been registered for this module.
 *
 * @type {boolean}
 */
let registered = false;

/**
 * Function to intialise and register event listeners for this module.
 */
export const init = () => {
    if (registered) {
        return;
    }
    // Listen for toggled manual completion states of activities.
    document.addEventListener(CourseEvents.manualCompletionToggled, (e) => {
        const withAvailability = parseInt(e.detail.withAvailability);
        if (withAvailability) {
            // Reload the page when the toggled manual completion button has availability conditions linked to it.
            window.location.reload();
        }
    });
    registered = true;
   
};
export const Tab =()=> {
    document.addEventListener('DOMContentLoaded', function() {
            // Add your JavaScript functionality here
            // For example, code to switch between tabs
            const weakButton = document.getElementById('weakButton');
            const allButton = document.getElementById('allButton');
            const weakTopics = document.getElementById('weakTopics');
            const allTopics = document.getElementById('allTopics');
        
            weakButton.addEventListener('click', function() {
                weakButton.classList.add('selected');
                allButton.classList.remove('selected');
                weakTopics.style.display = 'block';
                allTopics.style.display = 'none';
            });
        
            allButton.addEventListener('click', function() {
                allButton.classList.add('selected');
                weakButton.classList.remove('selected');
                allTopics.style.display = 'block';
                weakTopics.style.display = 'none';
            });
        });
    };

    function handleWeakButtonClick() {
        // Handle click on weakButton
        console.log("Weak Topics button clicked");
        // You can add your custom logic here
    }

    function handleAllButtonClick() {
        // Handle click on allButton
        console.log("All Topics button clicked");
        // You can add your custom logic here
    }