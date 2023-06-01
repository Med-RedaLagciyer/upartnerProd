$(document).ready(function  () {
    // console.log('hi');

    var Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000
    });

    


    $('body').on('click','#btnReclamation',async function(e) {
        // const input = $(this).find("input");
        // alert('hi')
        e.preventDefault();
            id_facture_cab = $(this).closest('tr').attr('id');   
            type = $(this).attr('data-value');
            // console.log(type);

            try {
                const request = await axios.get('/fournisseur/factures/reclamation/'+id_facture_cab+'/'+type);
                const response = request.data;
                $('#show_modal #infos_factures').html(response.infos);
                $("#show_modal").modal("show")
                // $("#show_modal #designation").val(response.designation)
            } catch (error) {
                console.log(error, error.response);
                const message = error.response.data;
                Toast.fire({
                    icon: 'error',
                    title: message,
                  })
            }
        
    })
    $('body').on('click','#btnDetails',async function(e) {
        // const input = $(this).find("input");
        // alert('hi')
        e.preventDefault();
            id_facture_cab = $(this).closest('tr').attr('id');   
            type = $(this).attr('data-value');
            // console.log(type);

            try {
                const request = await axios.get('/fournisseur/factures/details/'+id_facture_cab+'/'+type);
                const response = request.data;
                $('#show_modal #infos_factures').html(response.infos);
                $("#show_modal").modal("show")
                // $("#show_modal #designation").val(response.designation)
            } catch (error) {
                console.log(error, error.response);
                const message = error.response.data;
                Toast.fire({
                    icon: 'error',
                    title: message,
                  })
            }
        
    })

    $("body #show_modal #infos_factures #datatables_detail_facture").DataTable({
        lengthMenu: [
            [10, 15, 25, 50, 100, 20000000000000],
            [10, 15, 25, 50, 100, "All"],
        ],
        order: [[0, "desc"]],
        language: {
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
        },
    });

    var tableAjoute= $("#datatables_facture_ajoute").DataTable({
        lengthMenu: [
            [10, 15, 25, 50, 100, 20000000000000],
            [10, 15, 25, 50, 100, "All"],
        ],
        order: [[0, "desc"]],
        language: {
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
        },
    });

    var table = $("#datatables_gestion_factures").DataTable({
        lengthMenu: [
            [10, 15, 25, 50, 100, 20000000000000],
            [10, 15, 25, 50, 100, "All"],
        ],
        order: [[0, "desc"]],
        ajax: "/fournisseur/factures/list",
        processing: true,
        serverSide: true,
        deferRender: true,
        // orderable: false, targets: [0] ,
        columnDefs: [
            { orderable: false, targets: [0] } // First column (index 0) is not orderable
          ],
        language: {
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
        },
    });

    var table2 = $("#datatables_gestion_factures_2").DataTable({
        lengthMenu: [
            [10, 15, 25, 50, 100, 20000000000000],
            [10, 15, 25, 50, 100, "All"],
        ],
        order: [[0, "desc"]],
        ajax: "/fournisseur/factures/list2",
        processing: true,
        serverSide: true,
        deferRender: true,
        // orderable: false, targets: [0] ,
        columnDefs: [
            { orderable: false, targets: 0 } // First column (index 0) is not orderable
          ],
        language: {
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
        },
    });

    factures = [];
    $("body").on("click", "#checkfacture", function () {
        const input = $(this)
        // console.log(input.attr("data-id"))
        // input.prop("checked", true);
        if(input.is(":checked")){
            
            factures.push(input.attr("data-id"));
        }else{
            const index = factures.indexOf(input.attr("data-id"));
            factures.splice(index,1);
        }

        var anyChecked = $(".checkfacture:checked").length > 0;
        $("#btnReclamer").prop("disabled", !anyChecked);
        
        console.log(factures);
    });
    // $("body").off("click", ".checkfacture");

    $("body").on("click", ".check_all_factures", function () {
        // alert('test')
        // e.stopPropagation();
        factures = [];
        const fac = $("body .checkfacture");

        fac.each(function () {
            if (!$(this).is(':disabled')) {
                $(this).prop("checked", $(".check_all_factures").prop("checked"));
                if ($(this).prop("checked")) {
                    factures.push($(this).attr("data-id"));
                }
            }
        });

        var anyChecked = $(".checkfacture:checked").length > 0;
        $("#btnReclamer").prop("disabled", !anyChecked);

        console.log(factures);
    });

    $("#btnReclamer").on("click", async function (e) {
        e.preventDefault();
        console.log(factures);
        
        $("#reclamer_modal").modal("show")
    });

    $("#btnAjouter").on("click", async function (e) {
        e.preventDefault();
        // console.log(factures);
        
        $("#ajouter_modal").modal("show")
    });

    $("#formReclamer").on("submit", async function (e) {
        console.log(factures);
        e.preventDefault();

        // $("#reclamer_modal").modal("show")
        const formData = new FormData($("#formReclamer")[0]);
        // let formData = new FormData([0]);
        formData.append("factures", JSON.stringify(factures));

        try {
            const request = await axios.post(
                "/fournisseur/factures/reclamer",
                formData
            );
            const response = request.data;
            $("#reclamer_modal").modal("hide")
            Toast.fire({
                icon: 'success',
                title: response
            })
            factures = [];
            table.ajax.reload();

        } catch (error) {
            const message = error.response.data;
            console.log(error, error.response);
            Toast.fire({
                icon: "error",
                title: message,
            });
            icon.addClass("fa-check-circle").removeClass("fa-spinner fa-spin ");
        }
    });


    $("body").on("submit", "#message_form", async function (e) {
        e.preventDefault();
        // console.log();
        // return;
        
        // $("#reclamer_modal").modal("show")
        const formData = new FormData($("#message_form")[0]);
        // let formData = new FormData([0]);
        formData.append("reclamation", $(this).attr('data-reclamation'));

        try {
            const request = await axios.post(
                "/fournisseur/factures/message",
                formData
            );
            const response = request.data;

            var msg = `<div class="row">   
                            <div class="col-7">
                                <div class="form-group">
                                    <textarea class="form-control" style="background: #d9eeff;" rows="3" disabled="">${response.message}</textarea>
                                    <label style="float: left;" >${response.date}</label>
                                </div>
                            </div>
                            <div class="col-5">
                            
                            </div>
                        </div>`

            $("body #messages").append(msg);

        } catch (error) {
            const message = error.response.data;
            console.log(error, error.response);
            Toast.fire({
                icon: "error",
                title: message,
            });
        }
    });

    var factureAjt = []


    $("#btnAjouterFacture").on("click", async function (e) {
        e.preventDefault();

        if($('#numFacture').val() == "" || $('#date').val() == "" || $('#montant').val() == "" || $('#observation').val() == "" ){
            Toast.fire({
                icon: 'error',
                title: "Veuillez remplire tous les informations du facture!"
            })
            return;
        }

        var numFacture = $('#numFacture').val();
        var date = $('#date').val();
        var montant = $('#montant').val();
        var observation = $('#observation').val();
        var id = Math.random();

        factureAjt.push({
            'id' : id,
            "numFacture" : numFacture,
            "date" : date,
            "montant" : montant,
            "observation" : observation,
        });

        var newRow = `<tr>  
                        <td>${numFacture}</td>
                        <td>${date}</td>
                        <td>${montant}</td>
                        <td>${observation}</td>
                        <td><a id="${id}" class="btnSupprimerFacture btn btn-danger btn-xs pull-right" style="width: 20px;"><i class="fas fa-minus"></i></a></td>
                    </tr>` ;

        $('#datatables_facture_ajoute tbody').prepend(newRow);

        $("#numFacture").val('')
        $("#date").val('')
        $("#montant").val('')
        $("#observation").val('')
    });

    $("body").on("click", ".btnSupprimerFacture", async function (e) {
        e.preventDefault();
        var id = $(this).attr('id');
        var row = $(this).parents('tr');
        let index = factureAjt.findIndex((f) => f.id = id);

        factureAjt.splice(index, 1);

        row.remove()
    });

    $("#formAjouter").on("submit", async function (e) {
        e.preventDefault();
        console.log(factureAjt);

        // // $("#reclamer_modal").modal("show")
        const formData = new FormData($("#formAjouter")[0]);
        // let formData = new FormData([0]);
        formData.append("factures", JSON.stringify(factureAjt));

        try {
            const request = await axios.post(
                "/fournisseur/factures/ajouter",
                formData
            );
            const response = request.data;
            $("#ajouter_modal").modal("hide")
            Toast.fire({
                icon: 'success',
                title: response
            })
            factureAjt = []
            table2.ajax.reload();

        } catch (error) {
            const message = error.response.data;
            console.log(error, error.response);
            Toast.fire({
                icon: "error",
                title: message,
            });
            icon.addClass("fa-check-circle").removeClass("fa-spinner fa-spin ");
        }
    });
    
})