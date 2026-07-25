(function ($) {
  "use strict";

  function confirmDelete(e) {
    if (!window.confirm(ctaAdmin.i18n.confirmDelete)) {
      e.preventDefault();
    }
  }

  function copyShortcode(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      return navigator.clipboard.writeText(text);
    }

    var temp = $("<textarea>");
    $("body").append(temp);
    temp.val(text).select();
    document.execCommand("copy");
    temp.remove();
    return $.Deferred().resolve().promise();
  }

  function initCopyButtons() {
    $(document).on("click", ".cta-copy-shortcode", function () {
      var btn = $(this);
      var code = btn.data("shortcode");

      copyShortcode(code).then(function () {
        var original = btn.text();
        btn.text(ctaAdmin.i18n.copied);
        setTimeout(function () {
          btn.text(original);
        }, 1500);
      });
    });
  }

  function initDeleteConfirms() {
    $(document).on("click", ".cta-delete-course", confirmDelete);
  }

  function initSlugGeneration() {
    var $title = $("#cta-course-title");
    var $slug = $("#cta-course-slug");

    if (!$title.length || !$slug.length) {
      return;
    }

    var slugEdited = !!$slug.val();

    $slug.on("input", function () {
      slugEdited = true;
    });

    $title.on("input", function () {
      if (slugEdited && $slug.val()) {
        return;
      }

      $slug.val(
        $title
          .val()
          .toLowerCase()
          .replace(/[^a-z0-9]+/g, "-")
          .replace(/^-+|-+$/g, "")
      );
    });
  }

  function initObjectivesRepeater() {
    $("#cta-add-objective").on("click", function () {
      $("#cta-objectives-repeater").append(
        '<div class="cta-objective-row">' +
          '<input type="text" class="regular-text" name="learning_objectives[]" value="">' +
          '<button type="button" class="button cta-remove-objective">Remove</button>' +
          "</div>"
      );
    });

    $(document).on("click", ".cta-remove-objective", function () {
      var rows = $("#cta-objectives-repeater .cta-objective-row");
      if (rows.length <= 1) {
        rows.find("input").val("");
        return;
      }
      $(this).closest(".cta-objective-row").remove();
    });
  }

  function initModulesPanel() {
    var $panel = $("#cta-modules-panel");

    if (!$panel.length) {
      return;
    }

    var courseId = $panel.data("course-id");

    $("#cta-modules-list").sortable({
      handle: ".cta-module-row__handle",
      update: function () {
        var order = [];
        $("#cta-modules-list .cta-module-row").each(function () {
          order.push($(this).data("module-id"));
        });

        $.post(ctaAdmin.ajaxUrl, {
          action: "cta_reorder_modules",
          nonce: ctaAdmin.nonce,
          course_id: courseId,
          order: order
        });
      }
    });

    $("#cta-save-module").on("click", function () {
      var btn = $(this);
      btn.prop("disabled", true);

      $.post(ctaAdmin.ajaxUrl, {
        action: "cta_save_module",
        nonce: ctaAdmin.nonce,
        course_id: courseId,
        module_id: $("#cta-module-id").val(),
        title: $("#cta-module-title").val(),
        description: $("#cta-module-description").val(),
        video_url: normalizeModuleVideoUrl(),
        duration_mins: $("#cta-module-duration").val(),
        is_locked: $("#cta-module-locked").is(":checked") ? 1 : 0
      })
        .done(function (response) {
          if (!response.success) {
            window.alert(
              response.data && response.data.message
                ? response.data.message
                : "Unable to save module."
            );
            return;
          }

          var moduleId = $("#cta-module-id").val();
          if (moduleId) {
            $('#cta-modules-list .cta-module-row[data-module-id="' + moduleId + '"]').replaceWith(
              response.data.html
            );
          } else {
            $("#cta-modules-list").append(response.data.html);
          }

          $("#cta-module-id, #cta-module-title, #cta-module-description, #cta-module-video, #cta-module-duration").val("");
          $("#cta-module-locked").prop("checked", true);
          btn.text("Add Module");
        })
        .always(function () {
          btn.prop("disabled", false);
        });
    });

    $(document).on("click", ".cta-delete-module", function () {
      if (!window.confirm(ctaAdmin.i18n.confirmDelete)) {
        return;
      }

      var row = $(this).closest(".cta-module-row");
      var moduleId = $(this).data("module-id");

      $.post(ctaAdmin.ajaxUrl, {
        action: "cta_delete_module",
        nonce: ctaAdmin.nonce,
        course_id: courseId,
        module_id: moduleId
      }).done(function (response) {
        if (response.success) {
          row.remove();
        }
      });
    });

    function toggleVideoSourceUI() {
      var type = $("#cta-module-video-type").val();
      var $input = $("#cta-module-video");
      var $select = $("#cta-module-video-select");
      var $help = $(".cta-module-video-help");

      $help.hide();
      $help.filter('[data-help="' + type + '"]').show();

      if (type === "wordpress") {
        $input.attr("placeholder", "Select a video from Media Library");
        $input.prop("readonly", true);
        $select.show();
      } else if (type === "youtube") {
        $input.attr("placeholder", "https://www.youtube.com/watch?v=...");
        $input.prop("readonly", false);
        $select.hide();
      } else if (type === "vimeo") {
        $input.attr("placeholder", "Vimeo ID or https://vimeo.com/123456789");
        $input.prop("readonly", false);
        $select.hide();
      } else {
        $input.attr("placeholder", "https://example.com/video.mp4");
        $input.prop("readonly", false);
        $select.hide();
      }
    }

    function normalizeModuleVideoUrl() {
      var type = $("#cta-module-video-type").val();
      var value = String($("#cta-module-video").val() || "").trim();

      if (!value) {
        return "";
      }

      if (type === "vimeo") {
        var vimeoId = value.replace(/\D/g, "");
        return vimeoId ? "https://vimeo.com/" + vimeoId : "";
      }

      return value;
    }

    if ($("#cta-module-video-type").length) {
      $("#cta-module-video-type").on("change", function () {
        $("#cta-module-video").val("");
        toggleVideoSourceUI();
      });
      toggleVideoSourceUI();
    }

    $("#cta-module-video-select").on("click", function (e) {
      e.preventDefault();

      if (typeof wp === "undefined" || !wp.media) {
        window.alert("WordPress media library is not available.");
        return;
      }

      var frame = wp.media({
        title: "Select Video",
        button: { text: "Use this video" },
        library: { type: "video" },
        multiple: false
      });

      frame.on("select", function () {
        var attachment = frame.state().get("selection").first().toJSON();
        $("#cta-module-video").val(attachment.url || "");
      });

      frame.open();
    });

    $(document).on("click", ".cta-edit-module", function () {
      var $row = $(this).closest(".cta-module-row");
      $("#cta-module-id").val($row.data("module-id"));
      $("#cta-module-title").val($row.data("title") || "");
      $("#cta-module-description").val($row.data("description") || "");
      $("#cta-module-video").val($row.data("video-url") || "");
      $("#cta-module-duration").val($row.data("duration") || "");
      $("#cta-module-locked").prop("checked", String($row.data("locked")) !== "0");

      var videoUrl = String($row.data("video-url") || "");
      if (videoUrl.indexOf("youtube.com") !== -1 || videoUrl.indexOf("youtu.be") !== -1) {
        $("#cta-module-video-type").val("youtube");
      } else if (videoUrl.indexOf("vimeo.com") !== -1) {
        $("#cta-module-video-type").val("vimeo");
        var vimeoMatch = videoUrl.match(/vimeo\.com\/(?:video\/)?(\d+)/);
        $("#cta-module-video").val(vimeoMatch ? vimeoMatch[1] : videoUrl);
      } else if (videoUrl.indexOf("/wp-content/") !== -1) {
        $("#cta-module-video-type").val("wordpress");
      } else {
        $("#cta-module-video-type").val("url");
      }

      toggleVideoSourceUI();
      $("#cta-save-module").text("Update Module");
      $("html, body").animate({ scrollTop: $("#cta-modules-panel").offset().top - 40 }, 200);
    });
  }

  function buildQuizQuestionCard(index, data) {
    data = data || {};

    return (
      '<div class="cta-quiz-question-card" data-index="' +
      index +
      '">' +
      '<div class="cta-quiz-question-card__header">' +
      "<strong>Question " +
      (index + 1) +
      "</strong>" +
      '<button type="button" class="button-link-delete cta-remove-quiz-question">Remove</button>' +
      "</div>" +
      '<p><textarea class="large-text cta-q-text" rows="2" placeholder="Question text">' +
      (data.question_text || "") +
      "</textarea></p>" +
      '<div class="cta-quiz-options-grid">' +
      '<p><label>Option A</label><input type="text" class="regular-text cta-q-a" value="' +
      (data.option_a || "") +
      '"></p>' +
      '<p><label>Option B</label><input type="text" class="regular-text cta-q-b" value="' +
      (data.option_b || "") +
      '"></p>' +
      '<p><label>Option C</label><input type="text" class="regular-text cta-q-c" value="' +
      (data.option_c || "") +
      '"></p>' +
      '<p><label>Option D</label><input type="text" class="regular-text cta-q-d" value="' +
      (data.option_d || "") +
      '"></p>' +
      "</div>" +
      '<p><label>Correct Answer</label> ' +
      '<select class="cta-q-correct">' +
      '<option value="a"' +
      (data.correct_option === "a" ? " selected" : "") +
      ">A</option>" +
      '<option value="b"' +
      (data.correct_option === "b" ? " selected" : "") +
      ">B</option>" +
      '<option value="c"' +
      (data.correct_option === "c" ? " selected" : "") +
      ">C</option>" +
      '<option value="d"' +
      (data.correct_option === "d" ? " selected" : "") +
      ">D</option>" +
      "</select></p>" +
      '<p><label>Explanation (shown after quiz)</label>' +
      '<textarea class="large-text cta-q-explanation" rows="2" placeholder="Optional explanation">' +
      (data.explanation || "") +
      "</textarea></p>" +
      "</div>"
    );
  }

  function renderQuizQuestions(questions) {
    var $list = $("#cta-quiz-questions");
    $list.empty();

    if (!questions || !questions.length) {
      $list.append(buildQuizQuestionCard(0, {}));
      return;
    }

    questions.forEach(function (question, index) {
      $list.append(buildQuizQuestionCard(index, question));
    });
  }

  function collectQuizQuestions() {
    var questions = [];

    $("#cta-quiz-questions .cta-quiz-question-card").each(function (index) {
      var $card = $(this);
      var questionText = $.trim($card.find(".cta-q-text").val());

      if (!questionText) {
        return;
      }

      questions.push({
        question_text: questionText,
        option_a: $.trim($card.find(".cta-q-a").val()),
        option_b: $.trim($card.find(".cta-q-b").val()),
        option_c: $.trim($card.find(".cta-q-c").val()),
        option_d: $.trim($card.find(".cta-q-d").val()),
        correct_option: $card.find(".cta-q-correct").val() || "a",
        explanation: $.trim($card.find(".cta-q-explanation").val()),
        order_index: index
      });
    });

    return questions;
  }

  function renderQuizSavedList(questions, quizTitle) {
    var $list = $("#cta-quiz-saved-list");
    var $status = $("#cta-quiz-status-line");

    if (quizTitle) {
      $status.html(
        "<p>Quiz exists for this course. <strong>" +
          $("<div>").text(quizTitle).html() +
          "</strong></p>"
      );
    }

    if (!questions || !questions.length) {
      $list.empty();
      if (!quizTitle) {
        $status.html("<p>No quiz created yet.</p>");
      }
      return;
    }

    var html =
      "<h3>Saved Questions (" + questions.length + ")</h3>" +
      '<ol class="cta-quiz-saved-list__items">';

    questions.forEach(function (question, index) {
      var text = question.question_text || "";
      if (text.length > 90) {
        text = text.substring(0, 90) + "...";
      }

      html +=
        "<li><strong>Q" +
        (index + 1) +
        ":</strong> " +
        $("<div>").text(text).html() +
        ' <span class="cta-quiz-saved-list__answer">(' +
        String(question.correct_option || "a").toUpperCase() +
        ")</span></li>";
    });

    html += "</ol>";
    $list.html(html);
  }

  function loadQuizPanel(courseId) {
    return $.post(ctaAdmin.ajaxUrl, {
      action: "cta_load_quiz",
      nonce: ctaAdmin.nonce,
      course_id: courseId
    }).done(function (response) {
      if (response.success) {
        if (response.data.quiz && response.data.quiz.title) {
          $("#cta-quiz-title").val(response.data.quiz.title);
        }
        renderQuizQuestions(response.data.questions || []);
        renderQuizSavedList(
          response.data.questions || [],
          response.data.quiz ? response.data.quiz.title : ""
        );
      } else {
        renderQuizQuestions([]);
        renderQuizSavedList([], "");
      }
    });
  }

  function initQuizPanel() {
    var $panel = $("#cta-quiz-panel");

    if (!$panel.length) {
      return;
    }

    var courseId = $panel.data("course-id");

    loadQuizPanel(courseId).fail(function () {
      renderQuizQuestions([]);
    });

    $("#cta-add-quiz-question").on("click", function () {
      var count = $("#cta-quiz-questions .cta-quiz-question-card").length;
      $("#cta-quiz-questions").append(buildQuizQuestionCard(count, {}));
    });

    $(document).on("click", ".cta-remove-quiz-question", function () {
      var $cards = $("#cta-quiz-questions .cta-quiz-question-card");

      if ($cards.length <= 1) {
        $cards.find("input, textarea").val("");
        $cards.find(".cta-q-correct").val("a");
        return;
      }

      $(this).closest(".cta-quiz-question-card").remove();

      $("#cta-quiz-questions .cta-quiz-question-card").each(function (idx) {
        $(this).attr("data-index", idx);
        $(this)
          .find(".cta-quiz-question-card__header strong")
          .text("Question " + (idx + 1));
      });
    });

    $("#cta-save-quiz").on("click", function () {
      var btn = $(this);
      var $status = $("#cta-quiz-save-status");
      var questions = collectQuizQuestions();

      if (!questions.length) {
        window.alert("Please add at least one question.");
        return;
      }

      btn.prop("disabled", true).text("Saving...");
      $status.removeClass("is-success is-error").text("");

      $.post(ctaAdmin.ajaxUrl, {
        action: "cta_save_quiz",
        nonce: ctaAdmin.nonce,
        course_id: courseId,
        quiz_title: $("#cta-quiz-title").val(),
        questions_json: JSON.stringify(questions)
      })
        .done(function (response) {
          if (!response.success) {
            $status
              .addClass("is-error")
              .text(
                response.data && response.data.message
                  ? response.data.message
                  : "Unable to save quiz."
              );
            return;
          }

          $status.addClass("is-success").text(response.data.message || "Quiz saved.");
          btn.text("Save Quiz");
          renderQuizQuestions(response.data.questions || collectQuizQuestions());
          renderQuizSavedList(
            response.data.questions || collectQuizQuestions(),
            response.data.quiz ? response.data.quiz.title : $("#cta-quiz-title").val()
          );
        })
        .always(function () {
          btn.prop("disabled", false);
        });
    });
  }

  function initStripeTest() {
    $("#cta-test-stripe").on("click", function () {
      var $result = $("#cta-stripe-test-result");
      $result.removeClass("is-success is-error").text(ctaAdmin.i18n.stripeTesting);

      $.post(ctaAdmin.ajaxUrl, {
        action: "cta_test_stripe_connection",
        nonce: ctaAdmin.nonce,
        secret_key: $("#cta_stripe_secret_key").val()
      }).done(function (response) {
        if (response.success) {
          $result.addClass("is-success").text(response.data.message || ctaAdmin.i18n.stripeSuccess);
          return;
        }

        $result
          .addClass("is-error")
          .text(response.data && response.data.message ? response.data.message : ctaAdmin.i18n.stripeFailed);
      });
    });
  }

  function initCertificatePreview() {
    $("#cta-preview-certificate").on("click", function () {
      $.post(ctaAdmin.ajaxUrl, {
        action: "cta_preview_certificate",
        nonce: ctaAdmin.nonce
      }).done(function (response) {
        if (!response.success || !response.data || !response.data.html) {
          window.alert("Unable to generate preview.");
          return;
        }

        var preview = window.open("", "_blank");
        if (preview) {
          preview.document.open();
          preview.document.write(response.data.html);
          preview.document.close();
        }
      });
    });
  }

  function initEmailSettings() {
    var $settings = $(".cta-email-settings");
    if (!$settings.length) return;

    function getEditorContent(editorId) {
      if (
        window.tinymce &&
        window.tinymce.get(editorId) &&
        !window.tinymce.get(editorId).isHidden()
      ) {
        return window.tinymce.get(editorId).getContent();
      }

      return $("#" + editorId).val() || "";
    }

    $settings.on("click", ".cta-email-tab", function () {
      var type = $(this).data("email-tab");

      $(".cta-email-tab")
        .removeClass("cta-email-tab--active")
        .attr("aria-selected", "false");
      $(this)
        .addClass("cta-email-tab--active")
        .attr("aria-selected", "true");

      $(".cta-email-panel").prop("hidden", true);
      $('.cta-email-panel[data-email-panel="' + type + '"]').prop(
        "hidden",
        false
      );
    });

    $settings.on("click", ".cta-preview-email", function () {
      var $button = $(this);
      var $panel = $button.closest(".cta-email-panel");
      var $result = $button.siblings(".cta-inline-result");
      var type = $button.data("email-type");
      var editorId = $button.data("editor-id");

      $button.prop("disabled", true);
      $result.removeClass("is-error is-success").text("Generating preview...");

      $.post(ctaAdmin.ajaxUrl, {
        action: "cta_preview_email",
        nonce: ctaAdmin.nonce,
        email_type: type,
        subject: $panel.find(".cta-email-subject").val(),
        body: getEditorContent(editorId)
      })
        .done(function (response) {
          if (!response.success || !response.data || !response.data.html) {
            $result
              .addClass("is-error")
              .text(
                response.data && response.data.message
                  ? response.data.message
                  : "Unable to generate preview."
              );
            return;
          }

          $("#cta-email-preview-subject").text(
            response.data.subject || "Email Preview"
          );

          var frame = document.getElementById("cta-email-preview-frame");
          var frameDocument =
            frame.contentDocument ||
            (frame.contentWindow && frame.contentWindow.document);

          if (frameDocument) {
            frameDocument.open();
            frameDocument.write(response.data.html);
            frameDocument.close();
          }

          $("#cta-email-preview-modal").prop("hidden", false);
          $result.addClass("is-success").text("Preview ready.");
        })
        .fail(function () {
          $result.addClass("is-error").text("Unable to generate preview.");
        })
        .always(function () {
          $button.prop("disabled", false);
        });
    });

    $settings.find("form").on("submit", function () {
      if (window.tinyMCE && typeof window.tinyMCE.triggerSave === "function") {
        window.tinyMCE.triggerSave();
      }
    });
  }

  function initUserStats() {
    $(document).on("click", ".cta-view-user-stats", function () {
      var userId = $(this).data("user-id");
      var $modal = $("#cta-user-stats-modal");
      var $body = $("#cta-user-stats-body");

      $body.text("Loading...");
      $modal.prop("hidden", false);

      $.post(ctaAdmin.ajaxUrl, {
        action: "cta_admin_get_stats",
        nonce: ctaAdmin.nonce,
        user_id: userId
      }).done(function (response) {
        if (!response.success) {
          $body.text("Unable to load stats.");
          return;
        }

        var data = response.data;
        $body.html(
          "<p><strong>Courses Enrolled:</strong> " + data.courses_enrolled + "</p>" +
            "<p><strong>Courses Completed:</strong> " + data.courses_completed + "</p>" +
            "<p><strong>Certificates:</strong> " + data.certificates_count + "</p>" +
            "<p><strong>Supervision Status:</strong> " + (data.supervision_status || "—") + "</p>" +
            "<p><strong>Total Paid:</strong> $" + data.total_paid + "</p>"
        );
      });
    });
  }

  function initModals() {
    $(document).on("click", ".cta-admin-modal__close", function () {
      $(this).closest(".cta-admin-modal").prop("hidden", true);
    });

    $("#cta-open-session-modal").on("click", function () {
      $("#cta-session-modal").prop("hidden", false);
    });

    $("#cta-session-type").on("change", function () {
      if ($(this).val() === "individual") {
        $("#cta-session-seats-wrap").hide();
      } else {
        $("#cta-session-seats-wrap").show();
      }
    });
  }

  function initBookings() {
    $("#cta-add-session-form").on("submit", function (e) {
      e.preventDefault();

      $.post(ctaAdmin.ajaxUrl, {
        action: "cta_admin_add_session",
        nonce: ctaAdmin.nonce,
        session_date: $("#cta-session-date").val(),
        session_time: $("#cta-session-time").val(),
        session_type: $("#cta-session-type").val(),
        seats_total: $("#cta-session-seats").val()
      }).done(function (response) {
        if (!response.success) {
          window.alert(
            response.data && response.data.message
              ? response.data.message
              : "Unable to create session."
          );
          return;
        }

        $("#cta-sessions-list").append(response.data.html);
        $("#cta-session-modal").prop("hidden", true);
        $("#cta-add-session-form")[0].reset();
      });
    });

    $(document).on("click", ".cta-cancel-session", function () {
      if (!window.confirm(ctaAdmin.i18n.confirmCancel)) {
        return;
      }

      var row = $(this).closest("tr");
      var sessionId = $(this).data("session-id");

      $.post(ctaAdmin.ajaxUrl, {
        action: "cta_admin_cancel_session",
        nonce: ctaAdmin.nonce,
        session_id: sessionId
      }).done(function (response) {
        if (response.success) {
          row.remove();
        }
      });
    });
  }

  function initCourseVideoSource() {
    if (!$("#cta-course-video-type").length) {
      return;
    }

    function toggleCourseVideoUI() {
      var type = $("#cta-course-video-type").val();
      var $input = $("#cta-course-video-value");
      var $hidden = $("#cta-course-video-url");
      var $select = $("#cta-course-video-select");
      var $help = $(".cta-course-video-help");

      $help.hide();
      $help.filter('[data-help="' + type + '"]').show();

      if (type === "wordpress") {
        $input.attr("placeholder", "Select a video from Media Library");
        $input.prop("readonly", true);
        $select.show();
      } else if (type === "youtube") {
        $input.attr("placeholder", "https://www.youtube.com/watch?v=...");
        $input.prop("readonly", false);
        $select.hide();
      } else if (type === "vimeo") {
        $input.attr("placeholder", "Vimeo ID (numbers only)");
        $input.prop("readonly", false);
        $select.hide();
      } else {
        $input.attr("placeholder", "https://example.com/video.mp4");
        $input.prop("readonly", false);
        $select.hide();
      }

      if (type === "wordpress" && $hidden.val()) {
        $input.val($hidden.val());
      }
    }

    $("#cta-course-video-type").on("change", function () {
      $("#cta-course-video-value, #cta-course-video-url").val("");
      toggleCourseVideoUI();
    });

    $("#cta-course-video-select").on("click", function (e) {
      e.preventDefault();

      if (typeof wp === "undefined" || !wp.media) {
        window.alert("WordPress media library is not available.");
        return;
      }

      var frame = wp.media({
        title: "Select Video",
        button: { text: "Use this video" },
        library: { type: "video" },
        multiple: false
      });

      frame.on("select", function () {
        var attachment = frame.state().get("selection").first().toJSON();
        $("#cta-course-video-value").val(attachment.url || "");
        $("#cta-course-video-url").val(attachment.url || "");
      });

      frame.open();
    });

    toggleCourseVideoUI();
  }

  function initAssociateApprovals() {
    var $table = $("#cta-pending-approvals-table");
    var $notice = $("#cta-approvals-notice");
    var $detailsModal = $("#cta-purchase-details-modal");
    var $rejectModal = $("#cta-reject-associate-modal");

    if (!$table.length || typeof ctaAdmin === "undefined") {
      return;
    }

    function showNotice(type, message) {
      if (!$notice.length) {
        return;
      }

      $notice
        .removeClass("notice-success notice-error")
        .addClass(type === "success" ? "notice-success" : "notice-error")
        .html("<p>" + $("<div>").text(message).html() + "</p>")
        .prop("hidden", false)
        .removeAttr("hidden")
        .show();
    }

    function reloadApprovalsPage(flash) {
      var url = new URL(window.location.href);
      url.searchParams.set("cta_approval", flash);
      window.location.href = url.toString();
    }

    $table.on("click", ".cta-view-supervision-purchase", function () {
      var details = {};
      var rawDetails = $(this).attr("data-purchase-details");

      try {
        details = rawDetails ? JSON.parse(rawDetails) : {};
      } catch (e) {
        details = {};
      }

      var $list = $("#cta-purchase-details-list").empty();
      var fields = [
        ["Associate", details.user_name],
        ["Email", details.user_email],
        ["Plan", details.plan_name],
        ["Registered", details.registered_date],
        ["Purchase Date", details.purchase_date],
        ["Amount", details.amount],
        ["Billing", details.billing],
        ["Status", details.status],
        ["Description", details.description],
        ["Stripe Reference", details.stripe_reference],
        ["Rejection Reason", details.rejection_reason]
      ];

      fields.forEach(function (field) {
        if (!field[1]) return;
        $("<dt>").text(field[0]).appendTo($list);
        $("<dd>").text(field[1]).appendTo($list);
      });

      $detailsModal.prop("hidden", false).removeAttr("hidden");
    });

    $table.on("click", ".cta-open-reject-associate", function () {
      var $button = $(this);
      var userId = $button.data("user-id");
      var userName = $button.data("user-name") || "";

      $("#cta-reject-user-id").val(userId);
      $("#cta-reject-nonce").val($button.data("review-nonce") || "");
      $("#cta-rejection-reason").val("");
      $("#cta-reject-associate-name").text(
        userName ? "Reject supervision access for " + userName + "?" : ""
      );
      $rejectModal.prop("hidden", false).removeAttr("hidden");
    });

    function handleFormSubmit(e) {
      var $form = $(this);
      var isApprove = $form.hasClass("cta-approval-form--approve");
      var userId = $form.find('input[name="user_id"]').val();
      var reason = $form.find('[name="reason"]').val() || "";
      var confirmMsg = isApprove
        ? ctaAdmin.i18n.approveConfirm
        : ctaAdmin.i18n.rejectConfirm;

      if (!window.confirm(confirmMsg)) {
        e.preventDefault();
        return;
      }

      // Prefer AJAX when available; fall back to normal form POST.
      if (!window.jQuery || !ctaAdmin.ajaxUrl) {
        return;
      }

      e.preventDefault();

      var $row = $form.closest("tr");
      var $buttons = $row.length ? $row.find("button") : $form.find("button");

      $buttons.prop("disabled", true);

      $.post(ctaAdmin.ajaxUrl, {
        action: isApprove ? "cta_approve_associate" : "cta_reject_associate",
        nonce: ctaAdmin.nonce,
        user_id: userId,
        reason: reason
      })
        .done(function (response) {
          if (!response || !response.success) {
            $buttons.prop("disabled", false);
            showNotice(
              "error",
              (response && response.data && response.data.message) ||
                ctaAdmin.i18n.actionFailed
            );
            return;
          }

          showNotice(
            "success",
            (response.data && response.data.message) ||
              (isApprove
                ? ctaAdmin.i18n.approveSuccess
                : ctaAdmin.i18n.rejectSuccess)
          );

          $rejectModal.prop("hidden", true);

          // Keep the record visible: reload so Approved/Rejected tabs stay in sync.
          reloadApprovalsPage(isApprove ? "approved" : "rejected");
        })
        .fail(function (xhr) {
          $buttons.prop("disabled", false);
          var msg = ctaAdmin.i18n.actionFailed;
          if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
            msg = xhr.responseJSON.data.message;
          }
          showNotice("error", msg);
        });
    }

    $table.on("submit", ".cta-approval-form", handleFormSubmit);
    $rejectModal.on("submit", ".cta-approval-form", handleFormSubmit);
  }

  $(function () {
    if (typeof ctaAdmin === "undefined") {
      return;
    }

    try { initCopyButtons(); } catch (e) {}
    try { initDeleteConfirms(); } catch (e) {}
    try { initSlugGeneration(); } catch (e) {}
    try { initObjectivesRepeater(); } catch (e) {}
    try { initCourseVideoSource(); } catch (e) {}
    try { initModulesPanel(); } catch (e) {}
    try { initQuizPanel(); } catch (e) {}
    try { initStripeTest(); } catch (e) {}
    try { initCertificatePreview(); } catch (e) {}
    try { initEmailSettings(); } catch (e) {}
    try { initUserStats(); } catch (e) {}
    try { initModals(); } catch (e) {}
    try { initBookings(); } catch (e) {}
    try { initAssociateApprovals(); } catch (e) {}
  });
})(jQuery);
