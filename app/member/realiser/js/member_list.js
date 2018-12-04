$(document).ready(function(){
	Realiser.on("dt_member_list_grid", "drawCallback", function(){
		$('[rl-action="edit"]').on('click', function(e){ 
			window.location = './manage/' + $(this).attr('rl-srl');
		});
	});
});