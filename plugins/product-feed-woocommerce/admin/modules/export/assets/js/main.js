var wt_pf_basic_export=(function( $ ) {
	//'use strict';
	var wt_pf_basic_export=
	{
		ajax_data:{},
		selected_template:0,
		selected_template_name:'',
		to_export:( wt_pf_export_basic_params.to_export !== '' ) ?  wt_pf_export_basic_params.to_export : 'google',
		to_export_title:'',
		export_method:'',
		current_step:'',
		loaded_status_arr:{'loaded':1, 'loading':2, 'not_loaded':0},
		page_overlay:false,
		step_keys:[],
		form_data:{},
		only_enabled_data:false,
		on_rerun:false,
		rerun_id:0,
        export_finished:false,
		Set:function()
		{
			this.step_keys=Object.keys(wt_pf_export_basic_params.steps);
			if(this.on_rerun)
			{
				this.rerun();
			}else
			{
				var first_step=this.step_keys[0];
				this.load_steps([first_step], first_step);
				this.show_step_page(first_step, false); /* just to show a loading */
			}
                        
		},
		rerun:function()
		{
            var first_step=this.step_keys[0];
			this.load_steps([first_step], first_step);
			this.show_step_page(first_step, false); /* just to show a loading */                               
            // Only run the first step for rerun as well, otherwsie the changes made in step one will not reflect
			//var last_step=this.step_keys[this.step_keys.length-1];
			//this.load_steps(this.step_keys, last_step);
			//this.show_step_page(last_step, false); /* just to show a loading */
		},
		load_steps:function(steps, step_to_show)
		{
			this.prepare_ajax_data('get_steps', 'json');
			this.ajax_data.steps=steps;
			if(this.on_rerun)
			{
				this.ajax_data.rerun_id=this.rerun_id;
			}
			this.set_step_loading_status(steps, 'loading');
			this.set_ajax_page_loader(steps, 'loading');
			$.ajax({
				type: 'POST',
            	url:wt_pf_basic_params.ajax_url,
            	data:this.ajax_data,
            	dataType:'json',
            	success:function(data)
				{
					if(data.status==1)
					{
						wt_pf_basic_export.set_step_page(data);
						wt_pf_basic_export.set_step_loading_status(steps, 'loaded');
						if(step_to_show)
						{
							wt_pf_basic_export.show_step_page(step_to_show, true);
						}
					}else
					{
                        wt_pf_basic_export.set_step_loading_status(steps, 'not_loaded');
						wt_pf_basic_export.set_ajax_page_loader(steps, 'error');
					}
					wt_pf_basic_export.remove_ajax_page_loader();
				},
				error:function()
				{
                    wt_pf_basic_export.set_step_loading_status(steps, 'not_loaded');
					wt_pf_basic_export.remove_ajax_page_loader();
					wt_pf_basic_export.set_ajax_page_loader(steps, 'error');
				}
			});
		},
		load_meta_mapping_fields:function()
		{
			if($('.wtpf_meta_mapping_box_con').length>0)
			{
				if($('.wtpf_meta_mapping_box_con[data-loaded="0"]').length==0)
				{
					return false;
				}
			}else
			{
				return false;
			}

			this.prepare_ajax_data('get_meta_mapping_fields', 'json');
			this.ajax_data.rerun_id = this.rerun_id;
			$('.button-primary.wt_pf_export_action_btn').prop('disabled', true);
			$('.wtpf_meta_mapping_box_con[data-loaded="0"]').html('<div class="wt_pf_export_step_loader">'+wt_pf_basic_params.msgs.loading+'</div>');
			$.ajax({
				type: 'POST',
				url:wt_pf_basic_params.ajax_url,
				data:this.ajax_data,
				dataType:'json',
				success:function(data)
				{
                    $('.button-primary.wt_pf_export_action_btn').prop('disabled', false);
					if(data.status==1)
					{
						$.each(data.meta_html, function(meta_id, meta_content){
							$('.wtpf_meta_mapping_box_con[data-key="'+meta_id+'"]').html(meta_content).attr('data-loaded', 1);					  	
						});
                                                
						$('.wc-enhanced-select').select2();
						$( document.body ).trigger( 'wc-enhanced-select-init' );                                                
                                                
						wt_pf_basic_export.enable_sortable();
						wt_pf_basic_export.reg_mapping_field_bulk_action();
                        wt_pf_basic_export.mapping_select_change();
					}else
					{
						$('.wtpf_meta_mapping_box_con[data-loaded="0"]').html('<div class="wt_pf_export_step_loader">'+wt_pf_basic_params.msgs.error+'</div>');
					}
				},
				error:function()
				{
					$('.wtpf_meta_mapping_box_con[data-loaded="0"]').html('<div class="wt_pf_export_step_loader">'+wt_pf_basic_params.msgs.loading+'</div>');
				}
			});
		},
		console_formdata:function()
		{
			console.log(this.form_data);
		},
		refresh_step:function(no_overlay)
		{
			if(!no_overlay){
				this.page_overlay=true; 
			}
			this.load_steps([this.current_step], this.current_step);
		},
		load_pending_steps:function(no_overlay)
		{
			if(!no_overlay){
				this.page_overlay=true; 
			}
			var rest_steps=this.step_keys.slice(0);
			rest_steps.shift(); /* remove first step. no need to load it agian */
			this.load_steps(rest_steps, this.current_step);
		},
		get_page_dom_object:function(step)
		{
			return $('.wt_pf_export_step_'+step);
		},
		remove_ajax_page_loader:function()
		{
			$('.wt_pf_loader_info_box').hide();
			$('.wt_pf_overlayed_loader').hide();
			$('.spinner').css({'visibility':'hidden'});	
			this.page_overlay=false;
		},
		set_ajax_page_loader:function(steps, msg_type)
		{
			if(this.page_overlay)
			{
				var h=parseInt($('.wt_pf_export_step_main').outerHeight());
				var w=parseInt($('.wt_pf_export_step_main').outerWidth());
				$('.wt_pf_overlayed_loader').show().css({'height':h,'width':w,'margin-top':'30px','margin-left':'30px'});
				$('.spinner').css({'visibility':'visible'});
			}else
			{
				var msg='';
				if(msg_type=='loading')
				{
					msg=wt_pf_basic_params.msgs.loading;
				}else if(msg_type=='error')
				{
					msg=wt_pf_basic_params.msgs.error;
				}
				$.each(steps, function(step_ind, step){
					wt_pf_basic_export.get_page_dom_object(step).html('<div class="wt_pf_export_step_loader">'+msg+'</div>');
				});
			}		
		},
		hide_export_info_box:function()
		{
			$('.wt_pf_loader_info_box').hide();
		},
		set_export_progress_info:function(msg)
		{
			$('.wt_pf_loader_info_box').show().html(msg);
		},
		nonstep_actions:function(action)
		{
			if(this.export_method=='template' && this.selected_template==0)
			{	
				$('.wt_pf_warn').hide();
				$('.wt_pf_export_template_wrn').show();
				return false;
			}

			this.prepare_ajax_data(action, 'json');

			if(action=='save_template' || action=='save_template_as' || action=='update_template')
			{
				$('.wt_pf_template_name_wrn').hide();
				var pop_elm=$('.wt_pf_template_name');
				var popup_label=pop_elm.attr('data-save-label');
				if(action=='save_template_as')
				{
					var popup_label=pop_elm.attr('data-saveas-label');
				}
				pop_elm.find('.wt_pf_popup_hd_label, .wt_pf_template_create_btn').text(popup_label);
				wt_pf_popup.showPopup(pop_elm);
				$('[name="wt_pf_template_name_field"]').val(this.selected_template_name).focus();

				$('.wt_pf_template_create_btn').off('click').on('click',function(){
					var name=$.trim($('.wt_pf_template_name_field').val());
					if(name=='')
					{
						$('.wt_pf_template_name_wrn').show();
						$('.wt_pf_template_name_field').focus();
					}else
					{
						$('.wt_pf_template_name_wrn').hide();
						wt_pf_popup.hidePopup();

						wt_pf_basic_export.prepare_form_data();
						wt_pf_basic_export.ajax_data['template_name']=name;
						wt_pf_basic_export.ajax_data['form_data']=wt_pf_basic_export.form_data;
						wt_pf_basic_export.do_nonstep_action(action);
					}
				});
			}else if(action=='export' || action=='upload' || action=='export_image')
			{
				if(action=='export' || action=='export_image')
				{
                                        localStorage.setItem('wt_feed_filename', $('#wt_pf_export_catalog_name').val());
					this.ajax_data['offset']=0;
                                        this.ajax_data['rerun_id']=wt_pf_export_basic_params.rerun_id;
					this.prepare_form_data();
					this.ajax_data['form_data']=this.form_data;
                     
				}
				wt_pf_basic_export.do_nonstep_action(action);
			}else
			{
				/* custom action section for other modules */
				this.prepare_form_data();
				this.ajax_data['form_data']=this.form_data;
				wt_pf_custom_action_basic(this.ajax_data, action, this.selected_template);
			}
		},
		do_nonstep_action:function(action)
		{
			this.page_overlay=true;
			this.set_ajax_page_loader();                        
                        
			if(this.rerun_id <= 0){
				$('.pf_export_btn').attr("disabled", true);
				$('.wt_pf_export_action_btn').attr("disabled", true);
			}
                        
			wt_pf_basic_export.export_ajax_xhr =  $.ajax({
				type: 'POST',
				url:wt_pf_basic_params.ajax_url,
				data:this.ajax_data,
				dataType:'json',
				success:function(data)
				{
					wt_pf_basic_export.remove_ajax_page_loader();
					if(data.status==1)
					{
						if(action=='save_template' || action=='save_template_as' || action=='update_template')
						{
							wt_pf_basic_export.selected_template=data.id;
							wt_pf_basic_export.selected_template_name=data.name;
							wt_pf_notify_msg.success(data.msg);
							//wt_pf_notify_msg.success(wt_pf_basic_params.msgs.success);
							
						}else if(action=='export' || action=='upload' || action=='export_image')
						{
							if(data.finished==1)
							{
								if(data.no_post==1){
									alert(data.msg);
								} else {
									$('.wt_pf_export_progress_wrapper').hide();
									$('.wt_pf_loader_info_box').css({'background': 'white', 'color': 'black', 'box-shadow': '2px 2px 4px 2px #ccc'});
									wt_pf_basic_export.set_export_progress_info(data.msg);
									wt_pf_basic_export.reg_copy_action();                                                                
									wt_pf_basic_export.export_finished = true;
									//wt_pf_notify_msg.success(wt_pf_basic_params.msgs.success);
                                }
							}
							else if(data.finished==2) /* Remote export */
							{
								wt_pf_basic_export.set_export_progress_info(data.msg);
								wt_pf_basic_export.ajax_data['export_id']=data.export_id;
								wt_pf_basic_export.ajax_data['total_records']=data.total_records;
								wt_pf_basic_export.ajax_data['export_action']='upload';
								wt_pf_basic_export.ajax_data['form_data']={};
								wt_pf_basic_export.do_nonstep_action('upload');
							}
							else
							{
                                                                                                    
								if (action == 'export') {
									$('.wt_pf_loader_info_box').css({'background': 'white', 'color': 'black', 'box-shadow': '2px 2px 4px 2px #ccc'});
									$('.wt_pf_export_progress_wrapper').show();
									$('.wt_pf_exporting_progress_done').html(data.total_done);
									$('.wt_pf_exporting_progress_total').html(data.total_records);
									var progressval = data.total_percent;
									var elm = document.getElementsByClassName('progressab')[0];
									elm.style.width = progressval + "%";
									elm.innerText = progressval + "%";
									elm.style.backgroundColor = '#5FBA89';
								}
								//wt_pf_basic_export.set_export_progress_info(data.msg);
								wt_pf_basic_export.ajax_data['offset']=data.new_offset;
								wt_pf_basic_export.ajax_data['export_id']=data.export_id;
								wt_pf_basic_export.ajax_data['total_records']=data.total_records;
								wt_pf_basic_export.do_nonstep_action(action);
							}
						}else
						{

						}
					}else
					{
						if(data.msg!="")
						{
							wt_pf_notify_msg.error(data.msg);
						}else
						{
							wt_pf_notify_msg.error(wt_pf_basic_params.msgs.error);
						}
						if(this.rerun_id <= 0){
							$('.pf_export_btn').attr("disabled", true);
							$('.wt_pf_export_action_btn').attr("disabled", true);
						}
					}
				},
				error:function()
				{
					wt_pf_basic_export.remove_ajax_page_loader();
					wt_pf_notify_msg.error(wt_pf_basic_params.msgs.error);
				}
			});
		},
		reg_button_actions:function()
		{
            $(".wt-pf-tips").tipTip({'attribute': 'data-wt-pf-tip'});
			$('.wt_pf_export_action_btn').off('click').on('click', function(e){
				e.preventDefault();
				var action=$(this).attr('data-action');
				var action_type=$(this).attr('data-action-type');
				var is_previous_step=wt_pf_basic_export.is_previous_step(action);
				// if(!wt_pf_exporter_validate(action, action_type, is_previous_step))
				// {
				// 	return false;
				// }

				if(action_type=='step'){       
					if(action=='category_mapping'){    
						// When proceeding to the category mapping page eliminate the need for users to scroll up
						const section = $('.wt_pf_step_head_post_type_name');       
						if (section) {
							const sectionTop = section.offset();	
							window.scrollTo({
								top: sectionTop.top,
								behavior: "smooth"
							});
						}
					
						$('.wc-enhanced-select').select2();
						$( document.body ).trigger( 'wc-enhanced-select-init' );
						$('.wt-wc-enhanced-search').css('width', '300px');
						wt_pf_basic_export.woo_cat_search();
					}                                
					wt_pf_basic_export.change_step(action);
				}else
				{
					wt_pf_basic_export.nonstep_actions(action);
				}	
			});
                                            
			$('.wt_pf_export_popup_cancel_btn').off('click').on('click',function(e){
				if (confirm(wt_pf_export_basic_params.msgs.export_cancel_warn) == true) {
					var temp_err_message = wt_pf_basic_params.msgs.error;
					wt_pf_basic_params.msgs.error = wt_pf_basic_params.msgs.export_canceled;
					wt_pf_basic_export.export_ajax_xhr.abort();
					wt_pf_basic_export.export_finished = true;
					wt_pf_basic_params.msgs.error = temp_err_message;
					var progressval = 1;
					var elm = document.getElementsByClassName('progressab')[0];
					elm.style.width = progressval+"%";
					elm.innerText = progressval+"%";
					jQuery('.wt_pf_overlay, .wt_pf_popup, .wt_pf_export_progress_wrapper').hide();
				}
			});
                        
			$(".wt_pf_add_filter_btn").off('live').on('click',function() {
				var $tr    = $(this).closest('tr');
				var $clone_tr = $tr.prev();
				var $clone = $clone_tr.clone();
				$clone.find(':text').val('');
				$clone.find(':selected').removeAttr('selected');
				$tr.before($clone);
			});   
			$(document).on("click", ".wt-feed-filter-row-remove", function() {
				var $tr    = $(this).closest('tr');
				if($('.wt-feed-filter-row-remove').length > 1){
					$tr.remove();
				}
			});
			$('.wt-feed-simple-filter').off('click').on('click',function(e){
				$('.wt-feed-advanced-filter').addClass('wt-filter-inactive');
				$('.wt-feed-advanced-filter').removeClass('wt-filter-active');
				$('.wt-feed-simple-filter').addClass('wt-filter-active');
				$('.wt-feed-simple-filter').removeClass('wt-filter-inactive');
				$('.wt-pf-adv-filter-options').hide();
				$('.wt-feed-basic-filter-section').show();
			});
			$('.wt-feed-advanced-filter').off('click').on('click',function(e){
				$('.wt-feed-simple-filter').addClass('wt-filter-inactive');
				$('.wt-feed-simple-filter').removeClass('wt-filter-active');
				$('.wt-feed-advanced-filter').removeClass('wt-filter-inactive');
				$('.wt-feed-advanced-filter').addClass('wt-filter-active');
				$('.wt-pf-adv-filter-options').show();
				$('.wt-feed-basic-filter-section').hide();
			});

			$(".wt_pf_add_attr_btn").off('click').on('click',function(e){
				var counter = 0;

				if( $('.wt_pf_dynamic_attr_input').length > 0 ){
					counter = $('.wt_pf_dynamic_attr_input').length;
					while( $('#columns_custom_'+counter).length > 0 ){
						counter++;
					}
				}
				var $attr_select_list    = jQuery('.columns_val').html();
				var $add_row = '<tr id="columns_custom_'+counter+'" class="wt_pf_dynamic_attr"><td><div class="wt_pf_custom_attr wt_pf_delete_row_handle"><span class="dashicons dashicons-remove wt-pf-remove-row"></span></div><input type="checkbox" name="columns_key[]" class="columns_key wt_pf_mapping_checkbox_sub" value="" checked=""></td><td><input required="" type="text" name="wt_pf_custom_attr_val_'+counter+'" value="" class="wt_pf_dynamic_attr_input" id="wt_pf_custom_attr_val_'+counter+'" style="width:180px;"></td><td><select name="columns_val[]" class="columns_val wt_pf_custom_attr_selval_'+counter+' ">'+$attr_select_list+'</select></td><td class="columns_static_td"><input type="text" name="columns_static_val[]" value="" style="display: none;" class="columns_static_val"></td><td class="columns_compute_td"><input type="text" name="columns_compute_val[]" value="" style="display: none;" class="columns_compute_val"></td></tr>';                                                
				if(counter === 0){
					$('.ui-sortable').after($add_row);
				}else{
					$('.wt-pfd-exporter-default-mapping-tb tr:last').after($add_row);
				}
				wt_pf_basic_export.mapping_select_change();
				wt_pf_basic_export.reg_remove_row_action();
			});
             
		},
            reg_copy_action:function()
		{
			jQuery('.wt_pf_copy').off('click').on('click',function(e){
				var data_uri = jQuery(this).attr('data-uri');
				var result = wt_pf_basic_export.copyToClipboard(data_uri);
				wt_pf_notify_msg.success(wt_pf_basic_params.msgs.copied_msg);
			});
		},
            reg_remove_row_action:function()
		{
			jQuery('.wt-pf-remove-row').off('click').on('click',function(e){
                $(this).closest("tr").remove();
			});
		},                
		copyToClipboard: function(text){
			if (window.clipboardData && window.clipboardData.setData) {
				// IE specific code path to prevent textarea being shown while dialog is visible.
				return clipboardData.setData("Text", text);

			} else if (document.queryCommandSupported && document.queryCommandSupported("copy")) {
				var textarea = document.createElement("textarea");
				textarea.textContent = text;
				textarea.style.position = "fixed";  // Prevent scrolling to bottom of page in MS Edge.
				document.body.appendChild(textarea);
				textarea.select();
				try {
					return document.execCommand("copy");  // Security exception may be thrown by some browsers.
				} catch (ex) {
					console.warn("Copy to clipboard failed.", ex);
					return false;
				} finally {
					document.body.removeChild(textarea);
				}
			}
		},                                
		change_step:function(step_to_go)
		{
			/* validation section */
			if(this.current_step=='post_type')
			{
				if(this.to_export=='')
				{
					$('.wt_pf_post_type_wrn').show();
					return false;
				}
                if($('#wt_pf_export_catalog_name').val()=='')
				{
					wt_pf_notify_msg.error(wt_pf_export_basic_params.msgs.export_fill_warn);
					return false;
				}
                var filename = $('#wt_pf_export_catalog_name').val();
                                
				if( wt_pf_export_basic_params.export_file_names.includes(filename)){
						wt_pf_notify_msg.error(wt_pf_export_basic_params.msgs.file_name_duplicate);
						return false;
				}
				if( filename == localStorage.getItem('wt_feed_filename') && wt_pf_basic_export.on_rerun == false ){
					wt_pf_notify_msg.error(wt_pf_export_basic_params.msgs.file_name_duplicate);
					return false;
				}
                                
			}else if(this.current_step=='method_export') /* method export page */ 
			{
				if(this.export_method=='template' && this.selected_template==0 && !this.is_previous_step(step_to_go))
				{	
					$('.wt_pf_warn').hide();
					$('.wt_pf_export_template_wrn').show();
					return false;
				}
			}

			/* setting fromdata */
			this.prepare_form_data();
			if('post_type' == step_to_go){
				// Back to channel selection - category filter changes will not reflect if didnt posted again.
				wt_pf_basic_export.get_page_dom_object(step_to_go).attr('data-loaded', 1);
				wt_pf_basic_export.get_page_dom_object('mapping').attr('data-loaded', 0);
				wt_pf_basic_export.get_page_dom_object('category_mapping').attr('data-loaded', 0);
				
				if('custom' == wt_pf_basic_export.to_export){
					if(!confirm(wt_pf_export_basic_params.msgs.changes_not_saved_warn)){
						return;
					}
				}
			}
			if('mapping' == step_to_go && this.on_rerun == true ){
				wt_pf_basic_export.get_page_dom_object('category_mapping').attr('data-loaded', 0);
			}
			/* step changing section */
			this.show_step_page(step_to_go, true);
		},
		reset_form_data:function()
		{
			this.form_data={};
			this.selected_template=0;
			this.selected_template_name='';
			this.export_method='';
		},
		prepare_form_data:function()
		{
			if(this.current_step=='post_type')
			{	
           
                var fm_data =$('.wt_pf_feed_filter_form').serializeArray();                                                        
                var filter_form_data={};
				$.each(fm_data, function(){
					if(filter_form_data[this.name])
					{
						if(!filter_form_data[this.name].push)
						{
							filter_form_data[this.name] = [filter_form_data[this.name]];
						}
						filter_form_data[this.name].push(this.value || '');
					}else
					{
						if(wt_pf_basic_export.is_multi_select(this.name))
						{
							filter_form_data[this.name] = [(this.value || '')];
						}else
						{
							filter_form_data[this.name] = this.value || '';
						}
					}
				});
				this.form_data['post_type_form_data']=JSON.stringify(filter_form_data);
			}
			else if(this.current_step=='method_export')
			{			
				var mapping_enabled_fields=new Array();
				$('.wt_pf_export_step_method_export [name="wt_pf_include_these_fields[]"]').each(function(){
					if($(this).is(':checked'))
					{
						mapping_enabled_fields.push($(this).val());
					}
				});
				this.form_data['method_export_form_data']=JSON.stringify({'method_export':wt_pf_basic_export.export_method, 'mapping_enabled_fields':mapping_enabled_fields, 'selected_template':this.selected_template});
			}
			else if(this.current_step=='filter')
			{
				if($('.wt_pf_export_filter_form').length>0)
				{
					var form_data=$('.wt_pf_export_filter_form').serializeArray();
					var filter_form_data={};
					$.each(form_data, function(){
						if(filter_form_data[this.name])
						{
							if(!filter_form_data[this.name].push)
							{
								filter_form_data[this.name] = [filter_form_data[this.name]];
							}
							filter_form_data[this.name].push(this.value || '');
						}else
						{
							if(wt_pf_basic_export.is_multi_select(this.name))
							{
								filter_form_data[this.name] = [(this.value || '')];
							}else
							{
								filter_form_data[this.name] = this.value || '';
							}
						}
					});
					this.form_data['filter_form_data']=JSON.stringify(filter_form_data);
				}
			}
			else if(this.current_step=='mapping')
			{				
                                                                
				/**
				* Default mapping fields  //===============================================
				*/

				var mapping_form_data={};
				var mapping_fields={};
				var mapping_selected_fields={}; /* this value is only for backend processing */

				$('.wt-pfd-exporter-default-mapping-tb tbody tr').each(function(){
					
                    if( !$(this).hasClass('wt_pf_dynamic_attr')){ // If it's not custom feed and dynamic crated rows                                                                                   
                                        
						var columns_key=$(this).find('.columns_key').val();
						var columns_val=$(this).find('.columns_val').val();
						if('wt-static-map-vl'===columns_val){
							var columns_val= 'wt_static_map_vl:'+$(this).find('.columns_static_val').val();
						}
						if('wt-compute-map-vl'===columns_val){
							var columns_val= 'wt_compute_map_vl:'+$(this).find('.columns_compute_val').val();
						}                                        
						
						if(wt_pf_basic_export.only_enabled_data===false) /* get whole keys instead of enabled/disabled */
						{
							var enabled=($(this).find('.columns_key').is(':checked') ? 1 : 0);
							mapping_fields[columns_key]=[columns_val,enabled];
							
							if(enabled==1)
							{
								mapping_selected_fields[columns_key]=columns_val;
							}

						}else
						{
							if($(this).find('.columns_key').is(':checked'))
							{
								mapping_fields[columns_key]=columns_val;
								mapping_selected_fields[columns_key]=columns_val;
							}
						}
                    }
				});
				// Only for dynamic created custom rows
				$(".wt_pf_dynamic_attr").each(function(index, element) {

					var $input_element = $(element).find('input[type="text"]');
					var columns_key= $input_element.val();
					var $select_element = $(element).find('select');
					var columns_val= $select_element.val();
					if (typeof columns_key != "undefined") {										
						if('wt-static-map-vl'===columns_val){
							var columns_val= 'wt_static_map_vl:'+$(element).find('input[name="columns_static_val[]"]').val();
						}
						if('wt-compute-map-vl'===columns_val){
							var columns_val= 'wt_compute_map_vl:'+$(element).find('input[name="columns_compute_val[]"]').val();
						}                                    
						mapping_fields[columns_key]=[columns_val,1];
						mapping_selected_fields[columns_key]=columns_val;
					}
				});                                

				var mapping_enabled_fields=new Array();
				/*
				$('.wt_pf_export_step_mapping [name="wt_pf_include_these_fields[]"]').each(function(){
					
					if($(this).is(':checked'))
					{
						mapping_enabled_fields.push($(this).val());
					}

				});
				*/

				mapping_form_data={'mapping_fields':mapping_fields,'mapping_enabled_fields':mapping_enabled_fields, 'mapping_selected_fields':mapping_selected_fields};
				this.form_data['mapping_form_data']=JSON.stringify(mapping_form_data);
				
				

				/**
				* meta mapping fields  //===============================================
				*/

				var meta_step_form_data={};
				var mapping_fields={};
				var mapping_selected_fields={}; /* this value is only for backend processing */

				$('.wt-pfd-exporter-meta-mapping-tb').each(function(){
					var mapping_key=$(this).attr('data-field-type');
					mapping_fields[mapping_key]={};
					mapping_selected_fields[mapping_key]={};
					
					$(this).find('tbody tr').each(function(){						
						if($(this).find('.columns_key').length>0 && $(this).find('.columns_val').length>0)
						{
							var columns_key=$(this).find('.columns_key').val();
							var columns_val=$(this).find('.columns_val').val();
							if('wt-static-map-vl'===columns_val){
								var columns_val= 'wt_static_map_vl:'+$(this).find('.columns_static_val').val();
							}
							if('wt-compute-map-vl'===columns_val){
								var columns_val= 'wt_compute_map_vl:'+$(this).find('.columns_compute_val').val();
							} 							
							if(wt_pf_basic_export.only_enabled_data===false) /* get whole keys instead of enabled/disabled */
							{
								var enabled=($(this).find('.columns_key').is(':checked') ? 1 : 0);
								mapping_fields[mapping_key][columns_key]=[columns_val,enabled];
								
								if(enabled==1)
								{
									mapping_selected_fields[mapping_key][columns_key]=columns_val;
								}

							}else
							{
								if($(this).find('.columns_key').is(':checked'))
								{
									mapping_fields[mapping_key][columns_key]=columns_val;
									mapping_selected_fields[mapping_key][columns_key]=columns_val;
								}
							}
						}
					});
				});

				meta_step_form_data={'mapping_fields':mapping_fields, 'mapping_selected_fields':mapping_selected_fields};
				this.form_data['meta_step_form_data']=JSON.stringify(meta_step_form_data);

			}else if(this.current_step == 'category_mapping'){     
                        
				var cat_mapping_fields={};
				$('.wt-pf-category-default-mapping-tb tbody tr').each(function(){
		
					var columns_key=$(this).find('th').find('label').attr('for');
					var columns_val=$(this).find('td').val();
                    cat_mapping_fields[columns_key] = $('#'+columns_key).val();
                                        
				});
                this.form_data['category_mapping_form_data']= JSON.stringify(cat_mapping_fields);
            }
                else
			{
				var current_form_data={};
				if($('.wt_pf_export_'+this.current_step+'_form').length>0) /* may be user hit the back button */
				{
					var form_data=$('.wt_pf_export_'+this.current_step+'_form').serializeArray();
					$.each(form_data, function(){
						
						if(current_form_data[this.name])
						{
							if(!current_form_data[this.name].push)
							{
								current_form_data[this.name] = [current_form_data[this.name]];
							}
							current_form_data[this.name].push(this.value || '');
						}else
						{
							current_form_data[this.name] = this.value || '';
						}

					});

					this.form_data[this.current_step+'_form_data']=JSON.stringify(current_form_data);
				}
			}

			/* we are resetting formdata on second step. If user not going to first step then post type formdata will be empty. */
			if(this.current_step!='post_type')
			{	

                var fm_data =$('.wt_pf_feed_filter_form').serializeArray();
                            
                var filter_form_data={};
					$.each(fm_data, function(){
						if(filter_form_data[this.name])
						{
							if(!filter_form_data[this.name].push)
							{
								filter_form_data[this.name] = [filter_form_data[this.name]];
							}
							filter_form_data[this.name].push(this.value || '');
						}else
						{
							if(wt_pf_basic_export.is_multi_select(this.name))
							{
								filter_form_data[this.name] = [(this.value || '')];
							}else
							{
								filter_form_data[this.name] = this.value || '';
							}
						}
					});
                    this.form_data['post_type_form_data']=JSON.stringify(filter_form_data);         
			}

		},
		is_multi_select:function(name)
		{
			var elm=$('[name="'+name+'"]');
			if(elm.prop("tagName").toLowerCase()=='select' && this.has_attr(elm,'multiple'))
			{
				return true;
			}else
			{
				return false;
			}
		},
		has_attr:function(elm,attr_name)
		{
			var attr = elm.attr(attr_name);
			if(typeof attr!==typeof undefined  &&  attr!==false)
			{
				return true;
			}else
			{
				return false;
			}
		},
		is_previous_step:function(step_key)
		{
			if(wt_pf_export_basic_params.steps.hasOwnProperty(step_key)) 
			{				
				if(this.step_keys.indexOf(step_key)<this.step_keys.indexOf(this.current_step))
				{
					return true;
				}
			}
			return false;
		},
		is_step_loaded:function(step)
		{
			if(this.get_page_dom_object(step).length==0){ return true; } /* block infinite loop, if element is not available */ 
			return (this.get_page_dom_object(step).attr('data-loaded')==this.loaded_status_arr['loaded']);
		},
		set_step_loading_status:function(steps, status)
		{
			$.each(steps, function(step_ind, step){
			  	wt_pf_basic_export.get_page_dom_object(step).attr('data-loaded', wt_pf_basic_export.loaded_status_arr[status]);
			});
		},
		show_step_page:function(step, force_check_loaded)
		{
			if('advanced' !== step){ 
				// Change file type list when moving to advanced step based on channel selected
				this.get_page_dom_object('advanced').attr('data-loaded',0);                            
			}
			$('.wt_pf_export_step').hide();	
			this.get_page_dom_object(step).show();
			this.current_step=step;
			if(force_check_loaded)
			{	
				if(this.is_step_loaded(step))
				{
					this.current_step_actions();
				}else
				{
					this.refresh_step(true);
				}
			}else
			{
				this.current_step_actions();
			}
			wt_pf_form_toggler.runToggler();
		},
		current_step_actions:function() /* current page actions after page is visible */
		{
			if(this.current_step=='filter' || this.current_step=='mapping')
			{
				if(this.is_step_loaded('mapping'))
				{
					this.load_meta_mapping_fields();
				}else
				{
					this.load_steps(['mapping']);
				}
				
			}else if(this.current_step=='advanced')
			{
				wt_field_group.Set();
			}
		},
		show_post_type_name:function()
		{
			if(this.to_export!="" && this.to_export_title=='')
			{
				$('[name="wt_pf_export_post_type"]').val(this.to_export);
				if(wt_pf_export_basic_params.to_export !== ''){
					$('[name="wt_pf_export_post_type"]').trigger('change');
				}
				this.to_export_title=$('[name="wt_pf_export_post_type"] option:selected').text();                                
			}
                        
            if(this.to_export_title.includes('Product'))
			$('#product-type-message').show();                         
                        
			$('.wt_pf_step_head_post_type_name').html(this.to_export_title);
			$('.wt-ier-gopro-cta').hide();
			$('.wt-ier-'+this.to_export).show();
			
			$('.wt_pf_free_addon').hide();
			$('.wt_pf_export_action_btn').prop('disabled', false);

		},
		page_actions:function(step)
		{
			if(step=='post_type') /* post type page */
			{
                $('.wc-enhanced-select').select2();
				$( document.body ).trigger( 'wc-enhanced-select-init' );
				$('[name="wt_pf_export_post_type"]').off('change').on('change',function(){
					                
					wt_pf_basic_export.to_export=$(this).val();
					wt_pf_basic_export.to_export_title='';
					wt_pf_basic_export.reset_form_data();
					$('.wt_pf_post_type_name').html('');
					if(wt_pf_basic_export.to_export=='')
					{
						$('.wt_pf_post_type_wrn').show();
					}else
					{
						$('.wt_pf_post_type_wrn').hide();
						var post_type_name=$('[name="wt_pf_export_post_type"] option:selected').text();
						/* $('.wt_pf_post_type_name').html(': '+post_type_name); */
						wt_pf_basic_export.to_export_title=post_type_name;
						wt_pf_basic_export.show_step_page('post_type', true);	
					}
				});
                                
				var selected_catalog_interval = $('#wt_pf_export_catalog_interval').val();
				if( 'daily' == selected_catalog_interval || 'weekly' == selected_catalog_interval || 'monthly' == selected_catalog_interval ){
					wt_pf_basic_export.show_cron_intervals(selected_catalog_interval);
				}
                                
				$('[name="wt_pf_export_catalog_interval"]').off('change').on('change',function(){
					                                    
					var selected_interval = $(this).val();
					wt_pf_basic_export.show_cron_intervals(selected_interval);

				});   
                                    
				var qty_var_selection = $('[name="wt_pf_include_variations_type"]').val();  
				if ( '' !== qty_var_selection) {
					$('.wt_feed_parent_options').show();
				}
				$('[name="wt_pf_include_variations_type"]').off('change').on('change',function(){    
					var selectedValue = $(this).val();           
					if ( '' !== selectedValue) {
						$('.wt_feed_parent_options').show();
					} else {
						$('.wt_feed_parent_options').hide();
					}
				});    
			}
			else if(step=='method_export') /* method export page */ 
			{
				this.export_method=$('[name="wt_pf_export_method_export"]:checked').val();
				this.toggle_export_method_options();

				$('[name="wt_pf_export_method_export"]').off('click').on('click',function(){
					var vl=$(this).val();
					if(wt_pf_basic_export.export_method==vl)
					{
						return false;
					}
					wt_pf_basic_export.reset_form_data();
					wt_pf_basic_export.export_method=vl;
					if(wt_pf_basic_export.export_method=='template')
					{
						if($('.wt-pfd-export-template-sele').val()==0)
						{
							wt_pf_basic_export.refresh_step();
						}else
						{
							wt_pf_basic_export.selected_template=$('.wt-pfd-export-template-sele').val();
							wt_pf_basic_export.selected_template_name=$.trim($('.wt-pfd-export-template-sele option:selected').text());
							wt_pf_basic_export.load_pending_steps();
						}		
					}else
					{ 
						wt_pf_basic_export.load_pending_steps();
					}					
				});

				$('.wt-pfd-export-template-sele').off('change').on('change',function(){
					wt_pf_basic_export.selected_template=$(this).val();
					$('.wt_pf_warn').hide();				
					if(wt_pf_basic_export.selected_template==0)
					{
						$('.wt_pf_export_template_wrn').show();
					}else
					{
						wt_pf_basic_export.selected_template_name=$.trim($('.wt-pfd-export-template-sele option:selected').text());
						wt_pf_basic_export.load_pending_steps();
					}
				});
                wt_pf_basic_export.warn_on_refresh();
			}
            else if(step=='category_mapping') /* filter page */ 
			{
				$('.wc-enhanced-select').select2();
				$( document.body ).trigger( 'wc-enhanced-select-init' );
                                wt_pf_basic_export.woo_cat_search();
			}                                                
			else if(step=='filter') /* filter page */ 
			{
				$('.wc-enhanced-select').select2();
				$( document.body ).trigger( 'wc-enhanced-select-init' );
                                wt_pf_basic_export.warn_on_refresh();
			}
			else if(step=='mapping') /* mapping page */ 
			{
				$('.wc-enhanced-select').select2();
				
				$('#wt_pf_channel_selected').text(this.capitalizeFirstLetter(this.to_export));
				this.enable_sortable();
				this.mapping_box_accordian();
				this.reg_mapping_field_bulk_action();
				wt_pf_basic_export.warn_on_refresh();
				wt_pf_basic_export.mapping_select_change();
				wt_pf_basic_export.reg_remove_row_action();
                                
			}
			else if(step=='advanced')
			{	
				/* callback for external adapters */
				if($('select[name="wt_pf_file_into"]').length>0) /* multiple adapter exists so select box */
				{
					var file_into=$('[name="wt_pf_file_into"]').val();
					$('[name="wt_pf_file_into"]').off('change').on('change',function(){
						var file_into=$(this).val();
						wt_pf_set_file_into_fields(file_into);
					});
				}else  /* radio button */
				{
					var file_into=$('[name="wt_pf_file_into"]:checked').val();
					$('[name="wt_pf_file_into"]').on('click',function(){
						var file_into=$('[name="wt_pf_file_into"]:checked').val();
						wt_pf_set_file_into_fields(file_into);
					});
				}
				wt_pf_set_file_into_fields(file_into);

				/* separate image export option */
				if($('.wt_pf_separate_image_export').length>0) /* separate image export option available */
				{
					if($('.wt_pf_separate_image_export:checked').val()=='Yes')
					{
						$('.pf_export_image_btn').show();
					}else
					{
						$('.pf_export_image_btn').hide();
					}
				}else
				{
					$('.pf_export_image_btn').hide();
				}
				$('.wt_pf_separate_image_export').off('click').on('click',function(){
					if($(this).val()=='Yes')
					{
						$('.pf_export_image_btn').show();
					}else
					{
						$('.pf_export_image_btn').hide();
					}
				});

				/* CSV delimiter form toggler. Custom and preset delimiter */
				wt_pf_custom_and_preset.delimiter_toggler();

				/* file extension info box */
				$('.wt_pf_file_ext_info').html('.'+$('[name="wt_pf_file_as"]').val());
				$('[name="wt_pf_file_as"]').off('change').on('change',function(){
					$('.wt_pf_file_ext_info').html('.'+$(this).val());
				});
                wt_pf_basic_export.warn_on_refresh();
			}

			/* common events */
			if($('.wt_pf_datepicker').length>0)
			{
				$('.wt_pf_datepicker').datepicker({dateFormat: 'yy-mm-dd'});
			}
			wt_field_group.Set();
			wt_pf_form_toggler.Set();
			wt_pf_conditional_help_text.Set(this.get_page_dom_object(step));

		},
        woo_cat_search : function(){
                    
		$('.wt-wc-enhanced-search').selectWoo({
			
			ajax: {                                      
				dataType: 'json',
				delay:       1000,
				type: 'POST',
				url:wt_pf_basic_params.ajax_url,
				
				data: function(params){     
					return {
						'term' : params.term,
					'_wpnonce': wt_pf_basic_params.nonces.main,
					'action': "populate_cat_mapping",
					'export_action': 'populate_cat_mapping',
					'selected_template': wt_pf_basic_export.selected_template,
					'to_export': wt_pf_basic_export.to_export,
					'export_method': wt_pf_basic_export.export_method
				};
				},
				processResults: function( data ) {
						var terms = [];
						if ( data ) {
								$.each( data, function( id, text ) {
										terms.push( { id: id, text: text } );
								});
						}
						return {
								results: terms
						};
				},
				cache: true                                        
			},
			placeholder: wt_pf_basic_params.msgs.search_category_placeholder,
			minimumInputLength: 3
			});  
        },                
		enable_sortable:function()
		{
			$('.wtpf_meta_mapping_box_con[data-sortable="0"]').each(function(){
				var tb=$(this).find(".wt-pfd-mapping-tb tbody");
				if(tb.length>0)
				{
					tb.sortable({
						handle: ".wt_pf_sort_handle",
						placeholder: "wt-pfd-sortable-placeholder",
						forcePlaceholderSize: true,
						revert:true
					});
					$(this).attr('data-sortable', 1);
				}
			});			
		},
		toggle_export_method_options:function()
		{
			$('.wt-pfd-export-method-options').hide();
			$('.wt-pfd-export-method-options-'+this.export_method).show();
		},
		mapping_box_accordian:function()
		{
			$('.wtpf_meta_mapping_box_hd').off('click').on('click',function()
			{
				var c_dv=$(this).parents('.wtpf_meta_mapping_box').find('.wtpf_meta_mapping_box_con');
				if(c_dv.is(':visible'))
				{
					c_dv.hide();
					$(this).find('.dashicons').removeClass('dashicons-arrow-down').addClass('dashicons-arrow-right');
				}else
				{
					c_dv.show();
					c_dv.find(".wt-pfd-mapping-tb tbody tr td").each(function(){
						$(this).css({'width':$(this).width()});
					});
					$(this).find('.dashicons').removeClass('dashicons-arrow-right').addClass('dashicons-arrow-down');
				}
			});
		},
		mapping_fields_selected_count:function(tb_elm)
		{
			tb_elm.parents('.wtpf_meta_mapping_box').find('.wtpf_meta_mapping_box_selected_count_box_num').text(tb_elm.find('.wt_pf_mapping_checkbox_sub:checked').length);
		},
		mapping_select_change:function(){
											
			$('.columns_val').on('change', function(){
				
				var parent_tr = $(this).closest('tr');

				parent_tr.find('.columns_static_val').hide();
				parent_tr.find('.columns_compute_val').hide();
				parent_tr.find('.columns_static_td').hide();
				parent_tr.find('.columns_compute_td').hide();                        
				if('' === $(this).val()){
					parent_tr.find('.wt_pf_mapping_checkbox_sub').prop('checked',false);
				}else if('wt-static-map-vl' === $(this).val()){
					parent_tr.find('.columns_compute_val').hide();
					parent_tr.find('.columns_static_td').show();
					parent_tr.find('.columns_static_val').show();
					parent_tr.find('.columns_static_val').focus();
					parent_tr.find('.wt_pf_mapping_checkbox_sub').prop('checked',true);
				}else if('wt-compute-map-vl' === $(this).val()){
					parent_tr.find('.columns_static_val').hide();
					parent_tr.find('.columns_compute_td').show();
					parent_tr.find('.columns_compute_val').show();
					parent_tr.find('.columns_compute_val').focus();
					parent_tr.find('.wt_pf_mapping_checkbox_sub').prop('checked',true);
				}else{
					parent_tr.find('.wt_pf_mapping_checkbox_sub').prop('checked',true);
				}
			});
		},
		reg_mapping_field_bulk_action:function()
		{
			$('.wt_pf_mapping_checkbox_main').each(function()
			{
				var tb=$(this).parents('.wt-pfd-mapping-tb');
				if(tb.find('.wt_pf_mapping_checkbox_sub:checked').length==tb.find('.wt_pf_mapping_checkbox_sub').length)
				{
					tb.find('.wt_pf_mapping_checkbox_main').prop('checked',true);
				}else
				{
					tb.find('.wt_pf_mapping_checkbox_main').prop('checked',false);
				}
				wt_pf_basic_export.mapping_fields_selected_count(tb);
			});

			$('.wt_pf_mapping_checkbox_main').off('click').on('click',function()
			{
				var tb=$(this).parents('.wt-pfd-mapping-tb');
				if($(this).is(':checked'))
				{
					tb.find('.wt_pf_mapping_checkbox_sub').prop('checked',true);
				}else
				{
					tb.find('.wt_pf_mapping_checkbox_sub').prop('checked',false);
				}
				wt_pf_basic_export.mapping_fields_selected_count(tb);
			});
			$('.wt_pf_mapping_checkbox_sub').off('click').on('click',function()
			{
				var tb=$(this).parents('.wt-pfd-mapping-tb');
				if($(this).is(':checked') && tb.find('.wt_pf_mapping_checkbox_sub:checked').length==tb.find('.wt_pf_mapping_checkbox_sub').length)
				{
					tb.find('.wt_pf_mapping_checkbox_main').prop('checked',true);
				}else
				{
					tb.find('.wt_pf_mapping_checkbox_main').prop('checked',false);
				}
				wt_pf_basic_export.mapping_fields_selected_count(tb);
			});
		},
		set_step_page:function(data)
		{
			page_html=data.page_html;
			$.each(page_html, function(step_id, page_content){
			  	wt_pf_basic_export.get_page_dom_object(step_id).html(page_content);
			  	wt_pf_basic_export.page_actions(step_id);
			  	
			  	if(step_id=='method_export' && (wt_pf_basic_export.selected_template>0 || wt_pf_basic_export.on_rerun))
			  	{
			  		wt_pf_basic_export.form_data=data.template_data;
			  		
			  		if(wt_pf_basic_export.on_rerun)
			  		{
			  			if($('.wt-pfd-export-template-sele').val()==0)
						{
							wt_pf_basic_export.selected_template=0;
							wt_pf_basic_export.selected_template_name='';
						}else
						{
							wt_pf_basic_export.selected_template=$('.wt-pfd-export-template-sele').val();
							wt_pf_basic_export.selected_template_name=$.trim($('.wt-pfd-export-template-sele option:selected').text());
						}
			  		}
			  	}
			  	wt_pf_basic_export.show_post_type_name();
			});
			this.reg_button_actions();
		},
		prepare_ajax_data:function(action, data_type)
		{
                    
			this.ajax_data = {
	            '_wpnonce': wt_pf_basic_params.nonces.main,
	            'action': "pf_export_ajax_pro",
	            'export_action': action,
	            'selected_template': this.selected_template,
	            'to_export': this.to_export,
	            'data_type': data_type,
	            'export_method': this.export_method,
                    'cat_filter_type': jQuery('#wt_pf_export_cat_filter_type').val(),
                    'inc_exc_cat': jQuery('#wt_pf_inc_exc_category').val(),
                    'brand_filter_type': jQuery('#wt_pf_export_brand_filter_type').val(),
                    'inc_exc_brand': jQuery('#wt_pf_inc_exc_brand').val()                    
	        };
		},
		warn_on_refresh: function () {
			window.onbeforeunload = function (event)
			{
				if(!wt_pf_basic_export.export_finished){
					return confirm(wt_pf_basic_params.msgs.changes_not_saved_warn);
				}
			};
		},
		capitalizeFirstLetter: function(string) {
			
			const words = string.split("_");
			for (let i = 0; i < words.length; i++) {
				words[i] = words[i][0].toUpperCase() + words[i].substr(1);
			}
			return words.join(" ");                    
			//return string.charAt(0).toUpperCase() + string.slice(1);
		},
        show_cron_intervals:function(selected_interval) {
			var show_time = false;
			if ('daily' == selected_interval) {
				show_time = true;
				jQuery('.wt_feed_schedule_options_dayofmonth, .wt_feed_schedule_options_days').hide();
			}
			if ('weekly' == selected_interval) {
				show_time = true;
				jQuery('.wt_feed_schedule_options_dayofmonth').hide();
				jQuery('.wt_feed_schedule_options_days').show();
			}
			if ('monthly' == selected_interval) {
				show_time = true;
				jQuery('.wt_feed_schedule_options_dayofmonth').show();
				jQuery('.wt_feed_schedule_options_days').hide();
			}
			if (show_time) {
				jQuery('.wt_feed_schedule_options_time').show();
			} else {
				jQuery('.wt_feed_schedule_options').hide();
			}
			if('manual' == selected_interval)
			{
				jQuery('.wt_feed_schedule_options_cron_type').hide();
			}else{
				jQuery('.wt_feed_schedule_options_cron_type').show();
			}
			
		}
	}
	return wt_pf_basic_export;
	
})( jQuery );

jQuery(function() {
	
	if(wt_pf_export_basic_params.rerun_id>0)
	{
		wt_pf_basic_export.to_export=wt_pf_export_basic_params.to_export;
		wt_pf_basic_export.export_method=wt_pf_export_basic_params.export_method;
		wt_pf_basic_export.rerun_id=wt_pf_export_basic_params.rerun_id;
		wt_pf_basic_export.on_rerun=true;
                setTimeout(function(){ wt_pf_basic_export.show_step_page('post_type', true); }, 2000);
	}
	wt_pf_basic_export.Set();	
});