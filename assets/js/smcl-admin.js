(function(w,d,$){
	var SMCLadmin = {
		run: function(){
			SMCLadmin.app();
		},
		app: function(){
			$('.smcl-more').on('click', 'input:radio', function(){
				var $next = $(this).closest('tr').next('tr');
				if( $(this).hasClass('show-more') ){
					$next.stop().fadeIn(600);
				}else{
					$next.stop().fadeOut(300);
				};
			});
			$('.smcl-collapse').on('click', function(e){
				e.preventDefault();
				var id = $(this).attr('data-parent');
				$(id).slideToggle();
			});
		}
	}

	SMCLadmin.run();
}(window, document, window.jQuery))
