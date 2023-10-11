$ = jQuery;

$(document).ready(function() {
    console.log("Bonjour");
    $("#form_reservation").hide();
    $("#reserve_these").click(function() {
        $("#form_reservation").toggle();
        $('html, body').animate({
            scrollTop: $("#form_reservation").offset().top
        }, 1000);
    });
});

// Toggle the forms add/verify adherents
function toggleMemberForm(isAdherent) {
    if (isAdherent) {
        $("#formVerifyMember").show();
        $("#formAddMember").hide();
    } else {
        $("#formVerifyMember").hide();
        $("#formAddMember").show();
    }
}
// Toggle the forms add/verify user
function toggleUserForm(iam_adherent) {
    if (iam_adherent) {
        $("#current_user_verif_is_member").show();
        $("#current_user_add_member").hide();
    } else {
        $("#current_user_verif_is_member").hide();
        $("#current_user_add_member").show();
    }
}


