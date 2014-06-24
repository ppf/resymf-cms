$(function(){
	
	var dropbox = $('#dropbox'),
		message = $('.message', dropbox),
        fileFieldName = $('input[name="fileFieldName"]').val(),
        globalFile = '';
	
	dropbox.filedrop({
		// The name of the $_FILES entry:
		paramname:'file',
		
		maxfiles: 20,
    	maxfilesize: 40,
		url: '/app_dev.php/admin/upload-file',
		
		uploadFinished:function(i,file,response) {
            $('.files').append('<h4>'+response.fileName+'</h4><input type="hidden" value="'+response.fileName+'" name="'+fileFieldName +'"/>');
			$.data(file).addClass('done');
			// response is the JSON object that post_file.php returns
		},
		
    	error: function(err, file) {
			switch(err) {
				case 'BrowserNotSupported':
					showMessage('Your browser does not support HTML5 file uploads!');
					break;
				case 'TooManyFiles':
					alert('Too many files! Please select 5 at most! (configurable)');
					break;
				case 'FileTooLarge':
					alert(file.name+' is too large! Please upload files up to 2mb (configurable).');
					break;
				default:
					break;
			}
		},
		
		// Called before each upload is started
		beforeEach: function(file){
            globalFile = file;
//			if(!file.size > 20000000) {
//				alert('Files over 20MB not allowed');
//
//				// Returning false will cause the
//				// file to be rejected
//				return false;
//			}
		},
		
		uploadStarted:function(i, file, len){
			createImage(file);
		},
		
		progressUpdated: function(i, file, progress) {
			$.data(file).find('.progress').width(progress);
		}
    	 
	});
	
	var template = '<div class="preview">'+
						'<span class="imageHolder">'+
							'<img />'+
							'<span class="uploaded"></span>'+
						'</span>'+
						'<div class="progressHolder">'+
							'<div class="progress"></div>'+
						'</div>'+
					'</div>'; 
	
	
	function createImage(file){

		var preview = $(template), 
			image = $('img', preview);
			
		var reader = new FileReader();
		
		image.width = 100;
		image.height = 100;
		
		reader.onload = function(e){
			console.log(globalFile);
			// e.target.result holds the DataURL which
			// can be used as a source of the image:
            if(globalFile.type.match(/^image\//)) {
                image.attr('src', e.target.result);
            } else 	if(globalFile.type.match(/pdf/)){
                image.attr('src','/bundles/resymfcms/img/pdf-icon.png');
            } else {
                image.attr('src','/bundles/resymfcms/img/file.png');
            }
		};
		
		// Reading the file as a DataURL. When finished,
		// this will trigger the onload function above:
		reader.readAsDataURL(file);
		
		message.hide();
		preview.appendTo(dropbox);
		
		// Associating a preview container
		// with the file, using jQuery's $.data():
		
		$.data(file,preview);
	}

	function showMessage(msg){
		message.html(msg);
	}

});