/**
 * Docker plugin — UI integration
 *
 * Sections:
 *   1. Polling Engine
 *   2. Status Tab
 *   3. Images Tab
 *   4. Containers Tab
 *   5. Compose Tab
 *   6. Volumes Tab
 */

$(function () {
  /* ─────────────────────────────────────────────────────────────────────
   * Section 1 — Polling Engine
   * ──────────────────────────────────────────────────────────────────── */

  var dockerPollingIntervals = {};

  /**
   * Start polling docker_job_status.php for a running background job.
   *
   * @param {string}   jobId        Server-side job identifier
   * @param {string}   logSelector  CSS selector for the <pre> log element
   * @param {Function} onComplete   Called with parsed JSON when job.done === true
   */
  function dockerStartPolling(jobId, logSelector, onComplete) {
    var csrfToken = $("meta[name=csrf_token]").attr("content");

    dockerPollingIntervals[jobId] = setInterval(function () {
      $.post(
        "plugins/Docker/ajax/docker_job_status.php",
        { jobId: jobId, csrf_token: csrfToken },
        function (data) {
          var json;
          try {
            json = JSON.parse(data);
          } catch (e) {
            return;
          }

          var $log = $(logSelector);
          if ($log.length) {
            $log.text(json.output || "");
            $log.scrollTop($log[0].scrollHeight);
          }

          if (json.done === true) {
            clearInterval(dockerPollingIntervals[jobId]);
            delete dockerPollingIntervals[jobId];

            // Ask the server to clean up the job file
            $.post("plugins/Docker/ajax/docker_job_status.php", {
              jobId: jobId,
              cleanup: "true",
              csrf_token: csrfToken,
            });

            if (typeof onComplete === "function") {
              onComplete(json);
            }
          }
        },
      );
    }, 1500);
  }

  /* ─────────────────────────────────────────────────────────────────────
   * Section 2 — Status Tab
   * ──────────────────────────────────────────────────────────────────── */

  var dockerAutoRefreshInterval = null;

  // Daemon start
  $(document).on("click", "#js-docker-daemon-start", function () {
    var $btn = $(this);
    var csrfToken = $("meta[name=csrf_token]").attr("content");

    $btn.prop("disabled", true).text("Starting…");

    $.post(
      "plugins/Docker/ajax/docker_action.php",
      { action: "daemon_start", csrf_token: csrfToken },
      function (data) {
        var json;
        try {
          json = JSON.parse(data);
        } catch (e) {
          json = {};
        }

        if (json.error) {
          $btn.prop("disabled", false).text("Start Docker");
        } else {
          location.reload();
        }
      },
    ).fail(function () {
      $btn.prop("disabled", false).text("Start Docker");
    });
  });

  // Container start / stop (Status tab rows)
  $(document).on(
    "click",
    ".js-container-start, .js-container-stop",
    function () {
      var $btn = $(this);
      var id = $btn.data("id");
      var isStart = $btn.hasClass("js-container-start");
      var action = isStart ? "container_start" : "container_stop";
      var csrfToken = $("meta[name=csrf_token]").attr("content");

      $btn.prop("disabled", true);

      $.post(
        "plugins/Docker/ajax/docker_action.php",
        { action: action, id: id, csrf_token: csrfToken },
        function (data) {
          var json;
          try {
            json = JSON.parse(data);
          } catch (e) {
            json = {};
          }

          if (!json.error) {
            var $row = $("#container-row-" + id);

            if (isStart) {
              $row
                .find(".badge")
                .removeClass("bg-secondary")
                .addClass("bg-success")
                .text("running");
              $btn
                .removeClass("js-container-start btn-success")
                .addClass("js-container-stop btn-warning")
                .text("Stop");
            } else {
              $row
                .find(".badge")
                .removeClass("bg-success")
                .addClass("bg-secondary")
                .text("exited");
              $btn
                .removeClass("js-container-stop btn-warning")
                .addClass("js-container-start btn-success")
                .text("Start");
            }
          }

          $btn.prop("disabled", false);
        },
      ).fail(function () {
        $btn.prop("disabled", false);
      });
    },
  );

  // Auto-refresh toggle
  $("#docker-auto-refresh").on("change", function () {
    var csrfToken = $("meta[name=csrf_token]").attr("content");

    if ($(this).is(":checked")) {
      dockerAutoRefreshInterval = setInterval(function () {
        $.post(
          "plugins/Docker/ajax/docker_action.php",
          { action: "status_summary", csrf_token: csrfToken },
          function () {
            location.reload();
          },
        );
      }, 10000);
    } else {
      clearInterval(dockerAutoRefreshInterval);
      dockerAutoRefreshInterval = null;
    }
  });

  // Manual refresh button
  $(document).on("click", "#docker-refresh", function () {
    location.reload();
  });

  /* ─────────────────────────────────────────────────────────────────────
   * Section 3 — Images Tab
   * ──────────────────────────────────────────────────────────────────── */

  var dockerImageDeleteId = null;

  // Open delete-image confirmation modal
  $(document).on("click", ".js-image-delete", function () {
    dockerImageDeleteId = $(this).data("id");
    var name = $(this).data("name") || dockerImageDeleteId;
    $("#docker-image-delete-name").text(name);
    new bootstrap.Modal(
      document.getElementById("docker-image-delete-modal"),
    ).show();
  });

  // Confirm image delete
  $(document).on("click", "#docker-image-delete-confirm", function () {
    var $btn = $(this);
    var csrfToken = $("meta[name=csrf_token]").attr("content");

    $btn.prop("disabled", true);

    var modal = bootstrap.Modal.getInstance(
      document.getElementById("docker-image-delete-modal"),
    );
    if (modal) modal.hide();

    $.post(
      "plugins/Docker/ajax/docker_action.php",
      {
        action: "image_delete",
        id: dockerImageDeleteId,
        csrf_token: csrfToken,
      },
      function (data) {
        var json;
        try {
          json = JSON.parse(data);
        } catch (e) {
          json = {};
        }

        if (!json.error) {
          $("#image-row-" + dockerImageDeleteId).fadeOut(400, function () {
            $(this).remove();
          });
        }
        $btn.prop("disabled", false);
      },
    ).fail(function () {
      $btn.prop("disabled", false);
    });
  });

  // Docker Hub search state
  var dockerHubCurrentPage = 1;
  var dockerHubCurrentQuery = "";

  function dockerHubSearch(query, page) {
    var csrfToken = $("meta[name=csrf_token]").attr("content");
    var $searchBtn = $("#docker-hub-search-btn");

    dockerHubCurrentQuery = query;
    dockerHubCurrentPage = page;

    $searchBtn
      .prop("disabled", true)
      .html(
        '<span class="spinner-border spinner-border-sm me-1"></span>Searching…',
      );

    $.post(
      "plugins/Docker/ajax/docker_hub_search.php",
      {
        query: query,
        page: page,
        csrf_token: csrfToken,
      },
      function (data) {
        var json;
        try {
          json = JSON.parse(data);
        } catch (e) {
          json = { error: "Parse error" };
        }

        $searchBtn
          .prop("disabled", false)
          .html('<i class="fas fa-search me-1"></i>Search');

        if (json.error) {
          $("#docker-hub-results-body").html(
            '<tr><td colspan="5" class="text-danger">' +
              $("<span>").text(json.error).html() +
              "</td></tr>",
          );
          $("#docker-hub-results").removeClass("d-none");
          return;
        }

        var results = json.results || [];
        var $tbody = $("#docker-hub-results-body").empty();

        if (results.length === 0) {
          $tbody.html(
            '<tr><td colspan="5" class="text-muted text-center">No results found.</td></tr>',
          );
        } else {
          $.each(results, function (i, item) {
            var officialBadge = item.is_official
              ? '<span class="badge bg-info text-dark">Official</span>'
              : "";
            var imageName = $("<span>")
              .text(item.name || "")
              .html();
            var description = $("<span>")
              .text((item.description || "").substring(0, 80))
              .html();
            var stars = parseInt(item.star_count || item.stars || 0, 10);

            $tbody.append(
              "<tr>" +
                "<td>" +
                imageName +
                "</td>" +
                '<td class="text-muted small">' +
                description +
                "</td>" +
                "<td>" +
                stars +
                "</td>" +
                "<td>" +
                officialBadge +
                "</td>" +
                "<td>" +
                '<button class="btn btn-sm btn-outline-primary js-hub-pull" data-image="' +
                imageName +
                '">' +
                '<i class="fas fa-download me-1"></i>Pull' +
                "</button>" +
                "</td>" +
                "</tr>",
            );
          });
        }

        // Pagination controls
        var pageInfo = "Page " + page;
        if (json.num_pages) {
          pageInfo += " of " + json.num_pages;
        }
        $("#docker-hub-page-info").text(pageInfo);
        $("#docker-hub-prev").prop("disabled", page <= 1);
        $("#docker-hub-next").prop(
          "disabled",
          results.length === 0 || (json.num_pages && page >= json.num_pages),
        );

        $("#docker-hub-results").removeClass("d-none");
      },
    ).fail(function () {
      $searchBtn
        .prop("disabled", false)
        .html('<i class="fas fa-search me-1"></i>Search');
    });
  }

  $("#docker-hub-search-btn").on("click", function () {
    var q = $("#docker-hub-search-input").val().trim();
    if (q) dockerHubSearch(q, 1);
  });

  $("#docker-hub-search-input").on("keypress", function (e) {
    if (e.which === 13) {
      var q = $(this).val().trim();
      if (q) dockerHubSearch(q, 1);
    }
  });

  $(document).on("click", "#docker-hub-prev", function () {
    if (dockerHubCurrentPage > 1) {
      dockerHubSearch(dockerHubCurrentQuery, dockerHubCurrentPage - 1);
    }
  });

  $(document).on("click", "#docker-hub-next", function () {
    dockerHubSearch(dockerHubCurrentQuery, dockerHubCurrentPage + 1);
  });

  // Image pull
  $(document).on("click", ".js-hub-pull", function () {
    var $btn = $(this);
    var image = $btn.data("image");
    var csrfToken = $("meta[name=csrf_token]").attr("content");

    $btn
      .prop("disabled", true)
      .html(
        '<span class="spinner-border spinner-border-sm me-1"></span>Pulling…',
      );

    $("#docker-pull-image-name").text(image);
    $("#docker-pull-status")
      .removeClass()
      .addClass("badge bg-secondary")
      .text("Pulling…");
    $("#docker-pull-log").text("");
    $("#docker-pull-log-panel").removeClass("d-none");

    $.post(
      "plugins/Docker/ajax/docker_image_pull.php",
      { image: image, csrf_token: csrfToken },
      function (data) {
        var json;
        try {
          json = JSON.parse(data);
        } catch (e) {
          json = {};
        }

        if (!json.jobId) {
          $btn
            .prop("disabled", false)
            .html('<i class="fas fa-download me-1"></i>Pull');
          return;
        }

        dockerStartPolling(json.jobId, "#docker-pull-log", function (result) {
          $("#docker-pull-status")
            .removeClass()
            .addClass("badge bg-success")
            .text("Done ✓");
          $btn.prop("disabled", false).text("Pulled ✓");
        });
      },
    ).fail(function () {
      $btn
        .prop("disabled", false)
        .html('<i class="fas fa-download me-1"></i>Pull');
    });
  });

  /* ─────────────────────────────────────────────────────────────────────
   * Section 4 — Containers Tab
   * ──────────────────────────────────────────────────────────────────── */

  var dockerContainerDeleteId = null;

  // Open create-container modal
  $(document).on("click", "#docker-create-container-btn", function () {
    new bootstrap.Modal(
      document.getElementById("docker-create-container-modal"),
    ).show();
  });

  // Dynamic row removal (ports, volumes, env, labels)
  $(document).on("click", ".cc-remove-row", function () {
    $(this)
      .closest(".cc-port-row, .cc-volume-row, .cc-env-row, .cc-label-row")
      .remove();
  });

  // Add port row
  $("#cc-add-port").on("click", function () {
    $("#cc-ports-container").append(
      '<div class="d-flex align-items-center gap-2 mb-2 cc-port-row">' +
        '<input type="text" class="form-control form-control-sm" name="host_port" placeholder="Host port">' +
        "<span>:</span>" +
        '<input type="text" class="form-control form-control-sm" name="container_port" placeholder="Container port">' +
        '<select class="form-select form-select-sm" name="proto" style="max-width:80px;">' +
        '<option value="tcp">tcp</option>' +
        '<option value="udp">udp</option>' +
        "</select>" +
        '<button type="button" class="btn btn-sm btn-outline-danger cc-remove-row"><i class="fas fa-minus"></i></button>' +
        "</div>",
    );
  });

  // Add volume row
  $("#cc-add-volume").on("click", function () {
    $("#cc-volumes-container").append(
      '<div class="d-flex align-items-center gap-2 mb-2 cc-volume-row">' +
        '<input type="text" class="form-control form-control-sm" name="host_path" placeholder="Host path">' +
        "<span>:</span>" +
        '<input type="text" class="form-control form-control-sm" name="container_path" placeholder="Container path">' +
        '<button type="button" class="btn btn-sm btn-outline-danger cc-remove-row"><i class="fas fa-minus"></i></button>' +
        "</div>",
    );
  });

  // Add env row
  $("#cc-add-env").on("click", function () {
    $("#cc-env-container").append(
      '<div class="d-flex align-items-center gap-2 mb-2 cc-env-row">' +
        '<input type="text" class="form-control form-control-sm" name="env_key" placeholder="KEY">' +
        "<span>=</span>" +
        '<input type="text" class="form-control form-control-sm" name="env_value" placeholder="VALUE">' +
        '<button type="button" class="btn btn-sm btn-outline-danger cc-remove-row"><i class="fas fa-minus"></i></button>' +
        "</div>",
    );
  });

  // Add label row
  $("#cc-add-label").on("click", function () {
    $("#cc-labels-container").append(
      '<div class="d-flex align-items-center gap-2 mb-2 cc-label-row">' +
        '<input type="text" class="form-control form-control-sm" name="label_key" placeholder="key">' +
        "<span>=</span>" +
        '<input type="text" class="form-control form-control-sm" name="label_value" placeholder="value">' +
        '<button type="button" class="btn btn-sm btn-outline-danger cc-remove-row"><i class="fas fa-minus"></i></button>' +
        "</div>",
    );
  });

  // Create container — confirm
  $(document).on("click", "#docker-create-container-confirm", function () {
    var $btn = $(this);
    var $error = $("#cc-error");
    var image = $("#cc-image").val().trim();

    $error.addClass("d-none").text("");

    if (!image) {
      $error.removeClass("d-none").text("Image name is required.");
      return;
    }

    $("#cc-spinner").removeClass("d-none");
    $btn.prop("disabled", true);

    var csrfToken = $("meta[name=csrf_token]").attr("content");

    // Collect port mappings as "host:container/proto" strings
    var ports = [];
    $("#cc-ports-container .cc-port-row").each(function () {
      var hp = $(this).find('[name="host_port"]').val().trim();
      var cp = $(this).find('[name="container_port"]').val().trim();
      var proto = $(this).find('[name="proto"]').val();
      if (hp && cp) {
        ports.push(hp + ":" + cp + "/" + proto);
      }
    });

    // Collect volume mounts as "host:container" strings
    var volumes = [];
    $("#cc-volumes-container .cc-volume-row").each(function () {
      var hp = $(this).find('[name="host_path"]').val().trim();
      var cp = $(this).find('[name="container_path"]').val().trim();
      if (hp && cp) {
        volumes.push(hp + ":" + cp);
      }
    });

    // Collect environment variables as "KEY=VALUE" strings
    var env = [];
    $("#cc-env-container .cc-env-row").each(function () {
      var k = $(this).find('[name="env_key"]').val().trim();
      var v = $(this).find('[name="env_value"]').val().trim();
      if (k) {
        env.push(k + "=" + v);
      }
    });

    // Collect labels as "key=value" strings
    var labels = [];
    $("#cc-labels-container .cc-label-row").each(function () {
      var k = $(this).find('[name="label_key"]').val().trim();
      var v = $(this).find('[name="label_value"]').val().trim();
      if (k) {
        labels.push(k + "=" + v);
      }
    });

    $.post(
      "plugins/Docker/ajax/docker_create_container.php",
      {
        image: image,
        name: $("#cc-name").val().trim(),
        restart: $("#cc-restart").val(),
        network: $("#cc-network").val(),
        entrypoint: $("#cc-entrypoint").val().trim(),
        cmd: $("#cc-cmd").val().trim(),
        cpu_limit: $("#cc-cpu").val().trim(),
        memory_limit: $("#cc-memory").val().trim(),
        ports: JSON.stringify(ports),
        volumes: JSON.stringify(volumes),
        env: JSON.stringify(env),
        labels: JSON.stringify(labels),
        csrf_token: csrfToken,
      },
      function (data) {
        var json;
        try {
          json = JSON.parse(data);
        } catch (e) {
          json = { error: "Unexpected response" };
        }

        if (json.error) {
          $("#cc-spinner").addClass("d-none");
          $btn.prop("disabled", false);
          $error.removeClass("d-none").text(json.error);
        } else {
          var modal = bootstrap.Modal.getInstance(
            document.getElementById("docker-create-container-modal"),
          );
          if (modal) modal.hide();
          location.reload();
        }
      },
    ).fail(function () {
      $("#cc-spinner").addClass("d-none");
      $btn.prop("disabled", false);
      $error.removeClass("d-none").text("Request failed. Please try again.");
    });
  });

  // Container start / stop (Containers tab — all containers list)
  $(document).on(
    "click",
    ".js-all-container-start, .js-all-container-stop",
    function () {
      var $btn = $(this);
      var id = $btn.data("id");
      var isStart = $btn.hasClass("js-all-container-start");
      var action = isStart ? "container_start" : "container_stop";
      var csrfToken = $("meta[name=csrf_token]").attr("content");

      $btn.prop("disabled", true);

      $.post(
        "plugins/Docker/ajax/docker_action.php",
        { action: action, id: id, csrf_token: csrfToken },
        function (data) {
          var json;
          try {
            json = JSON.parse(data);
          } catch (e) {
            json = {};
          }

          if (!json.error) {
            var $row = $("#all-container-row-" + id);

            if (isStart) {
              $row
                .find(".badge")
                .removeClass("bg-secondary")
                .addClass("bg-success");
              $btn
                .removeClass("js-all-container-start btn-success")
                .addClass("js-all-container-stop btn-warning")
                .text("Stop");
            } else {
              $row
                .find(".badge")
                .removeClass("bg-success")
                .addClass("bg-secondary");
              $btn
                .removeClass("js-all-container-stop btn-warning")
                .addClass("js-all-container-start btn-success")
                .text("Start");
            }
          }

          $btn.prop("disabled", false);
        },
      ).fail(function () {
        $btn.prop("disabled", false);
      });
    },
  );

  // Container inspect — load details when button clicked
  // (Modal opens automatically via data-bs-toggle in the template)
  $(document).on("click", ".js-container-inspect", function () {
    var id = $(this).data("id");
    var csrfToken = $("meta[name=csrf_token]").attr("content");

    $("#docker-inspect-output").text("Loading…");

    $.post(
      "plugins/Docker/ajax/docker_action.php",
      { action: "container_inspect", id: id, csrf_token: csrfToken },
      function (data) {
        var json;
        try {
          json = JSON.parse(data);
        } catch (e) {
          json = {};
        }

        var output = json.output || data;
        try {
          output = JSON.stringify(JSON.parse(output), null, 2);
        } catch (e) {
          /* keep raw */
        }

        $("#docker-inspect-output").text(output);
      },
    );
  });

  // Open delete-container confirmation modal
  $(document).on("click", ".js-all-container-delete", function () {
    dockerContainerDeleteId = $(this).data("id");
    var name = $(this).data("name") || dockerContainerDeleteId;
    $("#docker-container-delete-name").text(name);
    new bootstrap.Modal(
      document.getElementById("docker-container-delete-modal"),
    ).show();
  });

  // Confirm container delete
  $(document).on("click", "#docker-container-delete-confirm", function () {
    var csrfToken = $("meta[name=csrf_token]").attr("content");

    var modal = bootstrap.Modal.getInstance(
      document.getElementById("docker-container-delete-modal"),
    );
    if (modal) modal.hide();

    $.post(
      "plugins/Docker/ajax/docker_action.php",
      {
        action: "container_delete",
        id: dockerContainerDeleteId,
        csrf_token: csrfToken,
      },
      function (data) {
        var json;
        try {
          json = JSON.parse(data);
        } catch (e) {
          json = {};
        }

        if (!json.error) {
          $("#all-container-row-" + dockerContainerDeleteId).fadeOut(
            400,
            function () {
              $(this).remove();
            },
          );
        }
      },
    );
  });

  /* ─────────────────────────────────────────────────────────────────────
   * Section 5 — Compose Tab
   * ──────────────────────────────────────────────────────────────────── */

  var dockerComposeDeleteProject = null;

  // Compose up / down / restart
  $(document).on(
    "click",
    ".js-compose-up, .js-compose-down, .js-compose-restart",
    function () {
      var $btn = $(this);
      var project = $btn.data("project");
      var action;

      if ($btn.hasClass("js-compose-up")) {
        action = "up";
      } else if ($btn.hasClass("js-compose-down")) {
        action = "down";
      } else {
        action = "restart";
      }

      var csrfToken = $("meta[name=csrf_token]").attr("content");
      var $logPanel = $("#compose-log-" + project);

      $btn.prop("disabled", true);
      $logPanel.find(".compose-log-output").text("");
      $logPanel.removeClass("d-none");

      $.post(
        "plugins/Docker/ajax/docker_compose_action.php",
        {
          project: project,
          action: action,
          csrf_token: csrfToken,
        },
        function (data) {
          var json;
          try {
            json = JSON.parse(data);
          } catch (e) {
            json = {};
          }

          if (!json.jobId) {
            $btn.prop("disabled", false);
            return;
          }

          dockerStartPolling(
            json.jobId,
            "#compose-log-" + project + " .compose-log-output",
            function () {
              $btn.prop("disabled", false);

              var $badge = $("#compose-status-" + project);
              if (action === "up" || action === "restart") {
                $badge
                  .removeClass()
                  .addClass("badge bg-success")
                  .text("running");
              } else {
                $badge
                  .removeClass()
                  .addClass("badge bg-danger")
                  .text("stopped");
              }
            },
          );
        },
      ).fail(function () {
        $btn.prop("disabled", false);
      });
    },
  );

  // Compose edit — show inline YAML editor
  $(document).on("click", ".js-compose-edit", function () {
    var project = $(this).data("project");
    $("#compose-editor-" + project).removeClass("d-none");
    $("#compose-log-" + project).addClass("d-none");
  });

  // Compose editor cancel
  $(document).on("click", ".js-compose-editor-cancel", function () {
    var project = $(this).data("project");
    $("#compose-editor-" + project).addClass("d-none");
  });

  // New compose project — create via hidden form POST
  $(document).on("click", "#docker-compose-new-confirm", function () {
    var name = $("#compose-new-name").val().trim();
    var yaml = $("#compose-new-yaml").val().trim();
    var $error = $("#compose-new-error");

    $error.addClass("d-none").text("");

    if (!name || !yaml) {
      $error
        .removeClass("d-none")
        .text("Project name and YAML content are required.");
      return;
    }

    var csrfToken = $("meta[name=csrf_token]").attr("content");

    var $form = $('<form method="post"></form>').attr(
      "action",
      "?page=plugin__Docker",
    );
    $form.append($('<input type="hidden" name="csrf_token">').val(csrfToken));
    $form.append($('<input type="hidden" name="compose_project">').val(name));
    $form.append($('<input type="hidden" name="compose_yaml">').val(yaml));
    $form.append($('<input type="hidden" name="saveCompose" value="1">'));
    $("body").append($form);
    $form.submit();
  });

  // Open delete-compose confirmation modal
  $(document).on("click", ".js-compose-delete", function () {
    dockerComposeDeleteProject = $(this).data("project");
    $("#compose-delete-project-name").val(dockerComposeDeleteProject);
    new bootstrap.Modal(
      document.getElementById("docker-compose-delete-modal"),
    ).show();
  });

  // Confirm compose delete
  $(document).on("click", "#docker-compose-delete-confirm", function () {
    var csrfToken = $("meta[name=csrf_token]").attr("content");

    var modal = bootstrap.Modal.getInstance(
      document.getElementById("docker-compose-delete-modal"),
    );
    if (modal) modal.hide();

    $.post(
      "plugins/Docker/ajax/docker_action.php",
      {
        action: "compose_delete",
        project: dockerComposeDeleteProject,
        csrf_token: csrfToken,
      },
      function (data) {
        var json;
        try {
          json = JSON.parse(data);
        } catch (e) {
          json = {};
        }

        if (!json.error) {
          location.reload();
        }
      },
    );
  });

  // Compose file upload
  $("#docker-compose-file-input").on("change", function () {
    var file = this.files[0];
    if (!file) return;

    var projectName = prompt("Enter a project name for this Compose file:");
    if (!projectName) {
      $(this).val("");
      return;
    }

    if (!/^[a-zA-Z0-9_-]+$/.test(projectName)) {
      alert(
        "Project name may only contain letters, numbers, hyphens and underscores.",
      );
      $(this).val("");
      return;
    }

    var csrfToken = $("meta[name=csrf_token]").attr("content");
    var reader = new FileReader();

    reader.onload = function (e) {
      var yaml = e.target.result;

      var $form = $('<form method="post"></form>').attr(
        "action",
        "?page=plugin__Docker",
      );
      $form.append($('<input type="hidden" name="csrf_token">').val(csrfToken));
      $form.append(
        $('<input type="hidden" name="compose_project">').val(projectName),
      );
      $form.append($('<input type="hidden" name="compose_yaml">').val(yaml));
      $form.append($('<input type="hidden" name="saveCompose" value="1">'));
      $("body").append($form);
      $form.submit();
    };

    reader.readAsText(file);
    $(this).val("");
  });

  /* ─────────────────────────────────────────────────────────────────────
   * Section 6 — Volumes Tab
   * ──────────────────────────────────────────────────────────────────── */

  var dockerVolumeDeleteName = null;
  var dockerCurrentInspectVolume = null;

  /**
   * Browse a Docker volume directory via docker_volume_browse.php.
   *
   * @param {string} volumeName  Volume name
   * @param {string} subpath     Subdirectory path within the volume ('' for root)
   */
  function dockerVolumeBrowse(volumeName, subpath) {
    var csrfToken = $("meta[name=csrf_token]").attr("content");

    $.post(
      "plugins/Docker/ajax/docker_volume_browse.php",
      {
        volume_name: volumeName,
        subpath: subpath,
        csrf_token: csrfToken,
      },
      function (data) {
        var json;
        try {
          json = JSON.parse(data);
        } catch (e) {
          $("#volume-files-body").html(
            '<tr><td colspan="4" class="text-danger">Failed to parse response.</td></tr>',
          );
          return;
        }

        if (json.error) {
          $("#volume-files-body").html(
            '<tr><td colspan="4" class="text-danger">' +
              $("<span>").text(json.error).html() +
              "</td></tr>",
          );
          return;
        }

        // Breadcrumb — built from api-provided json.breadcrumb
        var $breadcrumb = $("#volume-file-breadcrumb").empty();
        var crumbs = json.breadcrumb || [];

        $.each(crumbs, function (i, crumb) {
          var isLast = i === crumbs.length - 1;
          var label = $("<span>")
            .text(crumb.name || "/")
            .html();
          // Relative subpath: index 0 is volume root (''), deeper entries join names 1..i
          var relPath = crumbs
            .slice(1, i + 1)
            .map(function (c) {
              return c.name;
            })
            .join("/");
          if (isLast) {
            $breadcrumb.append(
              '<li class="breadcrumb-item active">' + label + "</li>",
            );
          } else {
            $breadcrumb.append(
              '<li class="breadcrumb-item">' +
                '<a class="js-volume-breadcrumb" href="#"' +
                ' data-volume="' +
                $("<span>").text(volumeName).html() +
                '"' +
                ' data-path="' +
                $("<span>").text(relPath).html() +
                '">' +
                label +
                "</a></li>",
            );
          }
        });

        // File listing — read from json.entries
        var files = json.entries || [];
        var $tbody = $("#volume-files-body").empty();

        if (files.length === 0) {
          $tbody.html(
            '<tr><td colspan="4" class="text-muted text-center">Empty directory.</td></tr>',
          );
        } else {
          $.each(files, function (i, f) {
            var name = $("<span>")
              .text(f.name || "")
              .html();
            var type = f.type || "file";
            var size = f.size || "—";
            var modified = $("<span>")
              .text(f.modified || "")
              .html();
            var nameCell;

            if (type === "dir" || type === "directory") {
              var childPath = subpath ? subpath + "/" + f.name : f.name;
              nameCell =
                '<i class="fas fa-folder text-warning me-1"></i>' +
                '<a class="js-volume-browse-dir" href="#"' +
                ' data-volume="' +
                $("<span>").text(volumeName).html() +
                '"' +
                ' data-subpath="' +
                $("<span>").text(childPath).html() +
                '">' +
                name +
                "</a>";
            } else {
              nameCell = '<i class="fas fa-file text-muted me-1"></i>' + name;
            }

            $tbody.append(
              "<tr>" +
                "<td>" +
                nameCell +
                "</td>" +
                "<td>" +
                $("<span>").text(type).html() +
                "</td>" +
                "<td>" +
                $("<span>").text(size).html() +
                "</td>" +
                "<td>" +
                modified +
                "</td>" +
                "</tr>",
            );
          });
        }

        // Details pane — mountpoint
        var mountpoint = json.current_path || "—";
        $("#volume-inspect-details").html(
          '<dt class="col-sm-4">Mountpoint</dt>' +
            '<dd class="col-sm-8"><code>' +
            $("<span>").text(mountpoint).html() +
            "</code></dd>",
        );
      },
    ).fail(function () {
      $("#volume-files-body").html(
        '<tr><td colspan="4" class="text-danger">Request failed.</td></tr>',
      );
    });
  }

  // Create volume — confirm
  $(document).on("click", "#docker-volume-create-confirm", function () {
    var name = $("#volume-create-name").val().trim();
    if (!name) {
      alert("Volume name is required.");
      return;
    }

    var driver = $("#volume-create-driver").val();
    var csrfToken = $("meta[name=csrf_token]").attr("content");

    var labels = [];
    $("#volume-labels-container .volume-label-row").each(function () {
      var inputs = $(this).find('input[type="text"]');
      var k = inputs.first().val().trim();
      var v = inputs.last().val().trim();
      if (k) {
        labels.push(k + "=" + v);
      }
    });

    $.post(
      "plugins/Docker/ajax/docker_action.php",
      {
        action: "volume_create",
        name: name,
        driver: driver,
        labels: JSON.stringify(labels),
        csrf_token: csrfToken,
      },
      function (data) {
        var json;
        try {
          json = JSON.parse(data);
        } catch (e) {
          json = {};
        }

        var modal = bootstrap.Modal.getInstance(
          document.getElementById("docker-create-volume-modal"),
        );
        if (modal) modal.hide();

        if (json.error) {
          alert("Error: " + json.error);
        } else {
          location.reload();
        }
      },
    ).fail(function () {
      alert("Request failed. Please try again.");
    });
  });

  // Add volume label row
  $(document).on("click", "#volume-add-label", function () {
    $("#volume-labels-container").append(
      '<div class="volume-label-row input-group input-group-sm mb-1">' +
        '<input type="text" class="form-control" placeholder="Key">' +
        '<input type="text" class="form-control" placeholder="Value">' +
        '<button type="button" class="btn btn-outline-danger js-volume-label-remove">' +
        '<i class="fas fa-times fa-fw"></i>' +
        "</button>" +
        "</div>",
    );
  });

  // Remove volume label row
  $(document).on("click", ".js-volume-label-remove", function () {
    $(this).closest(".volume-label-row").remove();
  });

  // Open delete-volume confirmation modal
  $(document).on("click", ".js-volume-delete", function () {
    dockerVolumeDeleteName = $(this).data("name");
    $("#docker-volume-delete-confirm").attr(
      "data-name",
      dockerVolumeDeleteName,
    );
    new bootstrap.Modal(
      document.getElementById("docker-volume-delete-modal"),
    ).show();
  });

  // Confirm volume delete
  $(document).on("click", "#docker-volume-delete-confirm", function () {
    var csrfToken = $("meta[name=csrf_token]").attr("content");

    var modal = bootstrap.Modal.getInstance(
      document.getElementById("docker-volume-delete-modal"),
    );
    if (modal) modal.hide();

    $.post(
      "plugins/Docker/ajax/docker_action.php",
      {
        action: "volume_delete",
        name: dockerVolumeDeleteName,
        csrf_token: csrfToken,
      },
      function (data) {
        var json;
        try {
          json = JSON.parse(data);
        } catch (e) {
          json = {};
        }

        if (json.error) {
          alert("Error: " + json.error);
        } else {
          $("#volume-row-" + dockerVolumeDeleteName).fadeOut(400, function () {
            $(this).remove();
          });
        }
      },
    ).fail(function () {
      alert("Request failed. Please try again.");
    });
  });

  // Volume inspect — modal opens via data-bs-toggle in the template
  $(document).on("click", ".js-volume-inspect", function () {
    var name = $(this).data("name");
    dockerCurrentInspectVolume = name;

    $("#volume-inspect-name").text(name);
    $("#volume-inspect-details").empty();
    $("#volume-files-body").html(
      '<tr><td colspan="4" class="text-muted">Loading…</td></tr>',
    );

    dockerVolumeBrowse(name, "");
  });

  // Files tab activation — refresh file listing when tab is shown
  $(document).on("shown.bs.tab", '[data-bs-target="#vi-files"]', function () {
    if (dockerCurrentInspectVolume) {
      dockerVolumeBrowse(dockerCurrentInspectVolume, "");
    }
  });

  // Directory navigation (file listing links)
  $(document).on("click", ".js-volume-browse-dir", function (e) {
    e.preventDefault();
    var vol = $(this).data("volume");
    var subpath = $(this).data("subpath");
    dockerVolumeBrowse(vol, subpath);
  });

  // Breadcrumb navigation
  $(document).on("click", ".js-volume-breadcrumb", function (e) {
    e.preventDefault();
    var vol = $(this).data("volume");
    var path = $(this).data("path");
    dockerVolumeBrowse(vol, path);
  });
});
