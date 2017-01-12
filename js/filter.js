$(function() {
	
	if (localStorage.clientName == undefined) {
		
		window.location.href = "restricted-access.php";
		
	} else {
		
		$.ajax({
			url: 'filter.php',
			type: 'post',
			data: { clientName: localStorage.clientName },
			success: function(data, status) {
				
				if (data == 'denied') window.location.href = "restricted-access.php";
				
			},
			error: function(xhr, status, err) {
				
			}
		});
		
	}
	
});