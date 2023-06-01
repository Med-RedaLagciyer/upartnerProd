$(document).ready(function  () {
    // console.log($("#btnReponse").attr("data-value"));

    var Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000
    });

    // $("body #show_modal #infos_factures #datatables_detail_facture").DataTable({
    //     lengthMenu: [
    //         [10, 15, 25, 50, 100, 20000000000000],
    //         [10, 15, 25, 50, 100, "All"],
    //     ],
    //     order: [[0, "desc"]],
    //     language: {
    //         url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
    //     },
    // });

    // var tableAjoute= $("#datatables_facture_ajoute").DataTable({
    //     lengthMenu: [
    //         [10, 15, 25, 50, 100, 20000000000000],
    //         [10, 15, 25, 50, 100, "All"],
    //     ],
    //     order: [[0, "desc"]],
    //     language: {
    //         url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
    //     },
    // });
    var table = $("#datatables_gestion_reclamation").DataTable({
        lengthMenu: [
            [10, 15, 25, 50, 100, 20000000000000],
            [10, 15, 25, 50, 100, "All"],
        ],
        order: [[0, "desc"]],
        ajax: "/fournisseur/autres/listreclamation",
        processing: true,
        serverSide: true,
        deferRender: true,
        // orderable: false, targets: [0] ,
        columnDefs: [
            { targets: [5], orderable: false } // First column (index 0) is not orderable
          ],
        language: {
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
        },
    });
    

    factures = [];

    $("body").on("click","#btnDetails", async function (e) {
        // console.log('hi');
        e.preventDefault();


            id_reclamation = $(this).closest('tr').attr('id');
            // console.log(type);

            try {
                const request = await axios.get('/fournisseur/autres/details/'+id_reclamation);
                const response = request.data;
                $('#show_modal #infos_factures').html(response.infos);
                
                $("#datatables_gestion_autres").DataTable({
                    lengthMenu: [
                        [10, 15, 25, 50, 100, 20000000000000],
                        [10, 15, 25, 50, 100, "All"],
                    ],
                    order: [[0, "desc"]],
                    // orderable: false, targets: [0] ,
                    columnDefs: [
                        { orderable: false, targets: 0 } // First column (index 0) is not orderable
                      ],
                    language: {
                        url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
                    },
                });

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
        
        $("#show_modal").modal("show")
    });

    $("body").on("click","#btnReclamation", async function (e) {
        // console.log('hi');
        e.preventDefault();


            id_reclamation = $(this).closest('tr').attr('id');
            // console.log(type);

            try {
                const request = await axios.get('/fournisseur/autres/details/'+id_reclamation);
                const response = request.data;
                $('#repondre_modal #infos_factures').html(response.repondre);

                

                $("#repondre_modal").modal("show")
                // $("#show_modal #designation").val(response.designation)
            } catch (error) {
                console.log(error, error.response);
                const message = error.response.data;
                Toast.fire({
                    icon: 'error',
                    title: message,
                  })
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
            table.ajax.reload();

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
    
})