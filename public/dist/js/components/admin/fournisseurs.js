$(document).ready(function  () {
    console.log('hi');

    var table = $("#datatables_gestion_fournisseurs").DataTable({
        lengthMenu: [
            [10, 15, 25, 50, 100, 20000000000000],
            [10, 15, 25, 50, 100, "All"],
        ],
        order: [[0, "desc"]],
        ajax: "/admin/fournisseurs/list",
        processing: true,
        serverSide: true,
        deferRender: true,
        language: {
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
        },
    });

    $('body').on('click','#btnAjouter', async function (e) {
        e.preventDefault();
        console.log('hello');
        
        $('#modalAjouter').modal("show");
        // setTimeout(() => {
        //     $('#annuler_planning_modal').modal("hide");
        // }, 1000);
    })
})