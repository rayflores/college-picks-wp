/* Small UI behaviors for week tabs and matchup card selection */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        // Week tab switching
        document.querySelectorAll('.cp-week-tab').forEach(function (tab) {
            tab.addEventListener('click', function (e) {
                e.preventDefault();
                var week = tab.getAttribute('data-week');
                // activate tab
                document.querySelectorAll('.cp-week-tab').forEach(function (t) { t.classList.remove('active'); });
                tab.classList.add('active');
                // show panel
                document.querySelectorAll('.cp-week-panel').forEach(function (p) {
                    if (p.getAttribute('data-week') === week) {
                        p.classList.add('active');
                    } else {
                        p.classList.remove('active');
                    }
                });
            });
        });

        // Matchup card selection (choose team)
        document.querySelectorAll('.cp-matchup-team').forEach(function (team) {
            team.addEventListener('click', function () {
                var parent = team.closest('.cp-matchup-card');
                if (!parent) return;
                // remove selected from siblings
                parent.querySelectorAll('.cp-matchup-team').forEach(function (t) { t.classList.remove('selected'); });
                team.classList.add('selected');
                // Optionally trigger a custom event
                var evt = new CustomEvent('cp:teamSelected', { detail: { matchup: parent.getAttribute('data-matchup-id'), team: team.getAttribute('data-team') } });
                parent.dispatchEvent(evt);
            });
        });
    });
})();
