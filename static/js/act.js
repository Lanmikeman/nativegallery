function createModal(id, type, value, modalid) {
  if (type === "EDIT_COMMENT") {
    var modal =
      `
      <div id="` +
      modalid +
      `" class="modal" style="display: block;">
          <div class="modal-content">
              <span data-close-modal-id="` +
      modalid +
      `" class="close">&times;</span>
              <h3><b>Отредактировать комментарий</b></h3>
              <div style="padding:0 11px 11px">
                  <textarea style="    width: 100%;
    height: 200px;" name="wtext" id="bodypost__commedit` +
      id +
      `">` +
      value +
      `</textarea><br>
                  <div class="cmt-submit">
                      <button type="submit" onclick="editComment('` +
      id +
      `', document.getElementById('bodypost__commedit` +
      id +
      `').value, '` +
      modalid +
      `')" id="sbmt">Отредактировать</button>&ensp;&emsp;Ctrl + Enter
                  </div>
              </div>
          </div>
      </div>`;
  }
  if (type === "DELETE_COMMENT") {
    var modal =
      `
    <div id="` +
      modalid +
      `" class="modal" style="display: block;">
        <div class="modal-content">
            <span data-close-modal-id="` +
      modalid +
      `" class="close">&times;</span>
            <h3><b>Удалить комментарий</b></h3>
            Вы действительно хотите удалить комментарий? Действие необратимо.
            <div style="margin-top: 35px;">
                
                <div class="cmt-submit">
                    <button type="submit" onclick="deleteComment('` +
      id +
      `', '` +
      modalid +
      `')" id="sbmt">Удалить</button>
                    <button data-close-modal-id="` +
      modalid +
      `"  type="submit" id="sbmt">Отмена</button>
                </div>
            </div>
        </div>
    </div>`;
  }
  document.body.innerHTML += modal;
}

document.addEventListener("click", function (event) {
  // Получаем ID модального окна, которое нужно закрыть
  var modalId = event.target.getAttribute("data-close-modal-id");

  // Удаляем модальное окно по его ID
  if (modalId) {
    var modalToClose = document.getElementById(modalId);
    if (modalToClose) {
      modalToClose.remove(); // Удаляем модальное окно из DOM
    }
  }

  // Проверяем, кликнули ли мы на само модальное окно
  var modals = document.querySelectorAll(".modal");
  modals.forEach(function (modal) {
    if (event.target === modal) {
      modal.remove(); // Удаляем модальное окно из DOM
    }
  });
});

const pinComment = (postId) => {
  $(document).ready(function () {
    $.ajax({
      type: "POST",
      url: "/api/photo/comment/" + postId + "/pin",
      success: function (response) {
        var jsonData = JSON.parse(response);

        console.log(response);
        if (jsonData.errorcode == "1") {
          Notify.noty("danger", JSON.stringify(response));
        } else {
          if (jsonData.action == "pin") {
            Notify.noty("success", "Успешно закреплено!");
          } else {
            Notify.noty("success", "Успешно откреплено!");
          }

          const url = window.location.pathname;
          const segments = url.split("/");
          const id = segments[2];
          console.log(segments);
          $.ajax({
            type: "POST",
            url: "/api/photo/getcomments/" + id,
            processData: false,
            async: true,
            success: function (r) {
              $("#posts").html(r);
            },
            error: function (r) {
              console.log(r);
            },
          });
        }
      },
    });
  });
};

const editComment = (postId, body, modalid) => {
  $(document).ready(function () {
    $.ajax({
      type: "POST",
      url: "/api/photo/comment/" + postId + "/edit",
      data: JSON.stringify({ value: body }),
      success: function (response) {
        var jsonData = JSON.parse(response);

        console.log(response);
        if (jsonData.errorcode == "1") {
          Notify.noty("danger", JSON.stringify(response));
        } else {
          document.getElementById(modalid).style.display = "none";

          Notify.noty("success", "Успешно отредактировано!");
          const url = window.location.pathname;
          const segments = url.split("/");
          const id = segments[2];
          $.ajax({
            type: "POST",
            url: "/api/photo/getcomments/" + id,
            processData: false,
            async: true,
            success: function (r) {
              $("#posts").html(r);
            },
            error: function (r) {
              console.log(r);
            },
          });
        }
      },
    });
  });
};

const deleteComment = (postId, modalid) => {
  $(document).ready(function () {
    $.ajax({
      type: "POST",
      url: "/api/photo/comment/" + postId + "/delete",
      success: function (response) {
        var jsonData = JSON.parse(response);

        console.log(response);
        if (jsonData.errorcode == "1") {
          Notify.noty("danger", JSON.stringify(response));
        } else {
          document.getElementById(modalid).style.display = "none";

          Notify.noty("success", "Успешно удалено!");
          const url = window.location.pathname;
          const segments = url.split("/");
          const id = segments[segments.length - 1];
          const commcountElem = document.getElementById("commcount");
          let innerHTML = commcountElem.innerHTML;
          let match = innerHTML.match(/>(\d+)</);
          console.log(match);
          if (match) {
            let count = parseInt(match[1], 10) - 1;
            console.log(count);
            let newHTML = innerHTML.replace(match[1], count);
            commcountElem.innerHTML = newHTML;
          }
          $.ajax({
            type: "POST",
            url: "/api/photo/getcomments/" + id,
            processData: false,
            async: true,
            success: function (r) {
              $("#posts").html(r);
            },
            error: function (r) {
              console.log(r);
            },
          });
        }
      },
    });
  });
};
const loadNews = () => {
  $.ajax({
    type: "GET",
    url: "/api/admin/loadnews",

    success: function (response) {
      $("#news").html(response);
    },
  });
};
const deleteNews = (postId) => {
  $.ajax({
    type: "POST",
    url: "/api/admin/news/" + postId + "/delete",
    success: function (response) {
      var jsonData = JSON.parse(response);

      if (jsonData.errorcode == "1") {
        Notify.noty("danger", JSON.stringify(response));
      } else {
        Notify.noty("success", "Успешно удалено!");

        loadNews();
      }
    },
  });
};
const createNews = () => {
  $.ajax({
    type: "POST",
    url: "/api/admin/news/create",
    data: {
      body: $("#body").val(),
    },
    success: function (response) {
      Notify.noty("success", "OK!");
      $("#body").val("");
      loadNews();
    },
  });
};

function parseNewsBodyAttr(raw) {
  if (raw == null || raw === "") return "";
  try {
    return JSON.parse(raw);
  } catch (e) {
    return String(raw);
  }
}

function showEditNewsModal(postId, body) {
  var modalEl = document.getElementById("editNewsModal");
  if (!modalEl) {
    Notify.noty("danger", "Форма редактирования не найдена. Обновите страницу.");
    return;
  }
  $("#edit-news-id").val(postId);
  $("#edit-body").val(body || "");
  if (typeof bootstrap === "undefined" || !bootstrap.Modal) {
    Notify.noty("danger", "Bootstrap не загружен");
    return;
  }
  bootstrap.Modal.getOrCreateInstance(modalEl).show();
}

const openEditNews = (postId, body) => {
  if (body !== undefined && body !== null) {
    showEditNewsModal(postId, body);
    return;
  }
  $.ajax({
    type: "GET",
    url: "/api/admin/news/" + postId,
    dataType: "json",
    success: function (jsonData) {
      if (parseInt(jsonData.errorcode, 10) !== 0 || !jsonData.news) {
        Notify.noty("danger", jsonData.message || "Не удалось загрузить новость");
        return;
      }
      showEditNewsModal(postId, jsonData.news.body || "");
    },
    error: function () {
      Notify.noty("danger", "Ошибка загрузки новости");
    },
  });
};

window.openEditNews = openEditNews;

$(document).on("click", ".edit-news-btn", function (e) {
  e.preventDefault();
  var postId = $(this).data("id");
  var body = parseNewsBodyAttr($(this).attr("data-news-body"));
  openEditNews(postId, body);
});

const updateNews = () => {
  var postId = $("#edit-news-id").val();
  var body = $("#edit-body").val();
  if (!postId) {
    Notify.noty("danger", "Новость не выбрана");
    return;
  }
  $.ajax({
    type: "POST",
    url: "/api/admin/news/" + postId + "/edit",
    data: { body: body },
    success: function (response) {
      var jsonData = typeof response === "string" ? JSON.parse(response) : response;
      if (parseInt(jsonData.errorcode, 10) !== 0) {
        Notify.noty("danger", jsonData.message || "Не удалось сохранить");
        return;
      }
      Notify.noty("success", "Новость обновлена");
      var modalEl = document.getElementById("editNewsModal");
      var modal = bootstrap.Modal.getInstance(modalEl);
      if (modal) modal.hide();
      loadNews();
    },
    error: function () {
      Notify.noty("danger", "Ошибка сохранения");
    },
  });
};

const createChronology = () => {
  $.ajax({
    type: "POST",
    url: "/api/admin/chronology/create",
    data: {
      city: $("#chrono-city").val(),
      geodb_id: $("#chrono-geodb").val(),
      transit_type: $("#chrono-transit").val(),
      date: $("#chrono-date").val(),
      body: $("#chrono-body").val(),
      main: $("#chrono-main").is(":checked") ? 1 : 0,
    },
    success: function () {
      Notify.noty("success", "Запись добавлена!");
      location.reload();
    },
  });
};

const deleteChronology = (id) => {
  $.ajax({
    type: "POST",
    url: "/api/admin/chronology/" + id + "/delete",
    success: function () {
      Notify.noty("success", "Удалено!");
      $("#chrono" + id).remove();
    },
  });
};

const createSiteLink = () => {
  $.ajax({
    type: "POST",
    url: "/api/admin/links/create",
    data: {
      title: $("#link-title").val(),
      url: $("#link-url").val(),
      sort: $("#link-sort").val(),
    },
    success: function () {
      Notify.noty("success", "Ссылка добавлена!");
      location.reload();
    },
  });
};

const deleteSiteLink = (id) => {
  $.ajax({
    type: "GET",
    url: "/api/admin/links/delete?id=" + id,
    success: function () {
      Notify.noty("success", "Удалено!");
      $("#link" + id).remove();
    },
  });
};

const loadPages = () => {
  $.ajax({
    type: "GET",
    url: "/api/admin/loadpages",
    success: function (response) {
      $("#pages-list").html(response);
    },
  });
};

const showEditPageModal = (pageId, title, body) => {
  var modalEl = document.getElementById("editPageModal");
  if (!modalEl) {
    Notify.noty("danger", "Форма редактирования не найдена. Обновите страницу.");
    return;
  }
  $("#edit-page-id").val(pageId);
  $("#edit-page-url").text("/page/" + pageId);
  $("#edit-page-open").attr("href", "/page/" + pageId);
  $("#edit-page-title").val(title || "");
  $("#edit-page-body").val(body || "");
  if (typeof bootstrap === "undefined" || !bootstrap.Modal) {
    Notify.noty("danger", "Bootstrap не загружен");
    return;
  }
  bootstrap.Modal.getOrCreateInstance(modalEl).show();
};

const openEditPage = (pageId) => {
  $.ajax({
    type: "GET",
    url: "/api/admin/pages/" + pageId,
    dataType: "json",
    success: function (jsonData) {
      if (parseInt(jsonData.errorcode, 10) !== 0 || !jsonData.page) {
        Notify.noty("danger", jsonData.message || "Не удалось загрузить страницу");
        return;
      }
      showEditPageModal(pageId, jsonData.page.title, jsonData.page.body);
    },
    error: function () {
      Notify.noty("danger", "Ошибка загрузки страницы");
    },
  });
};

window.openEditPage = openEditPage;

$(document).on("click", ".edit-page-btn", function (e) {
  e.preventDefault();
  openEditPage($(this).data("id"));
});

const createPage = () => {
  $.ajax({
    type: "POST",
    url: "/api/admin/pages/create",
    data: {
      id: $("#page-create-id").val(),
      title: $("#page-create-title").val(),
      body: $("#page-create-body").val(),
    },
    success: function (response) {
      var jsonData = typeof response === "string" ? JSON.parse(response) : response;
      if (parseInt(jsonData.errorcode, 10) !== 0) {
        Notify.noty("danger", jsonData.message || "Не удалось создать");
        return;
      }
      Notify.noty("success", "Страница создана");
      var createModal = document.getElementById("createPageModal");
      var modal = bootstrap.Modal.getInstance(createModal);
      if (modal) modal.hide();
      $("#page-create-id, #page-create-title, #page-create-body").val("");
      loadPages();
    },
    error: function () {
      Notify.noty("danger", "Ошибка создания");
    },
  });
};

window.createPage = createPage;

const updatePage = () => {
  var pageId = $("#edit-page-id").val();
  if (!pageId) {
    Notify.noty("danger", "Страница не выбрана");
    return;
  }
  $.ajax({
    type: "POST",
    url: "/api/admin/pages/" + pageId + "/edit",
    data: {
      title: $("#edit-page-title").val(),
      body: $("#edit-page-body").val(),
    },
    success: function (response) {
      var jsonData = typeof response === "string" ? JSON.parse(response) : response;
      if (parseInt(jsonData.errorcode, 10) !== 0) {
        Notify.noty("danger", jsonData.message || "Не удалось сохранить");
        return;
      }
      Notify.noty("success", "Страница обновлена");
      var modalEl = document.getElementById("editPageModal");
      var modal = bootstrap.Modal.getInstance(modalEl);
      if (modal) modal.hide();
      loadPages();
    },
    error: function () {
      Notify.noty("danger", "Ошибка сохранения");
    },
  });
};

window.updatePage = updatePage;

const deletePage = (pageId) => {
  if (!confirm("Удалить страницу #" + pageId + "?")) return;
  $.ajax({
    type: "POST",
    url: "/api/admin/pages/" + pageId + "/delete",
    success: function (response) {
      var jsonData = typeof response === "string" ? JSON.parse(response) : response;
      if (parseInt(jsonData.errorcode, 10) !== 0) {
        Notify.noty("danger", jsonData.message || "Не удалось удалить");
        return;
      }
      Notify.noty("success", "Страница удалена");
      loadPages();
    },
    error: function () {
      Notify.noty("danger", "Ошибка удаления");
    },
  });
};

window.deletePage = deletePage;

const handleModelRequest = (modelId, type) => {
  $.ajax({
    type: "POST",
    url: "/api/admin/models/requests/" + modelId + "/" + type,
    data: {
      body: $("#body").val(),
    },
    success: function (response) {
      Notify.noty("success", "OK!");
      const mdlNumElement = document.getElementById("mdlnum");

      if (mdlNumElement) {
        let currentNumber = parseInt(mdlNumElement.textContent, 10);
        if (!isNaN(currentNumber)) {
          currentNumber--;
          mdlNumElement.textContent = currentNumber.toString();
          if (currentNumber === 0) {
            mdlNumElement.remove();
          }
        }
      }
      $("#mdl" + modelId).remove();
    },
  });
};
