var menu_item_id = 0;

function getPagesList(obj) {
	var menu = jQuery("#nav-menu option:selected").val();
	var str = jQuery(obj).attr('id');
	var parts = str.split('|');
	jQuery.ajax({
		type: "POST",
		url: "/wp-admin/admin-ajax.php",
		data: 'action=vsm&operation=get-pages-list&menu=' + menu +
			  '&menu-item-id=' + parts[0] +
			  '&menu-item-object-id=' + parts[1],
	   dataType: 'json',	   
	   success: function(data){
			menu_item_id = data.menu_item_id;
			jQuery('#pages-list-dialog-content').html(data.pages);
			jQuery('#pages-list-dialog').dialog( "option", "title", data.title );
			jQuery('#pages-list-dialog').dialog('open');
	   },
		error: function(XMLHttpRequest, textStatus, errorThrown){
			alert(textStatus);
		}	   
	 });			
	return false;
}

function deletePage(obj) {
	if (confirm("Are you sure to delete the page and supages?")) {
		var id = jQuery(obj).attr('id');
		jQuery.ajax({
			type: "POST",
			url: "/wp-admin/admin-ajax.php",
			data: 'action=vsm&operation=delete-page&post-id=' + id,
		   dataType: 'json',	   
		   success: function(data){
				jQuery("#nav-page-items").html(data.json_page);
				jQuery('#nav-page-items .button-secondary').click(function(){
					deletePage(this);
				});
		   },
			error: function(XMLHttpRequest, textStatus, errorThrown){
				alert(textStatus);
			}	   
		 });			
	}		
	return false;
}

jQuery(function(){
	jQuery('#nav-menu').change(function(){
		var menu = jQuery("#nav-menu option:selected").val();
		jQuery.ajax({
			type: "POST",
			url: "/wp-admin/admin-ajax.php",
			data: 'action=vsm&operation=get-menu&menu=' + menu,
		   dataType: 'json',	   
		   success: function(data){
				jQuery("#nav-menu-items").html(data.json_menu);
				jQuery('#nav-menu-items .button-secondary').click(function(){
					getPagesList(this);
				});
		   },
			error: function(XMLHttpRequest, textStatus, errorThrown){
				alert(textStatus);		
			}	   
		 });			
	});
	
    jQuery('#nav-menu-items .button-secondary').click(function(){
		getPagesList(this);
    });

	jQuery('#pages-list-dialog').dialog({ autoOpen: false, modal: true,  width: '45%', 
		buttons: { "Cancel": function() { jQuery(this).dialog("close"); },
					"Save": function() {  
						var menu = jQuery("#nav-menu option:selected").val();
						var object_id = jQuery("#pages-list option:selected").val();
						jQuery.ajax({
							type: "POST",
							url: "/wp-admin/admin-ajax.php",
							data: 'action=vsm&operation=update-menu-item-list&menu=' + menu +
								  '&menu-item-id=' + menu_item_id +
								  '&menu-item-object-id=' + object_id,
						   dataType: 'json',	   
						   success: function(data){
								jQuery("#nav-menu-items").html(data.json_menu);
								jQuery('#nav-menu-items .button-secondary').click(function(){
									getPagesList(this);
								});
						   },
							error: function(XMLHttpRequest, textStatus, errorThrown){
								alert(textStatus);
							}	   
						 });			
						jQuery(this).dialog("close");					
					
					}}
	});	

    jQuery('#nav-page-items .button-secondary').click(function(){
		deletePage(this);
    });

	
});

