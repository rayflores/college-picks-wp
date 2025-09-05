// Fetch AP Top 25 button handler for admin page
jQuery(document).ready(function ($) {
  $("#fetch-ap-top-25").on("click", function (e) {
    e.preventDefault();
    var $btn = $(this);
    var $status = $("#ap-top-25-status");
    $btn.prop("disabled", true);
    $status.text("Fetching AP Top 25 rankings...");
    $.post(
      cpAPTop25.ajax_url,
      {
        action: "cp_fetch_ap_top_25",
        nonce: cpAPTop25.nonce,
      },
      function (response) {
        if (response.success) {
          $status.text(response.data.message);
          console.log(response.data.data);
          //   setTimeout(function () {
          //     location.reload();
          //   }, 1500);
        } else {
          $status.text(
            response.data && response.data.message
              ? response.data.message
              : "Error fetching rankings."
          );
        }
        $btn.prop("disabled", false);
      }
    ).fail(function () {
      $status.text("AJAX request failed.");
      $btn.prop("disabled", false);
    });
  });
});

