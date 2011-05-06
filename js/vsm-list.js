var menu_item_id = 0;

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
				jQuery('.button-secondary').click(function(){
					var menu = jQuery("#nav-menu option:selected").val();
					var str = jQuery(this).attr('id');
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
				});
				
		   },
			error: function(XMLHttpRequest, textStatus, errorThrown){
				alert(textStatus);		
			}	   
		 });			
	});
	
    jQuery('.button-secondary').click(function(){
		var menu = jQuery("#nav-menu option:selected").val();
		var str = jQuery(this).attr('id');
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
								jQuery('.button-secondary').click(function(){
									var menu = jQuery("#nav-menu option:selected").val();
									var str = jQuery(this).attr('id');
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
								});
						   },
							error: function(XMLHttpRequest, textStatus, errorThrown){
								alert(textStatus);
							}	   
						 });			
						jQuery(this).dialog("close");					
					
					}}
	});	
	
});
