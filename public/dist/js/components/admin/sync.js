$(document).ready(function () {
    // gettin data count and append it
    const icon = $("#PUniteContainer").find("i");
    let infoTables = [];

    setInterval(async function () {
        var now = new Date();
        var hours = now.getHours();
        var minutes = now.getMinutes();

        if (hours === 1 && minutes === 0) {
            console.log('starting');

            await processTables(infoTables);

            console.log('done');
        }
    }, 60000);

    function countData() {
        const container = $("body #syncContainer");
        container.append(`<div class="loader-container" style="padding:20px;display: flex;justify-content: center;width: 100%;align-items: center;"><div class="d-flex justify-content-center">
                <div class="spinner-border" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                </div></div>`);
        axios
            .get("/syncronize/count")
            .then(function (response) {
                infoTables = response.data;
                container.empty();
                console.log(infoTables);

                if (container.children().length <= 0) {
                    Object.keys(response.data).forEach(key => {
                        const item = response.data[key];
                        const count = item.count;
                        const lastId = item.lastId;
                        const tableName = item.tableName;

                        const card = `
                  <div class="col-sm-6 col-lg-3">
                      <div class="card card-sm">
                          <div class="card-body">
                              <div class="row align-items-center">
                                  <div class="col-auto">
                                      <button id="${tableName}Container" class="btn btn-primary sync-button" style="padding: 5px 8px !important;" data-entity-class="${tableName}">
                                          <i class="fa fa-plus-circle"></i>
                                      </button>
                                  </div>
                                  <div class="col">
                                      <div class="font-weight-medium">
                                          <div class="row">
                                              <div class="col-9">
                                                  <span id="${tableName}Container" class="count" data-count="${count}" data-last="${lastId}" data-tablename="${tableName}" data-entity="${tableName}">
                                                      <i class="fa fa-info-circle" style="color: #182433;"></i>
                                                  </span>
                                                  ${tableName}
                                              </div>
                                              <div class="col-3">
                                                  <span class="progressContainer" style="float:right">
                                                      ${count}
                                                  </span>
                                              </div>
                                          </div>
                                      </div>
                                      <div class="text-secondary synchronising">
                                          Cliquez pour synchroniser
                                      </div>
                                  </div>
                              </div>
                          </div>
                      </div>
                  </div>`;
                        container.append(card);
                    });
                }
            })
            .catch(function (error) {
                // Handle any errors
                $(".progressContainer").text("Erreur.");
                console.error("Error fetching data:", error);
            });
    }
    countData();

    async function processTables(infoTables) {
        for (let key in infoTables) {
            const item = infoTables[key];

            const initialCount = item.count;
            var lastId = item.lastId;
            const tableName = item.tableName;

            $(`#${tableName}Container`).find("i").removeClass("fa-plus-circle").addClass("fa-spinner fa-spin");
            try {
                let countDone = 1;
                var totalCount = initialCount;
                var progressContainer = $(`#${tableName}Container`).closest(".card-body").find(".progressContainer");
                while (countDone !== 0) {
                    const { countDone, lastInsertedId } = await syncData(
                        tableName,
                        lastId
                    );
                    console.log('done One');
                    console.log(countDone, lastInsertedId);
                    // return
                    lastId = lastInsertedId;
                    if (countDone == undefined || lastInsertedId == undefined) {
                        try {
                            const response = await axios.get('/syncronize/count_table', {
                                params: {
                                    tableName: tableName,
                                }
                            });

                            const { countDone, lastInsertedId } = response.data;
                            totalCount = countDone;
                            lastId = lastInsertedId;
                        } catch (error) {
                            console.error("Error fetching data:", error);
                        }
                    } else {
                        totalCount = totalCount + parseInt(countDone);
                    }
                    progressContainer.text(totalCount);
                    console.log(lastId);



                    if (countDone == 0) {
                        $(`#${tableName}Container`).find("i").removeClass("fa-spinner fa-spin").addClass("fa-plus-circle");
                        $(`#${tableName}Container`).find("button").removeClass("btn-primary").addClass("btn-success");
                        progressContainer.text(totalCount);
                        break;
                    } else {
                        await new Promise((resolve) => setTimeout(resolve, 1000));
                    }
                }
            } catch (error) {
                console.error("Error synchronizing data:", error);
                $(`#${tableName}Container`).find("i").removeClass("fa-spinner fa-spin").addClass("fa-plus-circle");
                $(`#${tableName}Container`).find("button").removeClass("btn-primary").addClass("btn-danger");
                progressContainer.text(totalCount);
            } finally {
                $(`#${tableName}Container`).find("i").removeClass("fa-spinner fa-spin").addClass("fa-plus-circle");
                $(`#${tableName}Container`).find("button").removeClass("btn-primary").addClass("btn-success");
            }
        }
    }

    $("body").on("click", "#syncAll", async function (e) {
        e.preventDefault();

        const icon = $(this).find("i");
        icon.addClass("fa-spinner fa-spin");

        await processTables(infoTables);

        icon.removeClass("fa-spinner fa-spin");
    });

    $("body").on("click", ".sync-button", async function (e) {
        e.preventDefault();

        var progressContainer = $(this)
            .closest(".card-body")
            .find(".progressContainer");
        var tableName = $(this)
            .closest(".card-body")
            .find(".count")
            .data("tablename");
        var lastId = $(this).closest(".card-body").find(".count").data("last");
        var initialCount = $(this).closest(".card-body").find(".count").data("count");

        const icon = $(this).find("i");
        icon.removeClass("fa-rotate").addClass("fa-spinner fa-spin");

        try {
            let countDone = 1;
            var totalCount = initialCount;

            while (countDone !== 0) {
                const { countDone, lastInsertedId } = await syncData(
                    tableName,
                    lastId
                );
                totalCount = totalCount + parseInt(countDone);
                console.log('done One');
                console.log(countDone, lastInsertedId);
                // return
                lastId = lastInsertedId;
                if (countDone == undefined || lastInsertedId == undefined) {
                    try {
                        const response = await axios.get('/syncronize/count_table', {
                            params: {
                                tableName: tableName,
                            }
                        });

                        const { countDone, lastInsertedId } = response.data;
                        totalCount = countDone;
                        lastId = lastInsertedId;
                    } catch (error) {
                        console.error("Error fetching data:", error);
                    }
                } else {
                    totalCount = totalCount + parseInt(countDone);
                }
                progressContainer.text(totalCount);
                console.log(lastId);



                if (countDone == 0) {
                    $(`#${tableName}Container`).closest(".card-body").find("i").removeClass("fa-spinner fa-spin").addClass("fa-plus-circle");
                    $(`#${tableName}Container`).closest(".card-body").find("button").removeClass("btn-primary").addClass("btn-success");
                    progressContainer.text(totalCount);
                    break;
                } else {
                    await new Promise((resolve) => setTimeout(resolve, 1000));
                }
            }
        } catch (error) {
            console.error("Error synchronizing data:", error);
            icon.removeClass("fa-spinner fa-spin").addClass("fa-rotate");
            $(this).removeClass("btn-primary").addClass("btn-danger");
            progressContainer.text(totalCount);
        } finally {
            icon.removeClass("fa-spinner fa-spin").addClass("fa-rotate");
            $(this).removeClass("btn-primary").addClass("btn-success");
        }
    });

    async function syncData(tableName, lastId) {
        try {
            const response = await axios.get('/syncronize/sync', {
                params: {
                    tableName: tableName,
                    lastId,
                }
            });

            const { countDone, lastInsertedId } = response.data;
            return { countDone, lastInsertedId };
        } catch (error) {
            console.error("Error fetching data:", error);
            if (error.response && error.response.data) {
                return error.response.data;
            } else {
                return "Something went wrong!";
            }
        }
    }
});