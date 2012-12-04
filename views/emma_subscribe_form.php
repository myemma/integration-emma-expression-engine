<?php 
    if($message)
    {?>
    <div> <span style="color:<?php echo $message_color?>"><?php echo $message?></span>
    <?php }  
    $attributes = array('id' => 'emma_subscribe_form');
    echo form_open('http://camping5.empressem.in/EE/index.php/site/subscribe',$attributes);
    $this->table->add_row(array(
            lang('Select the groups you want to subscribe [Uncheck for Unsubscribing] ', 'group_list'),
            '<div style="overflow: auto; max-height: 100px;">'.form_multiselect('group_list[]', $groups,$member_group_ids).'</div>',
        )
    );     

    echo $this->table->generate();
    echo form_hidden('emma_member_id', $member_id, 'class="field"');
    echo form_hidden('emma_subscribe', '1', 'class="field"')
?>
	<?=form_submit(array('name' => 'submit', 'value' => lang('Submit'), 'class' => 'submit'))?>
<?=form_close()?>    