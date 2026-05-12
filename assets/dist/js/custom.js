//  $(".tracking").fancybox({
// 	keyboard: false,
// 	protect: true,
// 	infobar: false,
// 	loop: false,
// 	arrows: false,
// 	buttons: [
// 		"zoom",
// 		"close"
// 	],
// 	wheel: false,
// 	// Optional: Set the image size to be responsive
// 	afterLoad: function(instance, current) {
// 		current.$content.css({
// 			'max-width': '100%',
// 			'max-height': '100%'  
// 		});
// 	}
// });


 function success_alert(msg)
	{
		Swal.fire({
			title: "Sucess",
			text: msg,
			icon: "success",
		}).then((result) => {
			if (result.isConfirmed) {
				location.reload();
			}
			else{
				location.reload();
			}
		});
	}
	function error_alert(msg)
	{
		Swal.fire({
			title: "Error",
			text: msg,
			icon: "error"
		});  
	}
	function warning_alert(msg)
	{
		Swal.fire({
			title: "warning",
			text: msg,
			icon: "warning"
		});
	}

	async function delete_confirmation(msg) {
		const result = await Swal.fire({
			title: "Are you sure?",
			text: msg,
			icon: "warning",
			showCancelButton: true,
			confirmButtonColor: "#d33",
			cancelButtonColor: "#3085d6",
			confirmButtonText: "Yes, delete it!",
			cancelButtonText: "Cancel"
		});

		return result.isConfirmed;
	}
	async function confirmation_alert(msg) {
		const result = await Swal.fire({
			title: "Are you sure?",
			text: msg,
			icon: "warning",
			showCancelButton: true,
			confirmButtonColor: "#fd7e14",  // Orange (warning)
			cancelButtonColor: "#3085d6",   // Blue
			confirmButtonText: "Yes",
			cancelButtonText: "Cancel"
		});

		return result.isConfirmed;
	}
