/* Small UI behaviors for week tabs and matchup card selection */
(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    // Week tab switching for My Picks page
    document.querySelectorAll(".cp-week-tab").forEach(function (tab) {
      tab.addEventListener("click", function (e) {
        e.preventDefault();
        var week = tab.getAttribute("data-week");
        // activate tab
        document.querySelectorAll(".cp-week-tab").forEach(function (t) {
          t.classList.remove("active");
        });
        tab.classList.add("active");
        // show only the selected week card
        document.querySelectorAll(".cp-week-card").forEach(function (card) {
          if (card.getAttribute("data-week") === week) {
            card.style.display = "block";
          } else {
            card.style.display = "none";
          }
        });
      });
    });

    // Matchup card selection (choose team)
    document.querySelectorAll(".cp-matchup-team").forEach(function (team) {
      team.addEventListener("click", function () {
        var parent = team.closest(".cp-matchup-card");
        if (!parent) return;
        // remove selected from siblings
        parent.querySelectorAll(".cp-matchup-team").forEach(function (t) {
          t.classList.remove("selected");
        });
        team.classList.add("selected");
        // Optionally trigger a custom event
        var evt = new CustomEvent("cp:teamSelected", {
          detail: {
            matchup: parent.getAttribute("data-matchup-id"),
            team: team.getAttribute("data-team"),
          },
        });
        parent.dispatchEvent(evt);
      });
    });
  });
})();

