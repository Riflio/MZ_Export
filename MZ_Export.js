jQuery(document).ready(function($) {


	$('#exportaction').bind('click', function() {
		if ($(this).hasClass("start")) {
			exportAction({'step':0, 'total': 10 });
		}
		if ($(this).hasClass("download")) {
			var a = window.open($(this).attr("href"), "_blank", "");	
		}	
		return false;
	});

	function exportAction(exp) {

		$.ajax({
				url: ajaxurl,
				data: ({action : 'wp_mz_export', export: encodeURIComponent(JSON.stringify(exp))}),
				success: function(data) {					
					try {
  						var data=$.parseJSON(data); 
					}
					catch (err) {
						$("body").append(data);		
						alert("error!!");
						//TODO: При ошибке отключать кнопку экспорт или задавать новые параметры... а лучше нахуй обновить страницу			
						return;
					}			
					$("#vkwp").find('.percent').html(Math.round(data.step/data.total*100)+'%'+' '+data.step+' of '+data.total);
					$("#vkwp").find('.bar').css("width", Math.round(data.step/data.total*100)+'%');

					if (data.step>=data.total) {						
						$('#exportaction').removeClass("start");
						$('#exportaction').addClass("download");
						$('#exportaction').attr("href", data.resultpath);
						$('#exportaction').text("Download");
					}
				}	
		});
	}	


});