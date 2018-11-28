/**
 * The administration panel JS scripts.
 *
 * Copyright (C) 2018 Invincible Brands GmbH
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
(function ($) {
    /**
     * Run the initialisation script when the document is ready.
     */
    $(document).ready(init);

    /**
     * Initialize the scripts.
     *
     * @returns {void}
     */
    function init() {
        if (!wcssot) {
            console.error('ERROR: The localization object "wcssot" is missing!');
            return;
        }

        $('#wcssot_form').on('submit', handle_form_submission);
    }

    /**
     * Handles the form submission event.
     *
     * @param e
     *
     * @returns {void}
     */
    function handle_form_submission(e) {
        $(this).find('input[type="submit"]').attr('disabled', 'disabled').val(wcssot.l10n.loading_text);
    }
})(jQuery);