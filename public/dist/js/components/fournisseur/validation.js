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

    $("body").on("submit", "#formValider", async function (e) {
        e.preventDefault();
        
        id = $(this).attr('data-id');   
        // $("#reclamer_modal").modal("show")
        const formData = new FormData($("#formValider")[0]);
        formData.append("idfrs", id);
        
        try {
            const request = await axios.post(
                "/fournisseur/validation/valider",
                formData
                );
                const response = request.data;
                toastr.success(response);
                
                // window.location.href= "{{ path('app_index') }}";
                window.location = '/';  

        } catch (error) {
            const message = error.response.data;
            console.log(error, error.response);
            toastr.error(message);
            icon.addClass("fa-check-circle").removeClass("fa-spinner fa-spin ");
        }
    });
    
})