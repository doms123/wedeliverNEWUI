$(function() {
	var baseUrl = $(".kleanOnDemandScrubPage").attr("data-baseurl");
	var userId = $(".kleanOnDemandScrubPage").attr("data-userid");
	
	$(".scrubButton").click(function() {
		$("#myModal").modal('show'); 
	});

	$(".startScrub").click(function() {
		var appName = $(".appName").text();
		var authhash = $(".authhash").text();
		var tag = $(".tagCategory").val();

		if($(".checkEverything").is(":checked")) {
			tag = 0;
		}
		
		$("#myModal").modal("hide");
		$.ajax({
			type: 'GET',
			url: 'https://wdem.wedeliver.email/start_update.php?app=uir93022&auth=8a9ec2&tag='+tag+'&mode-full',
			success: function(data) {
				showUpdate();
				$(".scrubButton").attr('disabled', true);
			}
		});
	});

	localStorage.setItem("autoUpdateScrub", 1);
	showUpdate();
	function showUpdate() {
		console.log('update func')
		$.ajax({
			type: 'GET',
			url: 'https://wdem.wedeliver.email/show_update.php?app=uir93022&auth=8a9ec2',
			data: {},
			success: function(data) {
				setTimeout(function() {
				$(".cbMessage").text('');
				}, 4000);
				$(".printOutput").html(data);
				$(".scrubButton").attr('disabled', false);

				if(localStorage.getItem("autoUpdateScrub")) {
					setTimeout(showUpdate, 1000);
				}
			}
		})
	}

	$(".autoUpdate").click(function() {
		console.log('localStorage', localStorage.getItem("autoUpdateScrub"));
		if($(this).is(":checked")) {
			localStorage.setItem("autoUpdateScrub", 1);
			showUpdate();
		}else {
			localStorage.removeItem("autoUpdateScrub");
		}
	})

	$(".tagCategory").change(function() {
		$(".scrubButton").attr('disabled', false);
	});
	
	$(".checkEverything").click(function() {
		if($(this).is(":checked")) {
			$(".tagCategory").attr('disabled', true).attr('title', 'please uncheck "Everything" option to select tag category');
			getTagList();
			$(".scrubButton").attr('disabled', false);
		}else {
			$(".tagCategory").attr('disabled', false).removeAttr('title');

			if($(".tagCategory").val() === null) {
				$(".scrubButton").attr('disabled', true);
			}
		}
	})

	getTagList();
	function getTagList() {
		console.log('baseUrl', baseUrl)
		$.ajax({
			type: 'POST',
			url: baseUrl+'ept.php?op=ajaxcall',
			data: {
				action: 'getTagList',
				userId: userId
			},
			success: function(data) {
				var data = JSON.parse(data);
				var data = data.result;
				var html = '<option value="0" disabled selected>Please select a category</option>';
				for(var x = 0; x < data.length; x++) {
					html += '<option value="'+data[x].Id+'">'+data[x].CategoryName+': '+data[x].GroupName+'</option>';
				}

				$(".tagCategory").html(html);
			}
		});
	}
});