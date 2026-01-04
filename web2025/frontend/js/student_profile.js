// Αποθήκευση προφίλ
swalConfirmation = function(message, onConfirm){
    Swal.fire({
        title: 'Επιβεβαίωση',
        text: message,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ναι',
        cancelButtonText: 'Όχι'
    }).then((result) => {
        if (result.isConfirmed) onConfirm();
    });
}
swalMsg = function(msg) {
    Swal.fire({
        icon: 'warning',
        title: 'Προσοχή',
        text: msg,
        confirmButtonText: 'OK'
    });
}
swalError = function(message){
    Swal.fire({
        title: 'Σφάλμα',
        text: message,
        icon: 'error',
        confirmButtonText: 'OK'
    });
}
function saveProfile(){


    var first = $("#first_name").val();
    var last = $("#last_name").val();
    var email = $("#email").val();
    var addr = $("#address").val();
    var mobile = $("#phone_mobile").val();
    var home = $("#phone_home").val();






    console.log("first_name:", document.getElementById("first_name"));
    console.log("last_name:", document.getElementById("last_name"));
    console.log("email:", document.getElementById("email"));
    console.log("address:", document.getElementById("address"));
    console.log("phone_mobile:", document.getElementById("phone_mobile"));
    console.log("phone_home:", document.getElementById("phone_home"));

    if(first === ""){
        swalMsg("Δώστε όνομα.");
        return;
    }
    if(last === ""){
        swalMsg("Δώστε επώνυμο.");
        return;
    }

    $.ajax({
        url: "../backend/students/update_profile.php",
        type: "POST",
        dataType: "json",
        data: {
            first_name: first,
            last_name: last,
            email: email,
            address: addr,
            phone_mobile: mobile,
            phone_home: home
        },
        success: function(response){
            try {
                let data = response;

                if(data.success){
                    swalSuccess("Τα στοιχεία ενημερώθηκαν.");
                } else {
                    swalError("Σφάλμα: " + data.message);
                }
            } catch(e){
                console.log("Invalid JSON:", response);
                swalError("Σφάλμα στον server.");
            }
        }
    });
}


// Φόρτωμα προφίλ
$(document).ready(function(){

    $.ajax({
        url: "../backend/students/get_profile.php",
        type: "GET",
        dataType: "json",
        success: function(response){

            let p = response;

            $("#first_name").val(p.first_name);
            $("#last_name").val(p.last_name);
            $("#email").val(p.email);
            $("#address").val(p.address);
            $("#phone_mobile").val(p.phone_mobile);
            $("#phone_home").val(p.phone_home);
        }
    });

    $("#saveBtn").click(saveProfile);
});
