$(document).ready(function  () {
    console.log('hi');

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
        let icon = $("#btnvalider i");
        // alert(icon);
        icon.removeClass("fa-check").addClass("fa-spinner fa-spin");
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
                console.log(response);
                toastr.success(response);
                icon.addClass("fa-check").removeClass("fa-spinner fa-spin");
                
                window.location = '/';  

        } catch (error) {
            const message = error.response;
            console.log(error, error.response);
            toastr.error(message);
            icon.addClass("fa-check").removeClass("fa-spinner fa-spin");
        }
    });
    
})