$(document).ready(function  () {
    // console.log('hi');

    toastr.options = {
        "closeButton": true,
        "debug": false,
        "progressBar": true,
        "preventDuplicates": false,
        "positionClass": "toast-top-right",
        "onclick": null,
        "showDuration": "400",
        "hideDuration": "1000",
        "timeOut": "7000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    }

    


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
                toastr.error(message);
            }
        
    })
    $('body').on('click','#btnDetails',async function(e) {
        // const input = $(this).find("input");
        // alert('hi')
        e.preventDefault();
            id_facture_cab = $(this).closest('tr').attr('id');   
            type = $(this).attr('data-value');
            // console.log(type);

            if(type = "default"){
                // alert(id_facture_cab);
                // return;
                try {
                    const request = await axios.get('/fournisseur/factures/detailsCommande/'+id_facture_cab+'/'+type);
                    const response = request.data;
                    $('#detailsCommand #infos_Commande').html(response.infos);
                    $("#datatables_commande").DataTable({
                        lengthMenu: [
                            [5, 10, 15, 20 ,25, 50, 100, 20000000000000],
                            [5, 10, 15, 20, 25, 50, 100, "All"],
                        ],
                        pageLength: 5,
                        order: [[0, "desc"]],
                        // orderable: false, targets: [0] ,
                        columnDefs: [
                            { orderable: false, targets: 0 } // First column (index 0) is not orderable
                          ],
                        language: {
                            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
                        },
                    });
                    $("#datatables_reception").DataTable({
                        lengthMenu: [
                            [5, 10, 15, 20 ,25, 50, 100, 20000000000000],
                            [5, 10, 15, 20, 25, 50, 100, "All"],
                        ],
                        pageLength: 5,
                        order: [[0, "desc"]],
                        // orderable: false, targets: [0] ,
                        columnDefs: [
                            { orderable: false, targets: 0 } // First column (index 0) is not orderable
                          ],
                        language: {
                            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
                        },
                    });
                    $("#datatables_facture").DataTable({
                        lengthMenu: [
                            [5, 10, 15, 20 ,25, 50, 100, 20000000000000],
                            [5, 10, 15, 20, 25, 50, 100, "All"],
                        ],
                        pageLength: 5,
                        order: [[0, "desc"]],
                        // orderable: false, targets: [0] ,
                        columnDefs: [
                            { orderable: false, targets: 0 } // First column (index 0) is not orderable
                          ],
                        language: {
                            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
                        },
                    });
                    $("#detailsCommand").modal("show")
                    // $("#show_modal #designation").val(response.designation)
                } catch (error) {
                    console.log(error, error.response);
                    const message = error.response.data;
                    toastr.error(message);
                }
            }else{
                try {
                    const request = await axios.get('/fournisseur/factures/details/'+id_facture_cab+'/'+type);
                    const response = request.data;
                    $('#show_modal #infos_factures').html(response.infos);
                    $("#datatables_detail_facture").DataTable({
                        lengthMenu: [
                            [10, 15, 20 ,25, 50, 100, 20000000000000],
                            [10, 15, 20, 25, 50, 100, "All"],
                        ],
                        pageLength: 20,
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
                    toastr.error(message);
                }
            }


            
        
    })

    $('body').on('click','#btnExtraction',function (e) {
        e.preventDefault();
        window.open('/fournisseur/factures/extraction', '_blank');
    });

    $('body').on('click','.btnDet',async function(e) {
        // const input = $(this).find("input");
        // alert('hi')
        e.preventDefault();
            type = $(this).attr('id');   
            id = $(this).closest('tr').attr('id');  
            // type = $(this).attr('data-value');
            // alert(type);
            // return;
            // console.log(type);
                try {
                    const request = await axios.get('/fournisseur/factures/dets/'+id+'/'+type);
                    const response = request.data;
                    $('#dets #infos_dets').html(response.infos);
                    $("#datatables_dets").DataTable({
                        lengthMenu: [
                            [10, 15, 20 ,25, 50, 100, 20000000000000],
                            [10, 15, 20, 25, 50, 100, "All"],
                        ],
                        pageLength: 15,
                        order: [[0, "desc"]],
                        // orderable: false, targets: [0] ,
                        columnDefs: [
                            { orderable: false, targets: 0 } // First column (index 0) is not orderable
                          ],
                        language: {
                            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
                        },
                    });
                    
                    $("#dets").modal("show")
                    // $("#show_modal #designation").val(response.designation)
                } catch (error) {
                    console.log(error, error.response);
                    const message = error.response.data;
                    toastr.error(message);
                }


            
        
    })

    $("body #show_modal #infos_factures #datatables_detail_facture").DataTable({
        lengthMenu: [
            [10, 15, 20 ,25, 50, 100, 20000000000000],
            [10, 15, 20, 25, 50, 100, "All"],
        ],
        pageLength: 20,
        order: [[0, "desc"]],
        language: {
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
        },
    });

    var tableAjoute= $("#datatables_facture_ajoute").DataTable({
        lengthMenu: [
            [10, 15, 20 ,25, 50, 100, 20000000000000],
            [10, 15, 20, 25, 50, 100, "All"],
        ],
        pageLength: 20,
        order: [[0, "desc"]],
        language: {
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
        },
    });

    var table = $("#datatables_gestion_factures").DataTable({
        lengthMenu: [
            [10, 15, 20, 25, 50, 100, 20000000000000],
            [10, 15, 20, 25, 50, 100, "All"],
        ],
        pageLength: 20,
        order: [[0, "desc"]],
        ajax: {
            url: "/fournisseur/factures/list",
            type: "GET",
            data: function (d) {
                d.status = $('#status-filter').val(); // Add status filter value to request data
            }
        },
        processing: true,
        serverSide: true,
        deferRender: true,
        columnDefs: [
            { orderable: false, targets: [0,6,7] } // First column (index 0) is not orderable
        ],
        language: {
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
        },
    });

    $('#status-filter').on('change', function () {
        table.ajax.reload(); // Reload table data when filter changes
    });

    var table2 = $("#datatables_gestion_factures_2").DataTable({
        lengthMenu: [
            [10, 15, 20 ,25, 50, 100, 20000000000000],
            [10, 15, 20, 25, 50, 100, "All"],
        ],
        pageLength: 20,
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

    commandes = [];
    $("body").on("click", "#checkfacture", function () {
        const input = $(this)
        // console.log(input.attr("data-id"))
        // input.prop("checked", true);
        if(input.is(":checked")){
            
            commandes.push(input.attr("data-id"));
        }else{
            const index = commandes.indexOf(input.attr("data-id"));
            commandes.splice(index,1);
        }

        var anyChecked = $(".checkfacture:checked").length > 0;
        $("#btnReclamer").prop("disabled", !anyChecked);
        
        console.log(commandes);
    });
    // $("body").off("click", ".checkfacture");

    $("body").on("click", ".check_all_factures", function () {
        // alert('test')
        // e.stopPropagation();
        commandes = [];
        const fac = $("body .checkfacture");

        fac.each(function () {
            if (!$(this).is(':disabled')) {
                $(this).prop("checked", $(".check_all_factures").prop("checked"));
                if ($(this).prop("checked")) {
                    commandes.push($(this).attr("data-id"));
                }
            }
        });

        var anyChecked = $(".checkfacture:checked").length > 0;
        $("#btnReclamer").prop("disabled", !anyChecked);

        console.log(commandes);
    });

    $("#btnReclamer").on("click", async function (e) {
        e.preventDefault();
        console.log(commandes);
        
        $("#reclamer_modal").modal("show")
    });

    $("#btnAjouter").on("click", async function (e) {
        e.preventDefault();
        // console.log(commandes);
        
        $("#ajouter_modal").modal("show")
    });

    $("#formReclamer").on("submit", async function (e) {
        const fac = $("body .checkfacture");
        console.log(commandes);
        e.preventDefault();

        // $("#reclamer_modal").modal("show")
        const formData = new FormData($("#formReclamer")[0]);
        // let formData = new FormData([0]);
        formData.append("commandes", JSON.stringify(commandes));

        try {
            const request = await axios.post(
                "/fournisseur/factures/reclamer",
                formData
            );
            const response = request.data.message;
            $("#reclamer_modal").modal("hide")
            toastr.success(response);
            commandes = [];
            $("#btnReclamer").prop("disabled", true);
            table.ajax.reload();
            console.log(commandes);

        } catch (error) {
            const message = error.response.data;
            console.log(error, error.response);
            toastr.error(message);
            icon.addClass("fa-check-circle").removeClass("fa-spinner fa-spin ");
        }
    });


    $("body").on("submit", "#message_form", async function (e) {
        e.preventDefault();
        // console.log();
        // return;
        
        // $("#reclamer_modal").modal("show")
        var file = $("#fileUpload")[0].files[0];
        const formData = new FormData($("#message_form")[0]);
        formData.append('file', file);
        // let formData = new FormData([0]);
        formData.append("reclamation", $(this).attr('data-reclamation'));

        try {
            const request = await axios.post(
                "/fournisseur/factures/message",
                formData
            );
            const response = request.data;

            var msg="";
            if(response.message){
                msg +=`<div class="row">   
                                <div class="col-7">
                                    <div class="form-group">
                                        <textarea class="form-control" style="background: #d9eeff;" rows="3" disabled="">${response.message}</textarea>
                                        <label style="float: left;" >${response.date}</label>
                                    </div>
                                </div>
                                <div class="col-5">
                                
                                </div>
                            </div>`;
            }
            if(response.file){
                msg +=`<div class="row">   
                    <div class="col-7">
                        <div class="form-group">
                            <a id="downloadPiece" data-file="${response.file}" class="btn btn-primary btn-xs pull-right" style="background-color: #d9eeff; color: #515151;border: 1px solid #ced4da;">
                                <i class="fas fa-download"></i> Piece jointe
                            </a>
                            <br>
                            <label style="float: left;" >${response.date}</label>
                        </div>
                    </div>
                    <div class="col-5">
                    
                    </div>
                </div>`;
            }

            $("body #messages").append(msg);
            $("#message_form")[0].reset();

        } catch (error) {
            const message = error.response.data;
            console.log(error, error.response);
            toastr.error(message);
        }
    });

    var factureAjt = []


    $("#btnAjouterFacture").on("click", async function (e) {
        e.preventDefault();

        if($('#numFacture').val() == "" || $('#date').val() == "" || $('#montant').val() == "" || $('#observation').val() == "" ){
            toastr.error("DONNÉES FACTURE OBLIGATOIRES");
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
            toastr.success(response);
            factureAjt = []
            table2.ajax.reload();

        } catch (error) {
            const message = error.response.data;
            console.log(error, error.response);
            toastr.error(message);
            icon.addClass("fa-check-circle").removeClass("fa-spinner fa-spin ");
        }
    });

    $('body').on('click', '#downloadPiece', function() {
        // alert("hi");
        var fileName = $(this).data('file');
        
        var fileUrl = '/uploads/message/' + fileName; 
        // window.location.href = fileUrl;
        var downloadLink = $('<a></a>');
        downloadLink.attr('href', fileUrl);
        downloadLink.attr('download', 'PieceJoint.pdf'); 
        downloadLink.css('display', 'none');
        
        $('body').append(downloadLink);
        
        downloadLink[0].click();
        
        setTimeout(function() {
            downloadLink.remove();
        }, 100);
    });
    
})