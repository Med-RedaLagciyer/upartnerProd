$(document).ready(function  () {
    // console.log('hi');
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

    var table = $("#datatables_gestion_reclamations").DataTable({
        lengthMenu: [
            [10, 15, 20 ,25, 50, 100, 20000000000000],
            [10, 15, 20, 25, 50, 100, "All"],
        ],
        pageLength: 20,
        order: [[0, "desc"]],
        ajax: "/admin/reclamations/list",
        processing: true,
        serverSide: true,
        deferRender: true,
        language: {
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
        },
    });

    $("#repondu").on('change', async function (){
        const repondu = $(this).val();
        console.log(repondu)
        table.columns(0).search(repondu).draw();
    })

    $('body').on('click','#btnReclamation',async function() {
        // const input = $(this).find("input");    
            id_reclamation = $(this).closest('tr').attr('id');   
            console.log(id_reclamation);
            try {
                const request = await axios.get('/admin/reclamations/details/'+id_reclamation);
                const response = request.data;
                // console.log(response)
                $('#reclamation_modal #infos_reclamation').html(response.infos);
                $('#reclamation_modal #tableFactures').DataTable({
                    lengthMenu: [
                        [10, 15, 25, 50, 100, 20000000000000],
                        [10, 15, 25, 50, 100, "All"],
                    ],
                    order: [[0, "desc"]],
                    language: {
                        url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
                    },
                });
                $('#reclamation_modal #datatables_commande').DataTable({
                    lengthMenu: [
                        [10, 15, 25, 50, 100, 20000000000000],
                        [10, 15, 25, 50, 100, "All"],
                    ],
                    order: [[0, "desc"]],
                    language: {
                        url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
                    },
                });
                $("#reclamation_modal").modal("show")
                table.ajax.reload();
                // $("#show_modal #designation").val(response.designation)
            } catch (error) {
                console.log(error, error.response);
                const message = error.response.data;
                toastr.error(message);
            }
            // ur here finish this
        
        
    })

    $('body').on('click','#btnRepondre',async function(e) {
        // const input = $(this).find("input");
        // alert('hi')
        e.preventDefault();
        id_reclamation = $(this).closest('tr').attr('id');   
            // console.log(id_freclamation);
            $('#reclamation_repondre_modal #repondre_reclamation').html('');
            try {
                const request = await axios.get('/admin/reclamations/details/'+id_reclamation);
                const response = request.data;
                // console.log(response.modification)
                $('#reclamation_repondre_modal #repondre_reclamation').html(response.repondre);
                $('#reclamation_repondre_modal #tableFactures').DataTable({
                    lengthMenu: [
                        [10, 15, 25, 50, 100, 20000000000000],
                        [10, 15, 25, 50, 100, "All"],
                    ],
                    order: [[0, "desc"]],
                    language: {
                        url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
                    },
                });
                $("#reclamation_repondre_modal").modal("show")
                // $("#show_modal #designation").val(response.designation)
            } catch (error) {
                console.log(error, error.response);
                const message = error.response.data;
                toastr.error(message);
            }
        
    })

    $("body").on("submit", "#message_form", async function (e) {
        e.preventDefault();
        // console.log();
        // return;
        
        // $("#reclamer_modal").modal("show")
        var file = $("#fileUpload")[0].files[0];
        const formData = new FormData($(this)[0]);
        formData.append('file', file);
        // let formData = new FormData([0]);
        formData.append("reclamation", $(this).attr('data-reclamation'));

        try {
            const request = await axios.post(
                "/admin/reclamations/message",
                formData
            );
            const response = request.data;
            table.ajax.reload();
            var msg="";
            if(response.message){
                msg +=`<div class="row"> 
                                <div class="col-3">
                                    
                                </div>  
                                <div class="col-9">
                                    <div class="form-group">
                                        <textarea class="form-control" rows="3" disabled="">${response.message}</textarea>
                                        <label style="float: left;" >${response.date}</label>
                                    </div>
                                </div>
                                
                            </div>`;
            }
            if(response.file){
                msg +=`<div class="row"> 
                <div class="col-3">
                    
                    </div>  
                    <div class="col-9">
                        <div class="form-group">
                            <a id="downloadPiece" data-file="${response.file}" class="btn btn-primary btn-xs pull-right" style="background-color: #e9ecef; color: #515151;border: 1px solid #ced4da;">
                                <i class="fas fa-download"></i> Piece jointe
                            </a>
                            <br>
                            <label style="float: right;" >${response.date}</label>
                        </div>
                    </div>
                    
                </div>`;
            }
            $("body #messages").append(msg);
            $(this)[0].reset();

        } catch (error) {
            const message = error.response.data;
            console.log(error, error.response);
            toastr.error(message);
        }
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
                    const request = await axios.get('/admin/reclamations/dets/'+id+'/'+type);
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