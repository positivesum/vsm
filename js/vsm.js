//colors
    var lineColor = '#000000';
    var backgroundColor = '#8F8E8D';
    var nodeColor = '#CDCDCD';
    var selectedColor = '#87FF76';
    var childrenColor0 = '#CDCDCD';
    var childrenColor1 = '#F5FF79';
    var childrenColor2 = '#118D38';
    var maxwidth = 100;
    var maxlength = 40;
    var maxsize = 2000;	
    var st;
    var json;
	var tab = 'pages';
	var canvaswidth;
	var canvasheight;
	var recently_edited;

function showPage(id) {
	if (id == 0) {
		jQuery('#menu-item-name-wrap').hide();	
		jQuery('#titlewrap').show();
		jQuery('#title').val('');		
	} else {
		jQuery('#menu-item-name-wrap').show();	
		jQuery('#titlewrap').hide();	
	}

}

	
function init(jsonTree) {
	json = jsonTree;
/*	
	canvaswidth	= maxwidth*(json.count+1);
	canvasheight = maxlength*(json.count+1);	
	
	canvaswidth	= objDiv.clientWidth*2;
	canvasheight = objDiv.clientHeight*2;			
*/

	
    //load json data
    st.loadJSON(json);
    //compute node positions and layout
    st.compute();
	
    setST();		

	var node_active_id = jQuery.cookie("vsm_active_node_id");
	if (node_active_id) {
//	    var TUtil = TreeUtil;
		var TUtil = $jit.json;
		var node = TUtil.getSubtree(json, node_active_id);
		if (node != null) {
			st.onClick(node_active_id);				
		} else {
			st.onClick(st.root);			
		}
	} else if (recently_edited != '') {
		st.onClick('menu-'+recently_edited);	
	} else {
		st.onClick(st.root);	
	}
	
	calcSize();
	scrollMap();										
}

//canvas height function
function setCanvas(){
    if(jQuery('#viewPort').size()>0) jQuery('#viewPort').height(jQuery(window).height() - (jQuery('#viewPort').get(0).scrollTop + 150));
} 

function calcSize() {
	var levels = 0;
	var GUtil = $jit.Graph.Util;
	GUtil.eachNode(st.graph, function(n) {  
		if (levels <  n._depth) {
			levels = n._depth;
		}
	});	

	var nodeArray = [];	
	for (var i=0;i<levels;i++) {
		nodeArray[i] = 0;
	}

	GUtil.eachNode(st.graph, function(n) {  
		var count = 0;
		GUtil.eachSubnode(n, function(elem) {
			count++;
        });
	
		if (nodeArray[n._depth] <  count) {
			nodeArray[n._depth] = count;
		}
	});	

	var maxcount = 1;
	
	for (var i=0;i<nodeArray.length;i++) {
		maxcount += nodeArray[i];
	}

	var objDiv = document.getElementById("viewPort");		
	
	if (view.state == 'map') {
		canvaswidth = maxcount*st.config.Node.width/2;
		canvasheight = (levels+1)*st.config.Node.height*2+objDiv.clientHeight/2;
	} else {
		canvaswidth = maxcount*st.config.Node.height/2;
		canvasheight = (levels+1)*st.config.Node.width*2+objDiv.clientHeight/2;	
	}	

	if (canvaswidth < objDiv.clientWidth) {
		canvaswidth = objDiv.clientWidth;
	}
	if (canvasheight < objDiv.clientHeight) {
		canvasheight = objDiv.clientHeight;
	}	
	
    st.canvas.resize(parseInt(canvaswidth), parseInt(canvasheight));		
}

function setST(){
	var maxtext = '';
//	var GUtil = Graph.Util;
	var GUtil = $jit.Graph.Util;
      GUtil.eachNode(st.graph, function(n) {  
        if (maxtext.length <  n.name.length) {
            maxtext = n.name;
        }
    });

    var div = document.createElement('div');
    div.setAttribute('id', 'Test');
    div.style.fontSize = '0.9em';
    div.style.width = 'auto';
    div.style.height = st.config.Node.height + 'px';
    div.style.position = 'absolute';
    div.style.visibility = 'hidden';
    
    document.body.appendChild(div);
    div.innerHTML = maxtext;
    var test = document.getElementById("Test");
    var width = test.clientWidth;
    if (width  > maxwidth) {
        width  = maxwidth;
    }
    var height = test.clientHeight;
    st.config.Node.width = width;
    document.body.removeChild(div);
} 


//VIEW STATE TRACKING
var view = {
    state: 'map',
    screen: 'normal',	
    map: function(){
        if (this.getState() == 'outline') {
            jQuery('#viewPort, #viewToggles ul').addClass('map').removeClass('outline');
            this.state = 'map';
			calcSize();			
            st.switchPosition('top', "animate", {
                onComplete: function(){
//					st.refresh();
				}
            });
        }
    },
    outline: function(){
        if (this.getState() == 'map') {
            jQuery('#viewPort, #viewToggles ul').addClass('outline').removeClass('map');
            this.state = 'outline';
			calcSize();			
            st.switchPosition('left', "animate", {
                onComplete: function(){ 
//					st.refresh();
				}
            });
        }

    },
	
    fullscreen: function(){
        if (this.getScreen() == 'normal') {
            this.screen = 'full';
        	jQuery('#wphead').hide();
        	jQuery('#adminmenuback').hide();						
        	jQuery('#adminmenu').hide();						
        	jQuery('#footer').hide();
        	jQuery('#wpcontent').css("margin-left", "0");									
        	jQuery('body').css("overflow","hidden");
		    jQuery('#wrap').addClass('fullscreen');
        } else {
            this.screen = 'normal';				
        	jQuery('#wphead').show();
        	jQuery('#adminmenuback').show();						
        	jQuery('#adminmenu').show();					
        	jQuery('#footer').show();
        	jQuery('#wpcontent').css("margin-left", "165px");									
        	jQuery('body').css("overflow","auto");		
		    jQuery('#wrap').removeClass('fullscreen');					
		}
		scrollMap();												
    },	
    toggle: function(){
        if(this.state == 'map') {this.outline();}
        else {this.map();}
    },
    getState: function(){
        return this.state;
    },
    getScreen: function(){
        return this.screen;
    }	
};

function scrollMap(){
    var objDiv = document.getElementById("viewPort");
    objDiv.scrollTop = 9*(objDiv.scrollHeight - objDiv.clientHeight) / 10;
    objDiv.scrollLeft = (objDiv.scrollWidth - objDiv.clientWidth) / 2;
} 

function showPageInfo(event, id){
    var TUtil = $jit.json;
    var node = TUtil.getSubtree(json, id);
	var patt=/menu/g;
	if (node.id == '0') {
        jQuery('#menu-item-settings').css('min-width', '200px');
        jQuery('#menu-item-settings').css('max-width', '200px');
        
		document.getElementById('edit-menu-item-title').value = '';		
		document.getElementById('menu-item-locations').style.display = 'inline';			
		document.getElementById('span-locations').style.display = 'inline';						
		document.getElementById('menu-item-url').style.display = 'none';				
		document.getElementById('link-to-original').style.display = 'none';		
		document.getElementById('menu-item-target').style.display = 'none';						
		document.getElementById('span-save').style.display = 'none';				
		document.getElementById('span-delete').style.display = 'none';				
		document.getElementById('menu-item-locations').onclick = function(){
			locationsMenu();
		};				
		document.getElementById('menu-item-add').onclick = function(){
			addMenu();
		};		
		
	} else {
        jQuery('#menu-item-settings').css('min-width', '300px');
        jQuery('#menu-item-settings').css('max-width', '300px');        
		document.getElementById('menu-item-locations').style.display = 'none';					
		document.getElementById('span-locations').style.display = 'none';		
		document.getElementById('link-to-original').style.display = 'none';		
		document.getElementById('span-add').style.display = 'inline';						
		document.getElementById('span-save').style.display = 'inline';						
		document.getElementById('span-delete').style.display = 'inline';							
		if (id.search('menu') != -1) {
			document.getElementById('edit-menu-item-title').value = decodeURIComponent(node.data.name);	
			document.getElementById('menu-item-url').style.display = 'none';							
			document.getElementById('menu-item-target').style.display = 'none';														
			document.getElementById('link-to-original').style.display = 'none';
			document.getElementById('menu-item-add').onclick = function(){
				addMenuItem(node.id);
			};							
			document.getElementById('menu-item-delete').onclick = function(){
				deleteMenu(node.data.term_id);
			};						
			document.getElementById('menu-item-save').onclick = function(){
				saveMenu(node.data.term_id);
			};
		} else {
			document.getElementById('edit-menu-item-title').value = decodeURIComponent(node.data.title);
			document.getElementById('menu-item-target').style.display = 'block';
			switch (node.data.target) {
			case '_blank':
				document.getElementById('edit-menu-item-target').selectedIndex = 1;	
				break;
			case '_nothing':
				document.getElementById('edit-menu-item-target').selectedIndex = 2;	
				break;
			default:
				document.getElementById('edit-menu-item-target').selectedIndex = 0;				
			}
			if (node.data.type_label != 'Custom') {
				document.getElementById('menu-item-url').style.display = 'none';				
				document.getElementById('link-to-original').style.display = 'block';		
				document.getElementById('menu-item-edit').href = '/wp-admin/post.php?post='+ node.data.object_id +'&action=edit';
				document.getElementById('menu-item-preview').href = decodeURIComponent(node.data.url);			
			} else {
				// document.getElementById('span-add').style.display = 'none';												
				document.getElementById('link-to-original').style.display = 'none';					
				document.getElementById('menu-item-url').style.display = 'block';		
				document.getElementById('edit-menu-item-url').value = decodeURIComponent(node.data.url);				
			}
			document.getElementById('menu-item-add').onclick = function(){
				addMenuItem(node.id);
			};										
			document.getElementById('menu-item-save').onclick = function(){
				saveMenuItem(node.data.ID);
			};		
			document.getElementById('menu-item-delete').onclick = function(){
				deleteMenuItem(node.data.menu, node.data.ID);
			};									
		}	
	}

    var tempPageMeta = jQuery('#menu-item-settings').attr('parentID', node.id).positionInfo().fadeIn(400);
	
	if (!event.cancelBubble) {
      event.cancelBubble = true;	
	}  else {
      event.stopPropagation();	
	}

} 

jQuery.fn.positionInfo = function(){
    var thisParent = jQuery('#'+jQuery(this).attr('parentID'));
    var parentLoc = thisParent.offset();
    jQuery(this).css('top', parentLoc.top  + 50);
    jQuery(this).css('left', parentLoc.left - 50);
    return jQuery(this);
}

function locationsMenu(){
	jQuery('#menu-item-settings').fadeOut(400);
    var tempPageMeta = jQuery('#nav-menu-theme-locations').attr('parentID', 0).positionInfo().fadeIn(400);
}

function saveMenuLocations(){
	var locations = jQuery('#nav-menu-theme-locations form').serialize();
  if (locations != '') {
		jQuery('#nav-menu-theme-locations').fadeOut(400);
		jQuery.ajax({
		   type: "POST",
//		   url: "/wp-admin/admin-ajax.php",
		   url: ajaxurl,
		   data: locations,
		   dataType: 'json',
		   success: function(data){
			}
		   });
	} else {
		alert('Theme locations are empty!');
	}
}

/*
action=menu-locations-save&menu-settings-column-nonce=2c5703b410&menu-locations%5Bprimary%5D=13&menu-locations%5Btop-navigation%5D=12
*/
function addMenu(){
	var name = document.getElementById('edit-menu-item-title').value;
	if (name != '') {
		jQuery('#menu-item-settings').fadeOut(400);
		jQuery.ajax({
		   type: "POST",
//		    url: "/wp-admin/admin-ajax.php",
			url: ajaxurl,
		    data: 'action=vsm&operation=add-menu&menu=0&menu-name=' + encodeURIComponent(name),
//		   url: "/wp-content/plugins/vsm/nav-menus-api.php",
//		   data: 'action=add-menu&menu=0&menu-name=' + encodeURIComponent(name),
		   dataType: 'json',
		   success: function(data){
				var id = 'menu-'+data.menu_object.term_id;
				var node = {'id': id, 'name': name,	'data': data.menu_object, 'children': []};
				var menu = {'id' : 0, children : [node]};
				json.children.push(node);
				st.addSubtree(menu, "animate", {hideLabels: false,
								onAfterCompute: function() {
									st.onClick(id);
									scrollMap();
									st.refresh();
									 var str = '';	
									 for (var i in data.messages) {
										str += data.messages[i];
									 }
									jQuery("#messages").html(str);									
									
							}
				});
			}
		   });
	} else {
		alert('Menu name is empty!');
	}
}

function deleteMenu(id) {
	var r=confirm("You are about to permanently delete this menu.\n 'Cancel' to stop, 'OK' to delete.");
	if (r==true) {
		jQuery('#menu-item-settings').fadeOut(400);
		jQuery.ajax({
		   type: "POST",
//		    url: "/wp-admin/admin-ajax.php",
			url: ajaxurl,
		    data: 'action=vsm&operation=delete&menu=' + id,
//		   url: "/wp-content/plugins/vsm/nav-menus-api.php",
//		   data: 'action=delete&menu=' + id,
		   dataType: 'json',
		   success: function(data){
			var menu_id = "menu-" + id;			
			st.removeSubtree(menu_id, true, 'replot', {
						hideLabels: false,
						onAfterCompute: function() {
							jQuery.cookie("vsm_active_node_id", st.root ); //		  																
							st.onClick(st.root);
							scrollMap();					
							 var str = '';	
							 for (var i in data.messages) {
								str += data.messages[i];
							 }
							jQuery("#messages").html(str);							
						}
			});
		
		   }
		 });		
	}
}

function saveMenu(id){
	var name = document.getElementById('edit-menu-item-title').value;
	if (name != '') {
		jQuery('#menu-item-settings').fadeOut(400);
		jQuery.ajax({
		   type: "POST",
//		    url: "/wp-admin/admin-ajax.php",
			url: ajaxurl,
		    data: 'action=vsm&operation=update-menu&menu='+id + '&menu-name=' + encodeURIComponent(name),

//		   url: "/wp-content/plugins/vsm/nav-menus-api.php",
//		   data: 'action=update-menu&menu='+id + '&menu-name=' + encodeURIComponent(name),
		   dataType: 'json',
		   success: function(data){
			 var str = '';	
			 for (var i in data.messages) {
				str += data.messages[i];
			 }
			jQuery("#messages").html(str);
//			var TUtil = TreeUtil;
			var TUtil = $jit.json;
			var node = TUtil.getSubtree(json, 'menu-'+id);			
			node.name = name;	
			node.data.name = name;							
			jQuery('#menu-'+id).html('<a href="javascript:void(0)" onclick="showPageInfo(event, \'' + node.id + '\')">'+node.name+'</a>');
		   }
		 });			
	} else {
		alert('Menu name is empty!');
	}
}

function getSubtree(subtree) {
	var ids = [subtree.id];
	for (var i=0;i<subtree.children.length;i++) {
		if (subtree.children[i].children.length > 0) {
			var aids = getSubtree(subtree.children[i]);	
			for (var j=0;j<aids.length;j++) {
				ids.push(aids[j]);
			}
		} else {
			ids.push(subtree.children[i].id);		
		}		
	}
	return ids;
}

function deleteMenuItem(menu, id){
	var TUtil = $jit.json;
	var subtree = TUtil.getSubtree(json, id);
	var ids = [id];
	if (subtree != null) {
		ids = getSubtree(subtree);
	}
	
	var r=confirm("You are about to permanently delete this menu item.\n 'Cancel' to stop, 'OK' to delete.");
	if (r==true) {
		jQuery('#menu-item-settings').fadeOut(400);
		jQuery.ajax({
		   type: "POST",
//		    url: "/wp-admin/admin-ajax.php",
			url: ajaxurl,
		    data: 'action=vsm&operation=delete-menu-item&menu=' + menu + '&menu-item='  + ids,
/*
		   url: "/wp-content/plugins/vsm/nav-menus-api.php",
		   data: 'action=delete-menu-item&menu=' + menu 
		   + '&menu-item='  + id,
*/
		   dataType: 'json',
		   success: function(data){
			st.removeSubtree(id, true, 'animate', {
				hideLabels: false,
				onAfterCompute: function() {
//					var TUtil = TreeUtil;
					var TUtil = $jit.json;
					var node = TUtil.getParent(json, id);									
					st.onClick(node.id);																			
					jQuery.cookie("vsm_active_node_id", node.id ); //		  												   				
					/*
					st.onClick(node.id);														
					scrollMap();														
					*/
					 var str = '';	
					 for (var i in data.messages) {
						str += data.messages[i];
					 }							
					 jQuery("#messages").html(str);																			 
				}
			});		   

		   }
		 });		
	}
}

function addMenuItem(id){
	document.getElementById('submenu-item-add').onclick = function(){
		addSubMenuItem(id);
	};		
	jQuery('#menu-item-settings').fadeOut(400);
    var tempPageMeta = jQuery('#typediv').attr('parentID', id).positionInfo().fadeIn(400);
}

function addSubMenuItem(id){
//	var TUtil = TreeUtil;
    var TUtil = $jit.json;
	var node = TUtil.getSubtree(json, id);
	var menu = 0;
	var parent_id = 0;
	var	type = 'post_type';	
	var object_type	 = 'post';
	var object_id = 0;
	var name = '';
	var url = '';	
	var str = String(id);
	if (str.search('menu') != -1) {
		menu = node.data.term_id;
		parent_id = 0;
	} else {
		menu = node.data.menu;
		parent_id = node.data.ID;	
	}
	switch(tab) {
	case 'pages':
		object_type	 = 'page';
		var page = jQuery('input:radio[name=page]:checked').val();
		if (page == 'new_page') {
			name = document.getElementById('title').value;				
			if (name == '') {
				alert('Page name is empty!');
				return false;						
			}
		} else {
			var obj = document.getElementById('pages-list');
			if (obj.selectedIndex == -1) {
				alert('Pages list is empty!');
				return false;			
			} 
			if (obj.selectedIndex == 0) {
				alert('Select a page!');
				return false;			
			} 			
			object_id = obj.options[obj.selectedIndex].value;
			name  = obj.options[obj.selectedIndex].text;
			url = '/?page_id='+object_id;				
		}
    break;
	case 'links':
		type = 'custom';
		object_type = 'custom';		
		name = document.getElementById('custom-menu-item-name').value;	
		if (name == '') {
			alert('Menu item name is empty!');
			return false;	
		}			
		url = document.getElementById('custom-menu-item-url').value;
		if (url == '') {
			alert('Menu item url is empty!');		
			return false;				
		}	
	  break;	
	case 'posts':
		object_type	 = 'post';
		var obj = document.getElementById('posts-list');
		if (obj.selectedIndex == -1) {
			alert('Posts list is empty!');
			return false;			
		}		
		object_id = obj.options[obj.selectedIndex].value;
		name  = obj.options[obj.selectedIndex].text;
		url = '/?p='+object_id;
	  break;	  
	}	

	jQuery('#typediv').fadeOut(400);	

	if (tab == 'links') {
		var post = 'operation=add-menu-item&menu='
				+ menu
				+ '&menu-item[-1][menu-item-parent-id]=' + parent_id
				+ '&menu-item[-1][menu-item-type]=' + type
				+ '&menu-item[-1][menu-item-url]=' + encodeURIComponent(url)
				+ '&menu-item[-1][menu-item-title]=' + encodeURIComponent(name);	
	} else {
		var post = 'operation=add-menu-item&menu='
			+ menu
			+ '&menu-item[-1][menu-item-db-id]=0'  				
			+ '&menu-item[-1][menu-item-object-id]=' + object_id					
			+ '&menu-item[-1][menu-item-object]=' + object_type		
			+ '&menu-item[-1][menu-item-parent-id]=' + parent_id
			+ '&menu-item[-1][menu-item-type]=' + type
			+ '&menu-item[-1][menu-item-url]=' + encodeURIComponent(url)
			+ '&menu-item[-1][menu-item-title]=' + encodeURIComponent(name);	
	}
	
	
	jQuery.ajax({
	   type: "POST",
//	    url: "/wp-admin/admin-ajax.php",
	    url: ajaxurl,
	    data: 'action=vsm&'  + post,
/*
	   url: "/wp-content/plugins/vsm/nav-menus-api.php",
	   data: post,
*/
       dataType: 'json',
	   success: function(data){
		name = data.menu_items[0].title;
		var menu_item = {'id': data.menu_items[0].ID, 'name': name, 'data': data.menu_items[0], 'children': []};
		menu_item.data.menu = menu;
		var obj = {'id' : id, children : [menu_item]};
		node.children.push(menu_item);
		st.addSubtree(obj, "replot", {hideLabels: false,
						onAfterCompute: function() {
						/*		
								st.refresh();								
								st.onClick(id);														
								scrollMap();									
						*/		
								 var str = '';	
								 for (var i in data.messages) {
									str += data.messages[i];
								 }
								jQuery("#messages").html(str);								
								if (tab == 'links') {
								  document.getElementById('edit-menu-item-url').value = url;	
								}
								document.getElementById('edit-menu-item-title').value = name;						
								saveMenuItem(data.menu_items[0].ID);								
						}		
		});
	   }
	 });	
}


function selectTab(id) {
	tab = id;
	jQuery('#add-menu-item-tabs li').removeClass('tabs');
	jQuery('#tab-'+id).addClass('tabs');	
	jQuery('.tabs-panel').removeClass('tabs-panel-active').addClass('tabs-panel-inactive');
	jQuery('#tabs-panel-'+id).removeClass('tabs-panel-inactive').addClass('tabs-panel-active');
}

function saveMenuItem(id){
//	var TUtil = TreeUtil;
    var TUtil = $jit.json;
	var node = TUtil.getSubtree(json, id);
	
	var name = document.getElementById('edit-menu-item-title').value;
	if (name == '') {
		alert('Menu item name is empty!');
		return false;	
	}	
	
	var url = node.data.url;
	if (node.data.type_label == 'Custom') {	
		url = document.getElementById('edit-menu-item-url').value;	
		if (url == '') {
			alert('Menu item url is empty!');		
			return false;				
		}		
	}

	var obj = document.getElementById('edit-menu-item-target');
	var target = obj.options[obj.selectedIndex].value;
	/*
	if (target == '_nothing') {
		url	= '#';
	}
	*/
	jQuery('#menu-item-settings').fadeOut(400);
	
	jQuery.ajax({
	   type: "POST",
//	    url: "/wp-admin/admin-ajax.php",
	    url: ajaxurl,
	    data: 'action=vsm&operation=update-menu-item&menu='
				+ node.data.menu 
				+ '&menu-item-id=' + id		
				+ '&menu-item-object-id=' + node.data.object_id
				+ '&menu-item-object=' + node.data.object		
				+ '&menu-item-parent-id=' + node.data.menu_item_parent				
				+ '&menu-item-position=' + node.data.menu_order
				+ '&menu-item-type=' + node.data.type
				+ '&menu-item-target=' + target				
				+ '&menu-item-url=' + encodeURIComponent(url)
				+ '&menu-item-db-id=' + node.data.db_id
				+ '&menu-item-title=' + encodeURIComponent(name),

/*
	   url: "/wp-content/plugins/vsm/nav-menus-api.php",
	   data: 'action=update-menu-item&menu='
				+ node.data.menu 
				+ '&menu-item-id=' + id		
				+ '&menu-item-object-id=' + node.data.object_id
				+ '&menu-item-object=' + node.data.object		
				+ '&menu-item-parent-id=' + node.data.menu_item_parent				
				+ '&menu-item-position=' + node.data.menu_order
				+ '&menu-item-type=' + node.data.type
				+ '&menu-item-url=' + encodeURIComponent(url)
				+ '&menu-item-db-id=' + node.data.db_id
				+ '&menu-item-title=' + encodeURIComponent(name),
*/
       dataType: 'json',
	   success: function(data){
			jQuery('#'+id).html('<a href="javascript:void(0)" onclick="showPageInfo(event, \'' + id + '\')">'+name+'</a>');
		node.name = name;	
		node.data.title = name;				
		node.data.url = url;		
		node.data.target = target;				
		 var str = '';	
		 for (var i in data.messages) {
			str += data.messages[i];
		 }
		jQuery("#messages").html(str);
	   }
	 });	
}

/*-------------------------------------------------------------------------
DOM READY EVENTS
-------------------------------------------------------------------------*/
jQuery(function(){
    /*----------------------------------
    BUTTON BAR
    ----------------------------------*/
    //TOGGLE MAP VIEW
    jQuery('#mapView').click(function(){
        view.map();
        return false;
    });

    //TOGGLE OUTLINE VIEW
    jQuery('#outlineView').click(function(){
        view.outline();
        return false;
    });

    //TOGGLE FULLSCREEN VIEW
    jQuery('#fullscreenView').click(function(){
        view.fullscreen();
        return false;
    });	

    //HIDE TITLE PROMPT TEXT
	jQuery("#title").focus(function(){
	  jQuery('#title-prompt-text').hide();
	  jQuery(this).css({"background" : "none repeat scroll 0 0 #FFE8B6", "border-color": "#666666"});	  
	});

    //SHOW TITLE PROMPT TEXT
	jQuery("#title").focusout(function(){
	  if (jQuery(this).val() == '') {
		jQuery('#title-prompt-text').show();	  
	  }	
	  jQuery(this).css({"background" : "none repeat scroll 0 0 #FFFFFF", "border-color": "#DFDFDF"});	  	  
	});	
	
    setCanvas();
    jQuery(window).resize(function () {setCanvas();}); 

    var viewPort = document.getElementById('viewPort');

    //Create a new ST instance
    st= new $jit.ST({
		injectInto:'viewPort',	
        orientation: "top",
		levelsToShow: 1,		
        withLabels: true, 
		backgroundColor: backgroundColor,		
        //change the animation/transition effect		
   //     transition: Trans.Quart.easeOut,		
      //set node and edge colors
		Node: {
		  height: 40,
		  width: 100,
		  type: 'rectangle',
		  color: nodeColor,
		  overridable: true
		},
		Edge: {
		  type: 'bezier',
		  overridable: true
		},
/*		
        Navigation: {
          enable:true,
          panning:true
        },
*/		
		//This method is called on DOM label creation.
		//Use this method to add event handlers and styles to
		//your node.
		onCreateLabel: function(label, node){
			label.id = node.id;		
			if (node.id != -1)  {
				label.innerHTML = '<b><a href="javascript:void(0)" onclick="showPageInfo(event, \'' + node.id + '\')">'+node.name+'</a><b>';			
			} else {
				label.innerHTML = '<b>'+node.name+'</b>';						
			}
		  label.onclick = function(){
			var node_active_id = jQuery.cookie("vsm_active_node_id");
			jQuery.cookie("vsm_active_node_id", node.id );		  			
			st.onClick(node.id);
            scrollMap();
		  };
		  //set label styles
		  var style = label.style;
		  style.width = st.config.Node.width + 'px';
		  style.height = st.config.Node.height + 'px';            
		  style.cursor = 'pointer';
		  style.color = '#333';
		  style.fontSize = '0.9em';
		  style.lineHeight = '1em';		  
		  style.textAlign= 'center';
		  style.paddingTop = '1px';
		  style.background = node.data.$color; // IE empty div fix
		  
		},
		//This method is called right before plotting
		//a node. It's useful for changing an individual node
		//style properties before plotting it.
		//The data properties prefixed with a dollar
		//sign will override the global node style properties.
		onBeforePlotNode: function(node){
		  //add some color to the nodes in the path between the
		  //root node and the selected node.
		  if (node.selected) {
			node.data.$color = selectedColor;
		  }
		  else {
			delete node.data.$color;
			//if the node belongs to the last plotted level
			if(!node.anySubnode("exist")) {
			  //count children number
			  var count = 0;
			  node.eachSubnode(function(n) { count++; });
			  //assign a node color based on
			  //how many children it has
			  if (count > 1) {
                count = 1;
              }
              node.data.$color = [childrenColor0, childrenColor1][count];
			}
		  }
		  jQuery('#'+node.id).css('background', node.data.$color); // IE empty div fix
		},
		
		//This method is called right before plotting
		//an edge. It's useful for changing an individual edge
		//style properties before plotting it.
		//Edge data proprties prefixed with a dollar sign will
		//override the Edge global style properties.
		onBeforePlotLine: function(adj){
		  if (adj.nodeFrom.selected && adj.nodeTo.selected) {
			adj.data.$color = lineColor;
			adj.data.$lineWidth = 3;
		  }
		  else {
			delete adj.data.$color;
			delete adj.data.$lineWidth;
		  }
		}
    });

	jQuery.ajax({
	    type: "POST",
//	    url: "/wp-admin/admin-ajax.php",
	    url: ajaxurl,
	    data: 'action=vsm&operation=load-nav-menus',
//	   url: "/wp-content/plugins/vsm/nav-menus-api.php",
//	   data: 'action=load-nav-menus',
       dataType: 'json',
	   success: function(data){
		 var str = '';	
		 for (var i in data.messages) {
			str += data.messages[i];
		 }
		jQuery("#messages").html(str);
		recently_edited = data.recently_edited;
		init(data.json_menus);
	   }
	 });	
});