document.addEventListener("DOMContentLoaded", function () {
  var sectionSelect = document.getElementById("Sections");
  var subjectSelect = document.getElementById("subsection");
  var tableRow = document.querySelector(".table-row");
  var prevPageBtn = document.getElementById("prevPageBtn");
  var nextPageBtn = document.getElementById("nextPageBtn");
  var currentPageDisplay = document.getElementById("currentPageDisplay");
  var currentPage = 1;
  var rowsPerPage = 10;

  function populateTableWithAllUsers(users, page) {
    tableRow.innerHTML = "";

    var startIndex = (page - 1) * rowsPerPage;
    var endIndex = startIndex + rowsPerPage;

    var usersToDisplay = users.slice(startIndex, endIndex);

    usersToDisplay.forEach(function (user) {
      var progressPercentage = user.progress_percentage
        ? user.progress_percentage
        : "N/A";

      var tableCell = document.createElement("div");
      tableCell.className = "table-cell";
      tableCell.innerHTML = `
              <div class="card" style="display: table-cell;height:59px;margin-left:40px;background-color: #FBFCFF; border-radius: 8px; padding: 20px; width: 1090px; display: flex; flex-direction: row; align-items: center; justify-content: space-around; gap:8rem;">
                  <div class="card-header" style="width: 25%;">${
                    user.section
                  }</div>
                  <div class="card-header" style="width: 25%;">${
                    user.roll_no
                  }</div>
                  <div class="card-header" style="width: 25%;">${
                    user.username
                  }</div>
                  <div class="card-header" style="width: 25%;">${
                    user.weakest_topic || "N/A"
                  }</div>
                  <div class="card-header" id="crd" style="width: 25%;">${
                    user.weaktopic_progress
                  }</div>
                  <div class="card-header" id="crd" style="width: 25%;">${progressPercentage}</div>
              </div>
          `;

      tableRow.appendChild(tableCell);
    });

    currentPageDisplay.textContent = `Page ${page}`;
  }

  function filterAndPopulateTable() {
    var selectedSection = sectionSelect.value;
    var selectedSubject = subjectSelect.value;

    var filteredUsers = userDataArray.filter(function (user) {
      return (
        (selectedSection === "All Sections" ||
          user.section === selectedSection) &&
        (selectedSubject === "All Subjects" ||
          user.subject.toUpperCase() === selectedSubject.toUpperCase())
      );
    });

    populateTableWithAllUsers(filteredUsers, currentPage);
  }

  subjectSelect.addEventListener("change", filterAndPopulateTable);

  sectionSelect.addEventListener("change", function () {
    filterAndPopulateTable();
  });

  prevPageBtn.addEventListener("click", function () {
    if (currentPage > 1) {
      currentPage--;
      filterAndPopulateTable();
    }
  });

  nextPageBtn.addEventListener("click", function () {
    var totalUsers = userDataArray.filter(function (user) {
      var selectedSection = sectionSelect.value;
      var selectedSubject = subjectSelect.value;

      return (
        (selectedSection === "All Sections" ||
          user.section === selectedSection) &&
        (selectedSubject === "All Subjects" ||
          user.subject.toUpperCase() === selectedSubject.toUpperCase())
      );
    }).length;

    var totalPages = Math.ceil(totalUsers / rowsPerPage);

    if (currentPage < totalPages) {
      currentPage++;
      filterAndPopulateTable();
    }
  });

  filterAndPopulateTable();
});