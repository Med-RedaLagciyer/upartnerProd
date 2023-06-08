$(document).ready(function  () {
    // console.log('hi');

    var Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000
    });

    var table = $("#datatables_gestion_users").DataTable({
        lengthMenu: [
            [10, 15, 25, 50, 100, 20000000000000],
            [10, 15, 25, 50, 100, "All"],
        ],
        order: [[0, "desc"]],
        ajax: "/admin/users/list",
        processing: true,
        serverSide: true,
        deferRender: true,
        language: {
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
        },
    });

    $('body').on('click','#btnAjouter', async function (e) {
        e.preventDefault();

        try {
            const request = await axios.get('/admin/users/fournisseurs');
            const response = request.data;
            // console.log(response.fournisseurs)
            $("#modalAjouter #fournisseurs").empty();
            for (const fournisseur of response.fournisseurs) {
                const nomComplet = fournisseur.nom + ' ' + fournisseur.prenom;
                if(fournisseur.existsInUserTable == true){
                    $("#modalAjouter #fournisseurs").append($('<option>').text(nomComplet).attr('value', fournisseur.ice_o).attr("style", "width: 10px;").prop('disabled', true));
                }else if (fournisseur.ice_o == null){
                    $("#modalAjouter #fournisseurs").append($('<option>').text(nomComplet).attr('value', fournisseur.ice_o).attr("style", "width: 10px; background : red !important").prop('disabled', true));
                }else{
                    $("#modalAjouter #fournisseurs").append($('<option>').text(nomComplet).attr('value', fournisseur.ice_o).attr("style", "width: 10px;"));
                }
            }
            
            $("#modalAjouter").modal("show")
        } catch (error) {
            console.log(error, error.response);
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
              })
        }
    })

    $("#save").on("submit", async (e) => {
        e.preventDefault();
        const formData = new FormData($("#save")[0]);

        var codes = $('#modalAjouter #fournisseurs').val();
        formData.append("codes", codes)
        // console.log(selectedValues);
        try {
            const request = await axios.post('/admin/users/ajouter', formData);
            const response = request.data;
            // console.log(response.message);
            
            // for (const fournisseur of response.fournisseurs) {
            //     const nomComplet = fournisseur.nom + ' ' + fournisseur.prenom;
            //     $("#modalAjouter #fournisseurs").append($('<option>').text(nomComplet).attr('value', fournisseur.code).attr("style", "width: 10px;"));
            // }
            
            $("#modalAjouter").modal("hide")
            Toast.fire({
                icon: 'success',
                title: response.message
            })
            table.ajax.reload();
        } catch (error) {
            console.log(error, error.response);
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
              })
            // icon.addClass('fa-edit').removeClass("fa-spinner fa-spin ");
        }
    })

    $('body').on('click','#btnDevalider', async function (e) {
        e.preventDefault();
        const id = $(this).closest('tr').attr('id');
        // console.log(id);

        try {
            const request = await axios.get('/admin/users/devalider/'+id);
            const response = request.data;
            table.ajax.reload();
            Toast.fire({
                icon: 'success',
                title: response.message
            })
            
        } catch (error) {
            console.log(error, error.response);
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
              })
        }
    })

    $('body').on('click','#btnSupprimer',async function (e) {
        e.preventDefault();
        // const icon = $("#supprimer i");
        const id = $(this).closest('tr').attr('id');
        var res = confirm('Vous voulez vraiment supprimer cet utilisateur ?');
        if(res == 1){
            try {
                // icon.remove('fa-trash').addClass("fa-spinner fa-spin ");
                const request = await axios.post('/admin/users/delete/'+id);
                const response = request.data;
                console.log(response);
                // icon.addClass('fa-trash').removeClass("fa-spinner fa-spin ");
                table.ajax.reload();
                Toast.fire({
                    icon: 'success',
                    title: response
                })
            } catch (error) {
                console.log(error, error.response);
                const message = error.response.data;
                Toast.fire({
                    icon: 'error',
                    title: message,
                })
                // icon.addClass('fa-trash').removeClass("fa-spinner fa-spin ");
            }
        }
    })

    $('body').on('click','#btnReinitialiser',async function (e) {
        e.preventDefault();
        // const icon = $("#supprimer i");
        const id = $(this).closest('tr').attr('id');
        var res = confirm('Vous voulez vraiment reinitialiser le mot de pass de cet utilisateur ?');
        if(res == 1){
            try {
                // icon.remove('fa-trash').addClass("fa-spinner fa-spin ");
                const request = await axios.post('/admin/users/reset/'+id);
                const response = request.data;
                console.log(response);
                // icon.addClass('fa-trash').removeClass("fa-spinner fa-spin ");
                table.ajax.reload();
                Toast.fire({
                    icon: 'success',
                    title: response
                })
            } catch (error) {
                console.log(error, error.response);
                const message = error.response.data;
                Toast.fire({
                    icon: 'error',
                    title: message,
                })
                // icon.addClass('fa-trash').removeClass("fa-spinner fa-spin ");
            }
        }
    })
    
})