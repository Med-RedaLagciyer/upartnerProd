$(document).ready(function  () {
    console.log('hi');
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
    // toastr.success('Toastr is working!');

    var table = $("#datatables_gestion_fournisseurs").DataTable({
        lengthMenu: [
            [10, 15, 20 ,25, 50, 100, 20000000000000],
            [10, 15, 20, 25, 50, 100, "All"],
        ],
        pageLength: 20,
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

    $('body').on('click','#btnDetails',async function(e) {
        e.preventDefault();
            id_frs = $(this).closest('tr').attr('id');
            console.log(id_frs);

            try {
                const request = await axios.get('/admin/fournisseurs/details/'+id_frs);
                const response = request.data;
                $('#show_modal #infos_frs').html(response.infos);
                $("#datatables_detail_frs").DataTable({
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
            } catch (error) {
                console.log(error, error.response);
                const message = error.response;
                Toast.fire({
                    icon: 'error',
                    title: message,
                  })
            }
        
    })

    // $('body #numberInput').on('input', function() {
    //     console.log('hi');
    //     var inputVal = $(this).val();
    //     if (inputVal.length > 15) {
    //       $(this).val(inputVal.slice(0, 15));
    //       Toast.fire({
    //         icon: 'error',
    //         title: "ice doit contenir 15 caractères maximum ",
    //       })
    //     }
    //   })

    $("body").on("input", '#numberInput',  () => {
        console.log($("body #formModifier #numberInput").val().length )
        if( $("body #formModifier #numberInput").val().length !== 15 ) { 
            $("body #formModifier #numberInput").css('border','1px solid red')
        }else { 
            $("body #formModifier #numberInput").css('border','1px solid #83D350')
        }
    })

    $('body').on('click','#btnModification',async function(e) {
        e.preventDefault();
            id_frs = $(this).closest('tr').attr('id');
            console.log(id_frs);

            try {
                const request = await axios.get('/admin/fournisseurs/details/'+id_frs);
                const response = request.data;
                $('#modif_modal #modif_frs').html(response.modif);
                $("#modif_modal").modal("show")
            } catch (error) {
                console.log(error, error.response);
                const message = error.response;
                Toast.fire({
                    icon: 'error',
                    title: message,
                  })
            }
        
    })

    $("body").on("submit", "#formModifier", async function (e) {
        e.preventDefault();
        
        id = $(this).attr('data-id');   
        // $("#reclamer_modal").modal("show")
        const formData = new FormData($("#formModifier")[0]);
        formData.append("idfrs", id);
        
        try {
            const request = await axios.post(
                "/admin/fournisseurs/modifier",
                formData
                );
                const response = request.data;
                // Toast.fire({
                //     icon: 'success',
                //     title: response
                // })
                toastr.success(response);
                $("#modif_modal").modal("hide")
                table.ajax.reload();

        } catch (error) {
            const message = error.response.data;
            console.log(error, error.response);
            // Toast.fire({
            //     icon: "error",
            //     title: message,
            // });
            toastr.error(message);
            icon.addClass("fa-check-circle").removeClass("fa-spinner fa-spin ");
        }
    });
})