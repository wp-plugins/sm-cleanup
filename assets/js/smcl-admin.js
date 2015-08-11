(function(w,d,$){
	var SMCLadmin = {
		run: function(){
			SMCLadmin.app();
		},
		app: function(){
			$('.smcl-more').on('click', 'input:radio', function(){
				var $next = $(this).closest('tr').next('tr');
				if( $(this).hasClass('show-more') ){
					$next.fadeIn(600);
				}else{
					$next.fadeOut(300);
				};
			});
		}
	}

	SMCLadmin.run();
}(window, document, window.jQuery))
