function make_admin_dropdown_categories_form(no){
	var target_div = '#admin_dropdown_categories_'+(no);
	no=no+1
	var option_base = jQuery('#admin_dropdown_categories_base').html();
	var inport_html = 
			'<div id="admin_dropdown_categories_'+no+'" class="admin_dropdown_categories_'+no+'">'+
			'<select class="0" name="admin_dropdown_categories_[]" onChange="admin_dropdown_categories_next(\''+no+'\',\'0\')">'+
			'<option value="false" class="clevel_01" >---未指定---</option>'+
				option_base+
			'</select>'+
		'</div>'+
		'<p id="admin_dropdown_categories_append_link"><a href="javascript:void(0)" onclick="make_admin_dropdown_categories_form('+no+')"> 追加 </a></p>';
	jQuery('#admin_dropdown_categories_append_link').remove();
	jQuery(target_div).after(inport_html);
	//console.log(inport_html);
}

function admin_dropdown_categories_next(id_no,class_id){ 
	var id_name = '#admin_dropdown_categories_';
	var cild_id = jQuery(id_name+id_no+' select.'+class_id+' option:selected').val();
	var nest_id = jQuery(id_name+id_no+' select.'+class_id).attr('class');
	var prev_cild_id = jQuery(id_name+id_no+' select.'+class_id).prev().val();
	var first_id = jQuery(id_name+id_no+' select:first').attr('class');
	nest_id++;
	//console.log(cild_id);
	if((!(cild_id == prev_cild_id)) && (!(cild_id == ''))){
		if((nest_id == first_id) && (cild_id == 0)){
			jQuery(id_name+class_id+' select').remove();
			make_following_elements(id_no,cild_id,nest_id);
		}else{
			jQuery(id_name+id_no+' select.'+class_id).nextAll().remove();	
			make_following_elements(id_no,cild_id,nest_id);
		}
	}else{
		jQuery(id_name+id_no+' select.'+class_id).nextAll().remove();
	}
}

function make_following_elements(id_no,cild_id,nest_id){
	var outer_id_name = '#admin_dropdown_categories_';
	var div_id = jQuery(outer_id_name+id_no);
	id_no.match(/(\d+)_/);
	//var get_durl = '#feas-searchform-'+RegExp.$1; //
	var json_url = location.pathname; //initでフックされるURL。
	
	//var search_element_id = jQuery(outer_id_name+id_no).attr('class');
	var search_element_id = 'admin_dropdown_categories_';
	json_url = json_url+'/?admin_dropdown_categories_parent='+cild_id;
	if( nest_id == null ){ nest_id = 0; }
	
	
	div_id.append('<span class="loading">読み込み中...</span>');
	jQuery.getJSON( json_url, function(json){
		if(json){
			var select_form = '<select name="'+search_element_id+'[]" class="'+nest_id+'" onChange="admin_dropdown_categories_next(\''+id_no+'\',\''+nest_id+'\')">';
			select_form += '<option value="'+cild_id+'" selected>---未指定---</option>';
			jQuery.each(json,function(){
				select_form += '<option value="'+ this.id +'">'+this.name+'</option>';
			});
			select_form += '</select>';
			div_id.children('.loading').remove();
			div_id.append(select_form);
		}else{
			div_id.children('.loading').remove();
		}
		//console.log(json);
	});
	
	div_id.ajaxComplete(function(){
		if(div_id.children().is('.loading')){
			div_id.children('.loading').remove();
			div_id.append('<span>通信エラー:(</span>');
		}
	});
}
