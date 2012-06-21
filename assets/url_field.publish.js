(function($, undefined){

	$(document).ready(function(){
		$(".field-url").each(function(){
			var $self = $(this);
			var $radios = $self.find('.url_type label');

			if( $radios.length > 0 ){
				var $values = $self.find('.value');

				$radios.on('click', function(){
					var target = $(this).find('input').data('target');
					$values.hide().filter('.'+target).show();
				});
			}
		});
	});

})(jQuery);
