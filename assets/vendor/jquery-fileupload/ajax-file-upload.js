jQuery(function($) {
	$('.wp-job-manager-file-upload').each(function(){
		var uploadCount = 0;

		$(this).fileupload({
			dataType: 'json',
			dropZone: $(this),
			url: CASE27.ajax_url + '?action=mylisting_upload_file&security=' + CASE27.ajax_nonce,
			maxNumberOfFiles: 1,
			formData: {
				script: true
			},
			add: function (e, data) {
				var $file_field     = $(this);
				var $form           = $file_field.closest('form');
				var $uploaded_files = $file_field.parents('.file-upload-field').find('.job-manager-uploaded-files');

				var globalMaxCount = parseInt($file_field.data('max_count'), 10) || 0;
				var packageLimits = $file_field.data('package-limits') || [];
				var fieldConfig   = $file_field.data('all-size-configs') || {};
				var listingPackageId = $form.find('input[name="listing_package_id"]').val() || '';
				var listingPackage    = $form.find('input[name="listing_package"]').val() || '';

				var currentPackage = listingPackageId || listingPackage;

				var effectiveLimit = 0;
				var effectiveSizeLimit = 0;
				if (currentPackage) {
					var matchedRule = null;
					for (var i = 0; i < packageLimits.length; i++) {
						var rule = packageLimits[i];
						if (rule.package && rule.package.toString() === currentPackage.toString()) {
							matchedRule = rule;
							break;
						}
					}
					if (matchedRule) {
						effectiveLimit = parseInt(matchedRule.limit, 10) || 0;
						effectiveSizeLimit = parseInt(matchedRule.size_limit_kb, 10) || 0;
					} else {
						effectiveLimit = globalMaxCount;
					}
				} else {
					effectiveLimit = globalMaxCount;
				}

				var total_file_count = $uploaded_files.find('.uploaded-file').length + data.originalFiles.length;
				if (effectiveLimit > 0 && total_file_count > effectiveLimit) {
					window.alert(
						CASE27.l10n.file_limit_exceeded.replace('%d', effectiveLimit)
						);
					return;
				}

				// Validate file type
				var allowed_types = $file_field.data('file_types');
				if ( allowed_types ) {
					var acceptFileTypes = new RegExp( '(\.|\/)(' + allowed_types + ')$', 'i' );
					if ( data.files[0].name.length && ! acceptFileTypes.test( data.files[0].name ) ) {
						window.alert( CASE27.l10n.invalid_file_type + ' ' + allowed_types );
						return;
					}
				}

				// Validate file size
				var serverMaxKB = parseInt(fieldConfig.server_max_kb, 10) || 2097152;
				var defaultLimitKB = parseInt(fieldConfig.default_limit_kb, 10) || 0;

				var allowed_size_kb = effectiveSizeLimit || defaultLimitKB || serverMaxKB;
				var file_size_kb = data.files[0].size / 1024;

				if ( file_size_kb > allowed_size_kb ) {
					window.alert(
						CASE27.l10n.file_size_limit
						.replace('%s', data.files[0].name)
						.replace('%d', allowed_size_kb + 'KB')
						);
					return;
				}

				// validation complete, proceed with the upload
				uploadCount++;
				$form.find(':input[type="submit"]').attr( 'disabled', 'disabled' );

				data.context = $('<progress value="" max="100"></progress>').appendTo( $uploaded_files );
				data.submit();
			},
			progress: function (e, data) {
				var progress        = parseInt(data.loaded / data.total * 100, 10);
				data.context.val( progress );
			},
			fail: function (e, data) {
				var $file_field     = $( this );
				var $form           = $file_field.closest( 'form' );

				if ( data.errorThrown ) {
					window.alert( data.errorThrown );
				}

				data.context.remove();
				uploadCount--;

				if (uploadCount <= 0) {
					$form.find(':input[type="submit"]').removeAttr( 'disabled' );
				}
			},
			done: function (e, data) {
				var $file_field     = $( this );
				var $form           = $file_field.closest( 'form' );
				var $uploaded_files = $file_field.parents('.file-upload-field').find('.job-manager-uploaded-files');
				var multiple        = $file_field.attr( 'multiple' ) ? 1 : 0;
				var image_types     = [ 'jpg', 'gif', 'png', 'jpeg', 'jpe', 'webp' ];

				data.context.remove();
				uploadCount--;

				// Handle JSON errors when success is false
				if( typeof data.result.success !== 'undefined' && ! data.result.success ){
					window.alert( data.result.data );
				}

				$.each(data.result.files, function(index, file) {
					if ( file.error ) {
						window.alert( file.error );
					} else {
						var html;
						if ( $.inArray( file.extension, image_types ) >= 0 ) {
							html = $.parseHTML( CASE27.js_field_html_img );
							$( html ).find('.job-manager-uploaded-file-preview img').attr( 'src', file.attachment_url );
						} else {
							html = $.parseHTML( CASE27.js_field_html );
							$( html ).find('.job-manager-uploaded-file-name code').text( file.name );
						}

						$( html ).find('.input-text').val( file.encoded_guid );
						$( html ).find('.input-text').attr( 'name', 'current_' + $file_field.attr( 'name' ) );

						if ( multiple ) {
							$uploaded_files.append( html );
						} else {
							$uploaded_files.html( html );
						}
					}
				});

				if (uploadCount <= 0) {
					$form.find(':input[type="submit"]').removeAttr( 'disabled' );
				}
			}
		});
	});
});