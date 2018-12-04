var working = false;
$('.login').on('submit', function(e) {
  e.preventDefault();
  if (working) return;
  working = true;
  var $this = $(this),
    $state = $this.find('button > .state');
  $this.addClass('loading');
  $state.html('Authenticating');
  setTimeout(function() {
    $this.addClass('ok');
    $state.html('Welcome back!');
    setTimeout(function() {
      $state.html('Log in');
      $this.removeClass('ok loading');
      working = false;
    }, 4000);
  }, 3000);
});

(function($){
	$(document).ready(function(){
		$('.login-form input').keypress(function(k){
			if(k.keyCode == 13) {
				$(".login-form #submit-btn").click();
			}
		});
		$('.login-form #submit-btn').click(function(){
			form = $('.login-form');
			action = $(form).data("action");
			method = $(form).data("method");
			
			required = {
				'user_id': {
					'title': '아이디',
					'suffix1': '는',
					'field': 'user_id'
				},
				'user_password': {
					'title': '비밀번호',
					'suffix1': '는',
					'field': 'user_password'
				}
			};
			chField = Validator.validateForm(form, required);
			if(chField.error != 0) {
				alert(chField.message);
				$("[name='"+chField.field+"']").focus();
				return false;
			}
			
			$.ajax({
				async: true,
				url: action, 
				method: method,
				headers: {'Accept': 'application/json'},
				data: $(form).serialize(),
				complete: function(args) {
					if(args.status == 200) {
						try {
							output = JSON.parse(args.responseText);
							if(output.code == 0) {
								if(output.redirect_url) 
									window.location = output.redirect_url;
							} else {
								alert(output.message);
								return false;
							}
						} catch(e) {
							alert("Error while parsing JSON:\n" + e.message);
						}
					} else {
						alert("요청을 처리하는 중 오류가 발생하였습니다.");
					}
				}
			});
			
			return false;
		});
	});
	
})(jQuery);