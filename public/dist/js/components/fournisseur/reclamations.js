$(document).ready(function  () {
    // console.log('hi');
    reclamations = [];

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

    id_freclamation = false
    $('body').on('click','#btnDetails',async function(e) {
        // const input = $(this).find("input");
        // alert('hi')
        e.preventDefault();
            id_freclamation = $(this).closest('tr').attr('id');   
            // console.log(id_freclamation);
            $('#reclamation_modal #infos_reclamation').html('');
            try {
                const request = await axios.get('/fournisseur/reclamations/details/'+id_freclamation);
                const response = request.data;
                // console.log(response)
                $('#reclamation_modal #infos_reclamation').html(response.infos);
                $('#reclamation_modal #tableFactures').DataTable({
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
                $("#reclamation_modal").modal("show")
                // $("#show_modal #designation").val(response.designation)
            } catch (error) {
                console.log(error, error.response);
                const message = error.response.data;
                toastr.error(message);
            }
        
    })

    $('body').on('click','#btnModifier',async function(e) {
        // const input = $(this).find("input");
        // alert('hi')
        e.preventDefault();
            id_freclamation = $(this).closest('tr').attr('id');   
            // console.log(id_freclamation);
            $('#reclamation_modification_modal #modifier_reclamation').html('');
            try {
                const request = await axios.get('/fournisseur/reclamations/details/'+id_freclamation);
                const response = request.data;
                // console.log(response.modification)
                $('#reclamation_modification_modal #modifier_reclamation').html(response.modification);
                $('#reclamation_modification_modal #tableFactures').DataTable({
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
                $("#reclamation_modification_modal").modal("show")
                // $("#show_modal #designation").val(response.designation)
            } catch (error) {
                console.log(error, error.response);
                const message = error.response.data;
                toastr.error(message);
            }
        
    })

    $('body').on('click','#btnReponse',async function(e) {
        // const input = $(this).find("input");
        // alert('hi')
        e.preventDefault();
        let formData = new FormData();
        formData.append("reponse", 'yes');
            id_freclamation = $(this).closest('tr').attr('id');   
            // console.log(id_freclamation);
            $('#reponse_modal #repondre_reclamation').html('');
            try {
                const request = await axios.post('/fournisseur/reclamations/details/'+id_freclamation, formData);
                const response = request.data;
                // console.log(response.modification)
                $('#reponse_modal #repondre_reclamation').html(response.reclamation_reponse);
                tableReponse.ajax.reload();
                $('#reponse_modal #tableFactures').DataTable({
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
                $("#reponse_modal").modal("show")
                // $("#show_modal #designation").val(response.designation)
            } catch (error) {
                console.log(error, error.response);
                const message = error.response.data;
                toastr.error(message);
            }
        
    })

    $("body #modifier_reclamation ").on("submit","#modifier-form", async function (e) {
        e.preventDefault();
        // alert(id_freclamation)
        // console.log(id_freclamation);
        const formData = new FormData($("#modifier-form")[0]);
        try {
            const request = await axios.post('/fournisseur/reclamations/modifier/'+id_freclamation, formData);
            const response = request.data;
            
            $("#reclamation_modification_modal").modal("hide")
            toastr.success(response);
            table.ajax.reload();
        } catch (error) {
            console.log(error, error.response);
            const message = error.response.data;
            toastr.error(message);
        }
    })

    var table = $("#datatables_gestion_reclamations").DataTable({
        lengthMenu: [
            [10, 15, 20 ,25, 50, 100, 20000000000000],
            [10, 15, 20, 25, 50, 100, "All"],
        ],
        pageLength: 20,
        order: [[0, "desc"]],
        ajax: "/fournisseur/reclamations/list",
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

    var tableReponse = $("#datatables_gestion_reclamations_reponses").DataTable({
        lengthMenu: [
            [10, 15, 20 ,25, 50, 100, 20000000000000],
            [10, 15, 20, 25, 50, 100, "All"],
        ],
        pageLength: 20,
        order: [[0, "desc"]],
        ajax: "/fournisseur/reclamations/listreponses",
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

    reclamations = [];
    $("body").on("click", "#datatables_gestion_reclamations tbody tr", function () {
        const input = $(this).find("input.checkfacture");

        if(input.prop('disabled')){
            console.log('already reclamee')
        }else{
            if (input.prop("checked") == true) {
                input.prop("checked", false);
                const index = reclamations.indexOf(input.attr("id"));
                reclamations.splice(index, 1);
            } else {
                input.prop("checked", true);
                reclamations.push(input.attr("id"));
            }
        }
        
        // console.log(reclamations);
    });

    $("body").on("click", ".check_all_reclamation", function () {
        // alert('test')
        // e.stopPropagation();
        reclamations = [];
        const fac = $("body .checkreclamation");
        if ($(".check_all_reclamation").prop("checked") == true) {
            fac.prop("checked", true);
            fac.map(function () {
                reclamations.push(this.value);
            });
            // console.log(admissions);
        } else {
            fac.prop("checked", false);
        }
        console.log(reclamations);
    });

    $('body').on('click','#btnSupprimer',async function (e) {
        e.preventDefault();
        rec = [];
        const id = $(this).closest('tr').attr('id');
        rec.push(id);
        let formData = new FormData();
        formData.append("reclamations", JSON.stringify(rec));
        var res = confirm('VOULEZ-VOUS VRAIMENT SUPPRIMER LA RÉCLAMATION ?');
        if(res == 1){
            try {
                // icon.remove('fa-trash').addClass("fa-spinner fa-spin ");
                const request = await axios.post('/fournisseur/reclamations/delete', formData);
                const response = request.data;
                console.log(response);
                // icon.addClass('fa-trash').removeClass("fa-spinner fa-spin ");
                table.ajax.reload();
                toastr.success(response);
            } catch (error) {
                console.log(error, error.response);
                const message = error.response.data;
                toastr.error(message);
                // icon.addClass('fa-trash').removeClass("fa-spinner fa-spin ");
            }
        }
    })

    $('body').on('click','#btnSupprimerMulti',async function (e) {
        e.preventDefault();
        if (reclamations.length == 0) {
            toastr.error("MERCI DE CHOISIR UNE OU PLUSIEURS RECLAMATIONS");
            return;
        }

        let formData = new FormData();
        formData.append("reclamations", JSON.stringify(reclamations));

        var res = confirm('VOULEZ-VOUS VRAIMENT SUPPRIMER CES RÉCLAMATIONS ?');
        if(res == 1){
            try {
                // icon.remove('fa-trash').addClass("fa-spinner fa-spin ");
                const request = await axios.post('/fournisseur/reclamations/delete', formData);
                const response = request.data;
                // console.log(response);
                // icon.addClass('fa-trash').removeClass("fa-spinner fa-spin ");
                table.ajax.reload();
                toastr.success(response);
            } catch (error) {
                console.log(error, error.response);
                const message = error.response.data;
                toastr.error(message);
                // icon.addClass('fa-trash').removeClass("fa-spinner fa-spin ");
            }
        }
    })

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
                "/fournisseur/reclamations/message",
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
            $("#message_form")[0].reset();

        } catch (error) {
            const message = error.response.data;
            console.log(error, error.response);
            toastr.error(message);
        }
    });
    
})