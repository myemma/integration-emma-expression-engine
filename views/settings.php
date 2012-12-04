

<?php
$attributes = array('id' => 'emma_settings_form');
echo form_open($action_url,$attributes);

$this->table->set_template($cp_pad_table_template);
$this->table->set_heading(
    array('data' => lang('preference'), 'style' => 'width:50%;'),
    lang('setting')
);

	$this->table->add_row(array(
			lang('emma_api_key', 'emma_api_key'),
			form_input('emma_api_key', $emma_api_key, 'class="field"')
		)
	);
    	$this->table->add_row(array(
			lang('emma_username', 'emma_username'),
			form_input('emma_username', $emma_username, 'class="field"')
		)
	);
	$this->table->add_row(array(
			lang('emma_password', 'emma_password'),
			form_password('emma_password', $emma_password, 'class="password"')
		)
	);
	
echo $this->table->generate();

?>

	<?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit'))?>

<?=form_close()?>

		<script>$("#emma_settings_form").submit(function () {
        var return_value;
        fv_url=(("<?php echo htmlspecialchars_decode($fv_url)?>"));
         $.ajax({
                type:'POST',
                url:fv_url,
                async:false,
                data:$("#emma_settings_form").serialize(),
                beforeSend: function() {
                    $("#emma_settings_form input.submit").after('<span id="submit_loading">&nbsp&nbsp;&nbsp&nbsp;<img alt="Loading" src="themes/cp_themes/default/images/indicator.gif"></span>');

                },		
                complete: function() {
                   $('#submit_loading').remove();
                },	                 
                success: function(msg){         
                    if(msg==1)
                    {
                        return_value=true;
                    }
                    else
                    {
                        var data = jQuery.parseJSON(msg);
                        var error_msg='';
                        $.each(data, function(key, value){
                            error_msg +=value+"<br>";
                           // $("#"+key).addClass('errorInput');
                        });                        
                        $.ee_notice(error_msg,{"type" : "error"});
                        return_value=false;
                    }
                }
          });         
          return return_value;      

		});
        </script>