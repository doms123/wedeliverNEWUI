$(function() {
	var baseUrl = $(".kleanApiConfigPage").attr("data-baseurl");

	// ##### TOGGLE SWITCH ON AND OFF #####
	$('.toggleConfig').click(function() {
		var toggleConfig = $('.toggleConfig:checked').val();
		var apiKey = $(".txtApiKey").val().trim();

		if(toggleConfig == 'on') {
			$(".switchStatus").text('ON');
			
			if(apiKey.length) {
				var tagCategory = $(".tagCategory").val();
				var otherTagCat = $(".otherTagCat").val().trim();
				if(tagCategory == 0 && otherTagCat == '') {
					$(this).prop('checked', false);
					$(".switchStatus").text('OFF');
					var errorMsg = "<p class='errorMsg'>Tag category is required.</p>";
					$(".loaderWrap").append(errorMsg);

					setTimeout(function() {
						$(".loaderWrap").find(".errorMsg").remove();
					}, 4000);
				}else {
					var loader = "<p class='webLoading'><span class='loader'></span> Validating your API Key, please wait...</p>";
					$(".loaderWrap").append(loader);

					// ##### MAKE SURE TO CHECK THE API KEY IF VALID #####
					$(this).attr('disabled', true);
					validateAPIKeyUsingSwitch();
				}
				
			}else {
				$(".switchStatus").text('OFF');
				var errorMsg = "<p class='errorMsg'>Switching on failed! API key is required.</p>";
				if(!$(".txtApiKey.error").length) {
					$(".loaderWrap").append(errorMsg);
					$(".txtApiKey").addClass('error');

					setTimeout(function() {
						$(".loaderWrap").find(".errorMsg").remove();
						$(".txtApiKey").removeClass('error');
					}, 4000);
				}
				$(this).prop('checked', false);
			}
		}else {
			disabledHook();
		}

	});

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
			$(this).prop('checked', false);
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
					deleteRESThook();
				}
			}
		});
	}

	function validateAPIKeyUsingSwitch() {
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
		  					<p>API Key was saved</p>
						</div>
					`;

					$(".resMsgWrap").append(res);

					setTimeout(function() {
						$(".resMsgWrap .apiValidMsg").remove();
					}, 4000);
					$(".txtApiKey").removeClass("error");
					// ##### SETUP RESTHOOK #####
					setupRESThook();
				}else {
					// ##### REMOVE DISABLED ATTRIBUTE WHEN AJAX REQUEST WAS COMPLETED #####
					$(".toggleConfig").removeAttr("disabled");
					var errorMsg = "<p class='errorMsg'>Save API failed, "+data.message+"</p>";
					$(".loaderWrap").append(errorMsg);
					$(".txtApiKey").addClass("error");
					$(".toggleConfig").prop('checked', false);
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
					$(".txtApiKey").removeClass("error");
					addTagCategory('saveSettings');

					setTimeout(function() {
						$(".resMsgWrap .apiValidMsg").remove();
					}, 4000);
				}else {
					// ##### REMOVE DISABLED ATTRIBUTE WHEN AJAX REQUEST WAS COMPLETED #####
					$(".toggleConfig").removeAttr("disabled");
					var errorMsg = "<p class='errorMsg'>Save API failed, "+data.message+"</p>";
					$(".txtApiKey").addClass("error");
					$(".loaderWrap").append(errorMsg);
					$(".toggleConfig").prop('checked', false);
					setTimeout(function() {
						$(".loaderWrap .errorMsg").remove();
					}, 4000);
				}
			}
		});
	}

	function addTagCategory(type) {
		var userId 			= $(".userId").val();
		var tagCategory 	= $(".tagCategory").val();
		var otherTagCat 	= $(".otherTagCat").val();

		$.ajax({
			type: 'POST',
			url: baseUrl+'ept.php?op=ajaxcall',
			data: {
				action: 'addTagCategory',
				userId: userId,
				tagCategory: tagCategory,
				otherTagCat: otherTagCat,
			}, 
			success: function(data) {
				var data = JSON.parse(data);
				console.log(data);
				$(".otherTagId").val(data.tagCategoryId);

				if(type == 'saveSettings') {
					saveTagCategory();
				}else {
					saveAPIkey();	
				}
				

				allTagCategories();
				$(".otherTagCat").val('');
			}
		});
	}

	// ##### SETUP RESTHOOK #####
	function setupRESThook() {
		var appName = $(".appName").text();
		var authhash = $(".appName").text();
		var userId = $(".userId").val();

		var loader = "<p class='restLoading'><span class='loader'></span> Setting up the RESThook please wait...</p>";
		$(".loaderWrap").append(loader);

		$.ajax({
			type: 'POST',
			url: baseUrl+'ept.php?op=ajaxcall',
			data: {
				action: 'setupRESThook',
				appName: appName,
				authhash: authhash,
				userId: userId,
			},
			success: function(data) {
				var data = JSON.parse(data);
				if(data.success) {

					// ##### SETUP NEXT THE WEBHOOK #####
					// setupWebHook();

					var res = `
						<div class='w3-green restCbMsg configMessage'>
		  					<p>RESThook was set up.</p>
						</div>
					`;

					$(".resMsgWrap").append(res);

					addTagCategory('setupRestHook');
				}else {
					$(".successConfigSave").fadeIn().find('p').text('Error setting up the RESThook.');
				}

				setTimeout(function() {
					$(".successConfigSave, .restCbMsg").remove();
				}, 4000);
			}
		});
	}

	// ##### SAVE API KEY  #####
	function saveAPIkey() {
		var userId = $(".userId").val();
		var apiKey = $(".txtApiKey").val();
		var tagCategoryId = $(".tagCategory").val();
		var otherTagId = $(".otherTagId").val();
		var otherTagCat = $(".otherTagCat").val();
		var tagPrefix = $(".tagPrefix").val();
		var tagCatId = 0;
		if(otherTagCat != '') {
			tagCatId = otherTagId;
		}else {
			tagCatId = tagCategoryId;
		}


		$.ajax({
			type: 'POST',
			url: baseUrl+'ept.php?op=ajaxcall',
			data: {
				action: 'configSave',
				userId: userId,
				apiKey: apiKey,
				tagCatId: tagCatId,
				tagPrefix: tagPrefix
			}, 
			success: function(data) {
				var data = JSON.parse(data);

				if(data.success) {
					$(".toggleConfig").removeAttr("disabled");
					$(".loaderWrap .restLoading").remove();

				}else {
					$(".successConfigSave").fadeIn().find('p').text('Error saving API key.');
				}
			}
		});
	}

	function saveTagCategory() {
		var userId = $(".userId").val();
		var apiKey = $(".txtApiKey").val();
		var tagCategoryId = $(".tagCategory").val();
		var otherTagId = $(".otherTagId").val();
		var otherTagCat = $(".otherTagCat").val();
		var tagPrefix = $(".tagPrefix").val();
		var tagCatId = 0;

		if(otherTagCat != '') {
			tagCatId = otherTagId;
		}else {
			tagCatId = tagCategoryId;
		}

		$.ajax({
			type: 'POST',
			url: baseUrl+'ept.php?op=ajaxcall',
			data: {
				action: 'saveTagCategory',
				userId: userId,
				apiKey: apiKey,
				tagCatId: tagCatId,
				tagPrefix: tagPrefix
			}, 
			success: function(data) {
				var data = JSON.parse(data);
			}
		});
	}

	function deleteRESThook() {
		var userId = $(".userId").val();

		var loader = "<p class='deleteRestLoading'><span class='loader'></span> Deleting RESThook please wait...</p>";
		$(".loaderWrap").append(loader);

		$.ajax({
			type: 'POST',
			url: baseUrl+'ept.php?op=ajaxcall',
			data: {
				action: 'deleteRestHook',
				userId: userId
			},
			success: function(data) {
				var data = JSON.parse(data);
				if(data.success) {
					$(".toggleConfig").removeAttr("disabled");
					$(".loaderWrap .deleteRestLoading").remove();

					var res = `
					<div class='w3-green delWebCbMsg configMessage'>
	  					<p>RESThook was deleted.</p>
					</div>
					`;

					$(".resMsgWrap").append(res);

					setTimeout(function() {
						$(".resMsgWrap .delWebCbMsg").remove();
					}, 4000);
				}else {
					$(".successConfigSave").fadeIn().find('p').text('Error deleting RESThook.');
					$(".toggleConfig").removeAttr("disabled");
				}
			}
		});
	}

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

	$(".tagCategory").change(function() {
		var val = $(this).val();
		if(val > 0) {
			$(".otherTagCat").attr('disabled', true).val('');
		}else {
			$(".otherTagCat").attr('disabled', false).val('');
		}
	});

	allTagCategories();
	function allTagCategories() {
		var userId = $(".userId").val();
		// tagCategory
		$.ajax({
			type: 'POST',
			url: baseUrl+'ept.php?op=ajaxcall',
			data: {
				action: 'allTagCategories',
				userId: userId
			},
			success: function(data) {
				var data = JSON.parse(data);
				var active = data.active;
				var data = data.result;
				var html = "<option value='0'>Please select a category</option>";
				console.log('data', data.length);
				for(var x = 0; x < data.length; x++) {
					html += '<option value="'+data[x].Id+'">'+data[x].CategoryName+'</option>';
				}

				$(".tagCategory").html(html);
				$('.tagCategory option[value="'+active+'"]').attr('selected', 'selected');
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
				console.log('data', data);
				if(data.tagPrefix != null && data.tagPrefix != '') {
					$(".tagPrefix").val(data.tagPrefix);
				}
				
			}
		});
	}
});
