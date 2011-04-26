jQuery(function(){
	jQuery('#nav-menu').change(function(){
		var id = jQuery("#nav-menu option:selected").val();
		jQuery.ajax({
			type: "POST",
			url: "/wp-admin/admin-ajax.php",
			data: 'action=vsm&operation=get-menu&menu=' + id,
		   dataType: 'json',	   
		   success: function(data){
				jQuery("#nav-menu-items").html(data.json_menu);
		   },
			error: function(XMLHttpRequest, textStatus, errorThrown){
				alert(textStatus);		
			}	   
		 });			
	});		
});
