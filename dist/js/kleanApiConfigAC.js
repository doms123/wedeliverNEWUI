$(function() {
	var baseUrl = $(".kleanApiConfigPage").attr("data-baseurl");
	$(".hiddenAC").hide();

	configButtonState();
	function configButtonState() {
		var userId = $(".userId").val();

		$.ajax({
			type: 'POST',
			url: baseUrl+'ept.php?op=ajaxcall',
			data: {
				action: 'configButtonState',
				userId: userId
			},
			success: function(data) {
				var data = JSON.parse(data);
				console.log('data', data);

				if(parseInt(data.isActive) != 0) {
					// Switch on
					$(".toggleConfig").attr('checked','checked');
					$(".switchStatus").text('ON');
				}else {
					// Switch off
					$(".toggleConfig").removeAttr('checked');
				}

				setTimeout(function() {
					$(".pageLoader").hide();
					$(".apiConfig").show();
				}, 400);
			}
		});
	}

	$(".buttonSaveApiKey").click(function() {
		var apiKey = $(".txtApiKey").val().trim();
		var loader = "<p class='webLoading'><span class='loader'></span> Validating your API Key, please wait...</p>";

		if(apiKey.length) {
			$(".loaderWrap").append(loader);
			validateAPIKey();

			return false;
		}else {
			$(".switchStatus").text('OFF');
			var errorMsg = "<p class='errorMsg'>Failed to save, API key is required.</p>";
			if(!$(".txtApiKey.error").length) {
				$(".loaderWrap").append(errorMsg);
				$(".txtApiKey").addClass('error');

				setTimeout(function() {
					$(".loaderWrap").find(".errorMsg").remove();
					$(".txtApiKey").removeClass('error');
				}, 4000);
			}

			$(this).removeAttr("checked");
		}
	});

	// ##### TOGGLE SWITCH ON AND OFF #####
	$('.toggleConfig').click(function() {
		var toggleConfig = $('.toggleConfig:checked').val();
		var apiKey = $(".txtApiKey").val().trim();
		console.log('apiKey', apiKey)
		if(toggleConfig == 'on') {
			$(".switchStatus").text('ON');
			if(apiKey.length) {
				var loader = "<p class='webLoading'><span class='loader'></span> Validating your API Key, please wait...</p>";
				$(".loaderWrap").append(loader);

				// ##### MAKE SURE TO CHECK THE API KEY IF VALID #####
				$(this).attr('disabled', true);
				validateAPIKeyUsingSwitch();
			}else {
				$(".switchStatus").text('OFF');
				var errorMsg = "<p class='errorMsg'>Switching on failed! API key is required.</p>";
				if(!$(".txtApiKey.error").length) {
					$(".loaderWrap").append(errorMsg);
					$(".txtApiKey").addClass('error');

					setTimeout(function() {
						var errorMsg = "<p class='errorMsg'>Switching on failed! API key is required.</p>";
						$(".loaderWrap").find(".errorMsg").remove();
						$(".txtApiKey").removeClass('error');
					}, 4000);
				}

				$(this).removeAttr("checked");
			}
		}else {
			disabledHook();
		}
	});

	function disabledHook() {
		var userId = $(".userId").val();
		$(".toggleConfig").attr("disabled", true);
		$(".switchStatus").text('OFF');
		$.ajax({
			type: 'POST',
			url: baseUrl+'ept.php?op=ajaxcall',
			data: {
				action: 'disabledHook',
				userId: userId
			},
			success: function(data) {
				var data = JSON.parse(data);

				if(data.success) {
					deleteWebhook();
				}
			}
		});
	}

	function validateAPIKeyUsingSwitch() {
		var apiKey = $(".txtApiKey").val();
		var userId = $(".userId").val();
		console.log('userIduserId', userId)

		$.ajax({
			type: 'POST',
			url: baseUrl+'ept.php?op=ajaxcall',
			data: {
				action: 'validateAPIKey',
				apiKey: apiKey,
				userId: userId
			},
			success: function(data) {
				var data = JSON.parse(data);
				$(".loaderWrap .webLoading").remove();
				console.log('data', data)
				if(data.success) {
					var res = `
						<div class='w3-green apiValidMsg configMessage'>
		  					<p>API Key was saved</p>
						</div>
					`;

					$(".resMsgWrap").append(res);

					setTimeout(function() {
						$(".resMsgWrap .apiValidMsg").remove();
					}, 4000);

					// ##### SETUP WEBHOOK #####
					setupWebHook();
				}else {
					// ##### REMOVE DISABLED ATTRIBUTE WHEN AJAX REQUEST WAS COMPLETED #####
					$(".toggleConfig").removeAttr("disabled");
					var errorMsg = "<p class='errorMsg'>Save API failed, "+data.message+"</p>";
					$(".loaderWrap").append(errorMsg);
					$(".toggleConfig").removeAttr("checked");

					setTimeout(function() {
						$(".loaderWrap .errorMsg").remove();
					}, 4000);
				}
			}
		});
	}

	function validateAPIKey() {
		var apiKey = $(".txtApiKey").val();
		var userId = $(".userId").val();

		$.ajax({
			type: 'POST',
			url: baseUrl+'ept.php?op=ajaxcall',
			data: {
				action: 'validateAPIKey',
				apiKey: apiKey,
				userId: userId
			},
			success: function(data) {
				var data = JSON.parse(data);
				$(".loaderWrap .webLoading").remove();

				if(data.success) {
					var res = `
						<div class='w3-green apiValidMsg configMessage'>
		  					<p>Settings was saved</p>
						</div>
					`;

					$(".resMsgWrap").append(res);

					saveTagPrefix();

					setTimeout(function() {
						$(".resMsgWrap .apiValidMsg").remove();
					}, 4000);
				}else {
					// ##### REMOVE DISABLED ATTRIBUTE WHEN AJAX REQUEST WAS COMPLETED #####
					$(".toggleConfig").removeAttr("disabled");
					var errorMsg = "<p class='errorMsg'>Save API failed, "+data.message+"</p>";
					$(".loaderWrap").append(errorMsg);
					$(".toggleConfig").removeAttr("checked");

					setTimeout(function() {
						$(".loaderWrap .errorMsg").remove();
					}, 4000);
				}
			}
		});
	}

	function saveTagPrefix() {
		var userId = $(".userId").val();
		var apiKey = $(".txtApiKey").val();
		var tagPrefix = $(".tagPrefix").val();
	
		$.ajax({
			type: 'POST',
			url: baseUrl+'ept.php?op=ajaxcall',
			data: {
				action: 'saveTagPrefix',
				userId: userId,
				apiKey: apiKey,
				tagPrefix: tagPrefix
			}, 
			success: function(data) {
				var data = JSON.parse(data);
			}
		});
	}

	function setupWebHook() {
		var appName = $(".appName").text();
		var authhash = $(".authhash").text();
		var userId = $(".userId").val();
		console.log('userId', userId)
		var loader = "<p class='webLoading'><span class='loader'></span> Setting up the Webhook please wait...</p>";
		$(".loaderWrap").append(loader);

		$.ajax({
			type: 'POST',
			url: baseUrl+'ept.php?op=ajaxcall',
			data: {
				action: 'setupWebHook',
				userId: userId,
				appName: appName,
				authhash: authhash
			},
			success: function(data) {
				var data = JSON.parse(data);
				// ##### REMOVE LOADING SPINNER #####
				$(".webLoading").remove();

				if(data.success) {
					var res = `
						<div class='w3-green webCbMsg configMessage'>
		  					<p>Webhook was set up.</p>
						</div>
					`;

					$(".resMsgWrap").append(res);

					saveAPIkey();
				}else {
					$(".successConfigSave").fadeIn().find('p').text('Error setting up Webhook.');
				}

				setTimeout(function() {
					$(".successConfigSave, .webCbMsg").remove();
				}, 4000);
			}
		});
	}		

	function saveAPIkey() {
		var userId = $(".userId").val();
		var apiKey = $(".txtApiKey").val();
		var platform = $(".platform").text();
		var tagPrefix = $(".tagPrefix").val();
		var tagName = '';

		$.ajax({
			type: 'POST',
			url: baseUrl+'ept.php?op=ajaxcall',
			data: {
				action: 'configSave',
				userId: userId,
				apiKey: apiKey,
				tagPrefix: tagPrefix
			}, 
			success: function(data) {
				var data = JSON.parse(data);

				if(data.success) {
					$(".toggleConfig").removeAttr("disabled");
				}else {
					$(".successConfigSave").fadeIn().find('p').text('Error saving API key.');
				}
			}
		});
	}

	function deleteWebhook() {
		var userId = $(".userId").val();

		$.ajax({
			type: 'POST',
			url: baseUrl+'ept.php?op=ajaxcall',
			data: {
				action: 'deleteWebHook',
				userId: userId
			},
			success: function(data) {
				$(".toggleConfig").removeAttr("disabled");
				$(".loaderWrap .deleteRestLoading").remove();

				var res = `
				<div class='w3-green delWebCbMsg configMessage'>
  					<p>Webhook was deleted.</p>
				</div>
				`;

				$(".resMsgWrap").append(res);

				setTimeout(function() {
					$(".resMsgWrap .delWebCbMsg").remove();
				}, 4000);
			}
		});
	}


	getTagPrefix();
	function getTagPrefix() {
		var userId = $(".userId").val();
		$.ajax({
			type: 'POST',
			url: baseUrl+'ept.php?op=ajaxcall',
			data: {
				action: 'getTagPrefix',
				userId: userId
			},
			success: function(data) {
				var data = JSON.parse(data);
				if(data.tagPrefix != null && data.tagPrefix != '') {
					$(".tagPrefix").val(data.tagPrefix);
				}
				
			}
		});
	}
});