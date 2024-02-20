$(document).ready(function  () {
    // console.log($("#btnReponse").attr("data-value"));

    // var Toast = Swal.mixin({
    //     toast: true,
    //     position: 'top-end',
    //     showConfirmButton: false,
    //     timer: 3000
    // });
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
            [10, 15, 20 ,25, 50, 100, 20000000000000],
            [10, 15, 20, 25, 50, 100, "All"],
        ],
        pageLength: 10,
        order: [[0, "desc"]],
        ajax: "/fournisseur/autres/listreclamation",
        processing: true,
        serverSide: true,
        deferRender: true,
        // orderable: false, targets: [0] ,
        columnDefs: [
            { targets: [3], orderable: false } // First column (index 0) is not orderable
          ],
        language: {
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
        },
    });

    $("body").on("dblclick", "#datatables_gestion_reclamation tbody tr", async function (e) {
        // alert('hi');
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
            toastr.error(message);
        }
        
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
                $("#datatables_commande").DataTable({
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
        
        $("#show_modal").modal("show")
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
                toastr.error(message);
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
            console.log(response);
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
            table.ajax.reload();

        } catch (error) {
            const message = error.response.data;
            console.log(error, error.response);
            toastr.error(message);
        }
    });

    var factureAjt = []


    $("#btnAjouterFacture").on("click", async function (e) {
        e.preventDefault();

        if($('#numFacture').val() == "" || $('#date').val() == "" || $('#montant').val() == "" ){
            // Toast.fire({
            //     icon: 'error',
            //     title: "DONNÉES FACTURE OBLIGATOIRES"
            // })
            toastr.error("DONNÉES FACTURE OBLIGATOIRES");
            return;
        }

        var numFacture = $('#numFacture').val();
        var date = $('#date').val();
        var montant = $('#montant').val();
        // var observation = $('#observation').val();
        var id = Math.random();
        var file = $("#fileUpload")[0].files[0];

        factureAjt.push({
            'id' : id,
            "numFacture" : numFacture,
            "date" : date,
            "montant" : montant,
            "file" : file,
        });

        var newRow = `<tr>  
                        <td>${numFacture}</td>
                        <td>${date}</td>
                        <td>${montant}</td>
                        <td>${montant}</td>
                        <td><a id="${id}" class="btnSupprimerFacture btn btn-danger btn-xs pull-right" style="width: 20px;"><i class="fas fa-minus"></i></a></td>
                    </tr>` ;

        $('#datatables_facture_ajoute tbody').prepend(newRow);

        $("#numFacture").val('')
        $("#date").val('')
        $("#montant").val('')
        $("#fileUpload").val('')
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
    
        const formDataMain = new FormData($("#formAjouter")[0]);
    
        try {
            const mainRequest = await axios.post(
                "/fournisseur/factures/reclamer",
                formDataMain
            );
            const mainResponse = mainRequest.data;
            console.log(mainResponse.reclamation_id);
            reclamation_id = mainResponse.reclamation_id;
            
            factureAjt.forEach(async function(facture) {
                const formData = new FormData();
                formData.append('numFacture', facture.numFacture);
                formData.append('date', facture.date);
                formData.append('montant', facture.montant);
                formData.append('file', facture.file);
                formData.append('reclamation_id', reclamation_id);
                console.log(facture.id);
                
                try {
                    const request = await axios.post(
                        "/fournisseur/factures/ajouter",
                        formData,
                        {
                            headers: {
                                'Content-Type': 'multipart/form-data',
                            }
                        }
                    );
                    const response = request.data;
                    var row = $('body a[id="' + facture.id + '"]').parents('tr');
                    let index = factureAjt.findIndex((f) => f.id = id);

                    factureAjt.splice(index, 1);

                    row.remove()
                    
                } catch (error) {
                    const message = error.response.data;
                    console.log(error, error.response);
                    toastr.error(message);
                    icon.addClass("fa-check-circle").removeClass("fa-spinner fa-spin ");
                }
            });
            factureAjt = [];
            table.ajax.reload();
            toastr.success("RÉCLAMATION BIEN ENVOYÉE");
        } catch (error) {
            const message = error.response.data;
            console.log(error, error.response);
            toastr.error(message);
            icon.addClass("fa-check-circle").removeClass("fa-spinner fa-spin ");
        }
    });
    
    $('body').on('click', '#downloadFile', function() {
        // alert("hi");
        var fileName = $(this).data('file');
        
        var fileUrl = '/uploads/factures/' + fileName; 
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