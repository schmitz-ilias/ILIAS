il.MetaModalFormButtonHandler  = {
	init: function(ModalSaveButtonId) {
		var form =
				$("#" + ModalSaveButtonId)
					.closest(".il-modal-roundtrip")[0]
					.querySelector(".il-standard-form");
		var formSaveButtons = form.querySelectorAll(".il-standard-form-cmd");

		// hide the form's save buttons
		formSaveButtons.forEach(b => {b.style.display = 'none'});

		//hide the form's title
		form.querySelectorAll(".il-section-input-header").forEach(h => {
			h.querySelector("h2").style.display = 'none'
		});

		// make the modal's save button submit the form
		$("#" + ModalSaveButtonId).click(function() {form.requestSubmit()});
	},
};