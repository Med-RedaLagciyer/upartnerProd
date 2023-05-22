$(document).ready(function  () {
    // console.log('hi');

    var Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000
    });

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
                Toast.fire({
                    icon: 'success',
                    title: response
                })
                
                // window.location.href= "{{ path('app_index') }}";
                window.location = '/';  

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