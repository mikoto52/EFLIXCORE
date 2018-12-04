(function($){
	window.Validator = {
		checkRequiredField: function(form, required)
		{
			$f = jQuery(form);
			for(field in required)
			{
				if(!$f.find("[name='"+field+"']").val())
				{
					if(required[field].custom_alert)
					{
						return {
							error: 1,
							message: required[field].custom_alert,
							field: field
						};
					}else{
						if(required[field].suffix1){
							return {
								error: 1,
								message: required[field].title + required[field].suffix1 + " 필수 입력사항입니다.",
								field: field
							};
						}else{
							return {
								error: 1,
								message: required[field].title + " 의 값은 필수 입력사항입니다.",
								field: field
							};
						}
					}
				}
			}
			return { error: 0 }
		},		
		validateForm: function(form, required) {
			return this.checkRequiredField(form, required);
		}
	};
	
})(jQuery);