$(document).ready(function() {
	$('.summernote').each(function() {
		var element = this;
		
		$(element).summernote({
			disableDragAndDrop: true,
			height: 300,
			emptyPara: '',
			toolbar: [
				['style', ['style']],
				['font', ['bold', 'underline', 'clear']],
				['fontname', ['fontname']],
				['color', ['color']],
				['para', ['ul', 'ol', 'paragraph']],
				['table', ['table']],
				['insert', ['link', 'picture', 'video']],
				['view', ['fullscreen', 'codeview', 'help']]
			]
		});
	});
	
});
