// cp-make-picks.js
// Handles pick selection UI and enables Save button when all picks are made

document.addEventListener("DOMContentLoaded", function () {
  const pickCards = document.querySelectorAll(".card");
  const saveBtn = document.querySelector('button[type="submit"]');
  const pickInputs = document.querySelectorAll(
    'input[type="radio"][name^="cp_picks["]'
  );

  // Track picks per game
  function updateButtonStates() {
    // For each game, highlight the selected button
    pickCards.forEach(function (card) {
      const radios = card.querySelectorAll(
        'input[type="radio"][name^="cp_picks["]'
      );
      radios.forEach(function (radio) {
        const label = radio.closest("label");
        if (label) {
          if (radio.checked) {
            label.classList.add("active");
          } else {
            label.classList.remove("active");
          }
        }
      });
    });
    // Enable save button only if all games have a pick
    let allPicked = true;
    const gameIds = new Set();
    pickInputs.forEach(function (input) {
      const name = input.getAttribute("name");
      const gameId = name.match(/cp_picks\[(\d+)\]/)[1];
      gameIds.add(gameId);
    });
    gameIds.forEach(function (gameId) {
      const checked = document.querySelector(
        'input[name="cp_picks[' + gameId + ']"]:checked'
      );
      if (!checked) {
        allPicked = false;
      }
    });
    if (saveBtn) {
      saveBtn.disabled = !allPicked;
    }
  }

  // Initial state
  updateButtonStates();

  // Listen for changes
  pickInputs.forEach(function (input) {
    input.addEventListener("change", updateButtonStates);
  });
});

