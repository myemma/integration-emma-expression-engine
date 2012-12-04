<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Emma
 *
 * @package		Emma
 * @subpackage	ThirdParty
 * @category	Modules
 * @author		Maju Ansari
 * @link		http://www.empressem.in
 */
class Emma_mcp
{
	var $base;			// the base url for this module			
	var $form_base;		// base url for forms
	var $module_name = "emma";	
    var $emmm_stats_group_name;
	public $settings = array();
	
	/**
	 * Does this extension have a settings screen?
	 *
	 * @access	public
	 * @var		string
	 */
	public $settings_exist = 'y';
	
    /**
     * @var Devkit_code_completion
     */
    var $EE;

	function Emma_mcp( $switch = TRUE )
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance(); 
		$this->base	 	 = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.$this->module_name;
		$this->form_base = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.$this->module_name;
				$this->EE->cp->set_right_nav(array(
					'settings'	=> $this->base.AMP.'method=settings',
                    'emma_list_manage'	=> $this->base.AMP.'method=emma_lists',
					'emma_stats'	=> $this->base.AMP.'method=emma_stats',
				));
        $this->EE->load->model('emma_model');        
        // uncomment this if you want navigation buttons at the top
/*		$this->EE->cp->set_right_nav(array(
				'home'			=> $this->base,
				'some_language_key'	=> $this->base.AMP.'method=some_method_here',
			));
*/			
	}

	function index() 
	{
		return $this->settings();
	}

	
	public function settings()
	{
		//$this->_permissions_check();
		$this->EE->load->library('table');

		$vars = array('action_url' => 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=emma'.AMP.'method=save_settings'
		);

		$this->EE->cp->set_variable('cp_page_title', lang('emma_settings'));

		$this->EE->cp->set_variable('cp_breadcrumbs', array(
			$this->base => lang('Emma')));

		$vars['emma_api_key']			= ($this->EE->config->item('emma_api_key'));
		$vars['emma_username']	= ($this->EE->config->item('emma_username'));
		$vars['emma_password']	= ($this->EE->config->item('emma_password'));
        $vars['fv_url']	= $this->base.AMP.'method=emma_settings_validation';
		return $this->EE->load->view('settings', $vars, TRUE);
	}
    public function emma_settings_validation()
    {
        $this->EE->load->library('form_validation');
        $this->EE->form_validation->set_rules('emma_api_key', 'Emma Api Key', 'required');
        $this->EE->form_validation->set_rules('emma_username', 'Emma Username', 'required');
        $this->EE->form_validation->set_rules('emma_password', 'Emma Password', 'required');
        $valid_form = $this->EE->form_validation->run();
        if ($valid_form)
        {
            echo 1;
            exit();
        }
        else
        {
             echo json_encode($this->EE->form_validation->_error_array);
             exit();
        }
    }    
	public function save_settings()
	{

		$insert['emma_api_key'] = $this->EE->input->post('emma_api_key');
		$insert['emma_username'] = $this->EE->input->post('emma_username');
		$insert['emma_password'] = $this->EE->input->post('emma_password');

		$this->EE->config->_update_config($insert);


		$this->EE->session->set_flashdata('message_success', lang('settings_updated'));

		$this->EE->functions->redirect($this->base.AMP.'method=settings');
	}  

/*
Code for Emma Lists
*/    
    public function emma_lists()
    {
        $this->EE->load->library('table');
        $rows =$groups= array();
        $groups=  $this->EE->emma_model->getGroups();
        $i=1;
        if($groups)
        {
          foreach($groups as $group)
          {
            $first_day=date('Y-m')."-01";
            $first_day_time=strTotime($first_day);//first day of month
            /*$members=  ($this->EE->emma_model->getGroupMembers($group->member_group_id));
            foreach($members as $member)
            {
                $time_date=str_replace('@D:','',$member->member_since);
                $time_date=str_replace('T',' ', $time_date);
                $member_since_time=strtotime($time_date);
                if($member_since_time > $first_day_time && $member->status=="active")
                {
                    $new_subscriber_count++;
                }
            }*/
            $attr = array(
                'onclick'=>"return confirm('Are you sure to Delete this Group?')"
            );            
            $actions = array(
              anchor( $this->base.AMP.'method=emma_add_edit_group_form&id='.$group->member_group_id,lang('Rename')),
              anchor($this->base.AMP.'method=emma_group_delete_submit&id='.$group->member_group_id.'&group_id='.$group->member_group_id,lang('Delete'),$attr)
            );  
            $rows[] = array(
              $i,  
              anchor( $this->base.AMP.'method=emma_group_details&id='.$group->member_group_id,$group->group_name),
              $group->active_count+$group->optout_count+$group->error_count,
              $group->active_count,
              $group->optout_count,
             // $new_subscriber_count,
              ($group->group_type=='g')?lang('Regular'):lang('Test'),      
              implode(' | ', $actions),
            );  
            $i++; 
         }    
        }
        $header=array(lang('No:'),lang('Name'), lang('Total Users'), lang('Active'), lang('Opt out'),lang('Type'), lang('Actions'));
		$this->EE->cp->set_variable('cp_page_title', lang('Emma Lists'));

		$this->EE->cp->set_variable('cp_breadcrumbs', array(
			$this->base => lang('Emma')));   
        $add_group_anchor=anchor( $this->base.AMP.'method=emma_add_edit_group_form',lang('Add a New Group'));    
		$vars = array('rows'=>$rows,
                      'header'=>$header,
                      'add_group_anchor'=>$add_group_anchor
        );
		return $this->EE->load->view('emma_lists', $vars, TRUE);                    
  
    }
    
    public function emma_add_edit_group_form()
    {
        $this->EE->load->library('table');
        if($group_id=$this->EE->input->get('id'))
        {
            $title=lang("Edit Group");
            $group_info=$this->EE->emma_model->getEmmaGroupInfo($group_id);
            $vars=array(
                    'action_url' => 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=emma'.AMP.'method=emma_edit_group_form_submit&id='.$group_id,  
                    'emma_group_name'=>$group_info->group_name,
                    'edit'=>1,
            );        
        }
        else
        {
            $title=lang("Add Group");
            $vars=array(
                    'action_url' => 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=emma'.AMP.'method=emma_add_group_form_submit',  
                    'emma_group_name'=>'',
                    'edit'=>0,
            );
        }
		$this->EE->cp->set_variable('cp_page_title', $title);

		$this->EE->cp->set_variable('cp_breadcrumbs', array(
			$this->base => lang('Emma'),
            $this->base.AMP.'method=emma_lists'=>lang('Emma List'),
            ));           
        $vars['fv_url']	= $this->base.AMP.'method=emma_add_edit_group_form_validation';
        return $this->EE->load->view('emma_add_edit_group_form', $vars, TRUE);                    
    }
    
    public function emma_add_edit_group_form_validation()
    {
        $this->EE->load->library('form_validation');
        $this->EE->form_validation->set_rules('emma_group_name', 'Group Name', 'required');
        $valid_form = $this->EE->form_validation->run();
        if ($valid_form)
        {
            echo 1;
            exit();
        }
        else
        {
             echo json_encode($this->EE->form_validation->_error_array);
             exit();
        }
    }     
    
    public function emma_add_group_form_submit()
    {
        $group = array( 'groups' => array( array(
                                            'group_name' => $this->EE->input->post('emma_group_name'),
                                            'group_type' => ($this->EE->input->post('test_group'))?'t':'g'
                                            ) ) );    
        $response=$this->EE->emma_model->createEmmaGroup($group);
        if(isset($response[0]->member_group_id))
        {
            $this->EE->session->set_flashdata('message_success', lang('New Group Created'));
            $this->EE->functions->redirect($this->base.AMP.'method=emma_add_edit_group_form');
          
        }  
        else
        {
            $this->EE->session->set_flashdata('message_failure', lang('Ooops...Something went wrong'));
        }
    }
    
    public function emma_edit_group_form_submit()
    {
        $response=$this->EE->emma_model->editEmmaGroup(intval($this->EE->input->get('id')),$this->EE->input->post('emma_group_name'));
        if($response && !isset($response->error)) 
        {
            $this->EE->session->set_flashdata('message_success', lang('Update Successfull !!!'));
        }
        else
        {
            $this->EE->session->set_flashdata('message_failure', lang('Ooops...Something went wrong'));
        }      
        $this->EE->functions->redirect($this->base.AMP.'method=emma_add_edit_group_form&id='.$this->EE->input->get('id'));        
    }
    public function emma_group_delete_submit()
    {
        $response=$this->EE->emma_model->deleteEmmaGroup(intval($this->EE->input->get('id')));
        if($response && !isset($response->error)) 
        {
            $this->EE->session->set_flashdata('message_success', lang('Update Successfull !!!'));
        }
        else
        {
            $this->EE->session->set_flashdata('message_failure', lang('Ooops...Something went wrong'));
        }      
        $this->EE->functions->redirect($this->base.AMP.'method=emma_lists');        
    }    
    
    public function emma_group_details()
    {
        $this->EE->load->library('table');
        $group=$_GET['id'];
        $members=$this->EE->emma_model->getGroupMembers($group);
        $rows = array();
        $i=1;
        foreach($members as $member)
        {
            $time_date=str_replace('@D:','',$member->member_since);
            //list($date,$time)=explode('T',$time_date);
            $time_date=str_replace('T',' ', $time_date);
            $member_since= date('l jS \of F Y h:i:s A', strtotime($time_date));  
            $attr = array(
            'onclick'=>"return confirm('Are you sure to Delete this User?')"
            );              
            $actions = array(
              anchor( $this->base.AMP.'method=emma_edit_user_status_form&id='.$member->member_id.'&group_id='.$group,lang('Edit Status')),
              anchor( $this->base.AMP.'method=emma_add_edit_user_form&id='.$member->member_id.'&group_id='.$group,lang('Edit Details')),
              anchor($this->base.AMP.'method=emma_user_delete_submit&id='.$member->member_id.'&group_id='.$group,lang('Delete'),$attr),
            );  
            $rows[$member->member_id] = array(
              $i,  
              ((isset($member->fields->first_name))?$member->fields->first_name:'')." ".((isset($member->fields->last_name))?$member->fields->last_name:''),
              $member->email,
              $member_since,
              ucfirst($member->status),
              implode(' | ', $actions),
            );  
            $i++; 
        }         
        $add_group_anchor=anchor( $this->base.AMP.'method=emma_add_edit_user_form&group_id='.$group,lang('Add a New User'));   
        $header=array(lang('No:'),lang('Name'), lang('Email'),lang('Member Since'), lang('Status'), lang('Actions'));
		$this->EE->cp->set_variable('cp_page_title', lang('Emma Group Members'));

		$this->EE->cp->set_variable('cp_breadcrumbs', 
                array(
                    $this->base => lang('Emma'),
                    $this->base.AMP.'method=emma_lists' => lang('Emma Lists'),
                    )
            );          
		$vars = array('rows'=>$rows,
                      'header'=>$header,
                      'add_group_anchor'=>$add_group_anchor
        );
		return $this->EE->load->view('emma_group_details', $vars, TRUE);           
    }
    
    public function emma_add_edit_user_form()
    {
        $this->EE->load->library('table');
        $member_details=array();
        $member_group_ids=array();
        if(isset($_GET['id']))
        {
           $member_details= $this->EE->emma_model->get_member_detail($_GET['id']);
           $action_url = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=emma'.AMP.'method=emma_add_user_form_submit&id='.$_GET['id']."&group_id=".$_GET['group_id'] ;
           $fv_url      =$this->base.AMP.'method=emma_add_user_form_validation&id='.$_GET['id'] ;
           $title="Edit a Member";
            $member_groups=  ($this->EE->emma_model->getMemberGroups($_GET['id']));
            foreach($member_groups as $group)
            {
                $member_group_ids[]=$group->member_group_id;
            }               
        }
        else
        {
            $action_url = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=emma'.AMP.'method=emma_add_user_form_submit&group_id='.$_GET['group_id'] ; 
            $fv_url      =$this->base.AMP.'method=emma_add_user_form_validation'     ;
            $title="Add a new Member";
        }
		$this->EE->cp->set_variable('cp_breadcrumbs', array(
			$this->base => lang('Emma')));  
            
        $fields=$this->EE->emma_model->getFields();
        $groups=$this->EE->emma_model->getGroups();
        foreach($groups as $group)
        {
            $groups_list[$group->member_group_id]=$group->group_name;
        }
   
		$this->EE->cp->set_variable('cp_page_title', lang($title));

		$this->EE->cp->set_variable('cp_breadcrumbs', 
                array(
                    $this->base => lang('Emma'),
                    $this->base.AMP.'method=emma_lists' => lang('Emma Lists'),
                    $this->base.AMP.'method=emma_group_details&id='.$_GET['group_id'] => lang('Emma Group Members'),
                    )
            );               
		$vars = array(
                        'fields'=>$fields,
                        'groups_list'=>$groups_list,
                        'member_details'=>$member_details,
                        'action_url'=>$action_url,
                        'fv_url'	=> $fv_url ,
                        'member_group_ids'	=> $member_group_ids ,
        );        
		return $this->EE->load->view('emma_add_edit_user_form', $vars, TRUE);              
    }
    
    public function emma_edit_user_status_form()    
    {
        $this->EE->load->library('table');
        $member_details= $this->EE->emma_model->get_member_detail($_GET['id']);
        $user_status=substr($member_details->status, 0, 1);
		$this->EE->cp->set_variable('cp_page_title', lang('Emma Member Status'));

		$this->EE->cp->set_variable('cp_breadcrumbs', 
                array(
                    $this->base => lang('Emma'),
                    $this->base.AMP.'method=emma_lists' => lang('Emma Lists'),
                    $this->base.AMP.'method=emma_group_details&id='.$_GET['group_id'] => lang('Emma Group Members'),
                    )
            );                  
		$vars = array(
                        'user_status'=>$user_status,
                        'action_url'=>'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=emma'.AMP.'method=emma_edit_user_status_form_submit&id='.$_GET['id'].'&current_status='.$user_status.'&group_id='.$_GET['group_id'],
        );       
        return $this->EE->load->view('emma_edit_user_status_form', $vars, TRUE);  
    }
    
    public function emma_add_user_form_validation()
    {
        $this->EE->load->library('form_validation');
        $this->EE->form_validation->set_rules('emma_email', 'Emma Email', 'trim|required|valid_email');
        $valid_form = $this->EE->form_validation->run();
        if ($valid_form)
        {
            echo 1;
            exit();
        }
        else
        {
             echo json_encode($this->EE->form_validation->_error_array);
             exit();
        }        
    }    
    
    public function emma_add_user_form_submit()
    {
        $field_data=array();
        $form_values['values']= $_POST;
        if(!isset($_GET['id']))
        {
            $response=$this->EE->emma_model->get_member_detail_by_email($form_values['values']['emma_email']);
            if(isset($response->member_id))
            {
                $this->EE->session->set_flashdata('message_failure', lang('The email already exists in your audience. You can update the information below and save your changes. '));
                $this->EE->functions->redirect($this->base.AMP.'method=emma_add_edit_user_form&id='.$response->member_id."&group_id=".$_GET['group_id']);        
            }
        }        
        $form_values_data=array();
        $fields=$this->EE->emma_model->getFields();
        foreach($fields as $field)
        {  
            if($field->widget_type=='check_multiple'||$field->widget_type=="select multiple")
            {
                if(isset($form_values['values'][$field->shortcut_name])&& $form_values['values'][$field->shortcut_name] )
                {
                    foreach($form_values['values'][$field->shortcut_name] as $key=>$val)
                    {
                        if($val)
                            $form_values_data['values'][$field->shortcut_name][]=$val;
                    }
                }
                else
                {   
                    $form_values_data['values'][$field->shortcut_name]=array();
                }
                if(isset($form_values_data['values'][$field->shortcut_name]) && $form_values_data['values'][$field->shortcut_name])
                {
                    $field_data[$field->shortcut_name]=$form_values_data['values'][$field->shortcut_name];
                }
            }
            else if($field->widget_type=="date")
            {
                if(isset($form_values['values'][$field->shortcut_name]) && $form_values['values'][$field->shortcut_name]&& $form_values['values'][$field->shortcut_name]['year'] && $form_values['values'][$field->shortcut_name]['day'] && $form_values['values'][$field->shortcut_name]['year'])
                {
                    $field_data[$field->shortcut_name]='@D:'.$form_values['values'][$field->shortcut_name]['year'].'-'.$form_values['values'][$field->shortcut_name]['month'].'-'.$form_values['values'][$field->shortcut_name]['day'].'T'.date('h:i:s');
                }                
            }
            else
            {
                $field_data[$field->shortcut_name]=isset($form_values['values'][$field->shortcut_name])?$form_values['values'][$field->shortcut_name]:'';
            }    
        }    
        $groups=array();
        if(isset($form_values['values']['group_list']))
        {        
            foreach($form_values['values']['group_list'] as $val)
            {
                if($val)
                    $groups[]=intval($val);
            }        
        }
        $success_message="";
        if(isset($_GET['id']))
        {
            $this->EE->emma_model->removeMemberFromAllGroups($_GET['id']);
            $response=$this->EE->emma_model->updateMember($form_values['values']['member_id'],$form_values['values']['emma_email'],$form_values['values']['member_status'],$field_data);
            $redirect_url=$this->base.AMP.'method=emma_add_edit_user_form&id='.$_GET['id'].'&group_id='.$_GET['group_id'] ;
            if($response==1 && !(isset($response->error)))
            {
                $add_to_group_response=$this->EE->emma_model->addMemberToGroups($form_values['values']['member_id'],$groups);
                if(!(isset($add_to_group_response->error)))
                {
                    $success_message=lang('User Successfully Updated');
                }
            }
        }
        else
        {   
            $redirect_url=$this->base.AMP.'method=emma_add_edit_user_form&group_id='.$_GET['group_id'] ;
            $response=$this->EE->emma_model->createEmmaUser($form_values['values']['emma_email'],$field_data, $groups);
            if(isset($response->member_id))
            {
                $success_message=lang('New User Created');
            }
        }
        if($success_message)
        {
            $this->EE->session->set_flashdata('message_success',$success_message);
            $this->EE->functions->redirect($redirect_url);
        }
        else
        {
            $this->EE->session->set_flashdata('message_failure', lang('Ooops...Something went wrong'));
            $this->EE->functions->redirect($redirect_url);
        }
    }
    public function emma_user_delete_submit()
    {
            $member_ids = array(intval($this->EE->input->get('id'))); 
            if($member_ids)
            {
                $response=$this->EE->emma_model->deleteEmmaUsers($member_ids);
                if(isset($response->member_ids)||$response==1)
                {
                    $this->EE->session->set_flashdata('message_success', lang('Delete Successfull'));
                }
            }
            else
            {
                $this->EE->session->set_flashdata('message_failure', lang('Please select a member'));
            }    
        $this->EE->functions->redirect($this->base.AMP.'method=emma_group_details&id='.$this->EE->input->get('group_id'));        
    }         
    function emma_edit_user_status_form_submit()
    {
        $member_ids = array(intval($this->EE->input->get('id'))); 
        if($this->EE->input->get('current_status')!=$this->EE->input->post('emma_user_status'))
        {
            $response=$this->EE->emma_model->updateMembersStatus($member_ids,$this->EE->input->post('emma_user_status'));
            if(!isset($response->error) && $response==1)
            {
                $this->EE->session->set_flashdata('message_success', lang('Status Changed Successfully !!!'));
            }    
            else
            {
                $this->EE->session->set_flashdata('message_failure', lang('Sorry, Users who have opted out of your list are uneditable. !!'));
            }    
        }
        else
        {
            $this->EE->session->set_flashdata('message_success', lang('Status Changed Successfully !!!'));
        }
        $this->EE->functions->redirect($this->base.AMP.'method=emma_edit_user_status_form&id='.$this->EE->input->get('id').'&group_id='.$_GET['group_id']); 
    }    
/*
Code for Emma Stats
*/    
    public function emma_stats()
    {
        $this->EE->load->library('table');
		$this->EE->cp->set_variable('cp_page_title', lang('Mailings'));

		$this->EE->cp->set_variable('cp_breadcrumbs', array(
			$this->base => lang('Emma')));            
        $mailings=$this->EE->emma_model->getMailingLists(); 
        $i=1;        
        if($mailings)
        {
          foreach($mailings as $mailing)
          {
            if($mailing->send_started)
            {
                if($mailing->send_finished)
                {
                    $time_date=str_replace('@D:','',$mailing->send_started);
                    //list($date,$time)=explode('T',$time_date);
                    $time_date=str_replace('T',' ', $time_date);
                    $send_details= date('l jS \of F Y h:i:s A', strtotime($time_date));
                    //echo $date.'<br>';
                    //echo $time.'<br>';
                }
                else
                {
                    $send_details=lang("In Progress");
                }
            }
            switch($mailing->mailing_status)
            {
                case "p":$mailing_status=lang("Pending");break;
                case "a":$mailing_status=lang("Paused"); break;
                case "s":$mailing_status=lang("In Progress"); break;
                case "x":$mailing_status=lang("Canceled"); break;
                case "c":$mailing_status=lang("Complete") ;break;
                case "f":$mailing_status=lang("Failed"); break;
                default:$mailing_status="--"; break;
            }
            
            switch($mailing->mailing_type)
            {
                case "m":$mailing_type=lang("Regular"); break;
                case "t":$mailing_type=lang("Test"); break;
                case "r":$mailing_type=lang("Trigger"); break;
                default:$mailing_type="--"; break;
            }    
            $rows[] = array(
              $i,  
              $mailing->name,
              $mailing->subject,
              $mailing_status,
              $mailing_type,
              $mailing->recipient_count,
              $send_details,
              anchor($this->base.AMP.'method=mailing_report&id='.$mailing->mailing_id, 'View Details', 'title="Details"')
            );  
            $i++; 
         }  
        }       
		$vars = array('rows'=>$rows,
                      'header'=>array(lang('No:'), lang('name'), lang('Mailing Subject'),lang('Status'),lang('Type'),lang('Recipients'),lang('Send At'),lang('Actions'))  
        );
        //var_dump($vars);
		return $this->EE->load->view('emma_stats', $vars, TRUE);        
    }
    
    public function mailing_report()
    {
        $this->EE->load->library('table');
       // $this->EE->load->model('emma_model');
        $mailing_id=$_GET['id'];
        $response=$this->EE->emma_model->getMailingDetails($_GET['id']);     
        $rows[]= array(
          "<span style='float: left;width: 100px;'>".lang('Total Opens')."</span><span style='margin-right:300px'>:&nbsp;&nbsp;&nbsp;".(($response->opened)?anchor($this->base.AMP.'method=type_details&id='.$mailing_id."&type=opens", $response->opened, 'title="Details"'):'0')."<span>",
        );    
        $rows[]= array(
          "<span style='float: left;width: 100px;'>".lang('Total Clicks')."</span><span style='margin-right:300px'>:&nbsp;&nbsp;&nbsp;".(($response->clicked)?anchor($this->base.AMP.'method=type_details&id='.$mailing_id."&type=clicks", $response->clicked, 'title="Details"'):'0')."<span>",
        ); 
        $rows[]= array(
          "<span style='float: left;width: 100px;'>".lang('Total Shares')."</span><span style='margin-right:300px'>:&nbsp;&nbsp;&nbsp;".(($response->shared)?anchor($this->base.AMP.'method=type_details&id='.$mailing_id."&type=shares", $response->shared, 'title="Details"'):'0')."<span>",
        ); 
        $rows[]= array(
          "<span style='float: left;width: 100px;'>".lang('Total Opt-outs')."</span><span style='margin-right:300px'>:&nbsp;&nbsp;&nbsp;".(($response->opted_out)?anchor($this->base.AMP.'method=type_details&id='.$mailing_id."&type=optouts", $response->opted_out, 'title="Details"'):'0')."<span>",
        );  
        $rows[]= array(
          "<span style='float: left;width: 100px;'>".lang('Total Sign-Ups')."</span><span style='margin-right:300px'>:&nbsp;&nbsp;&nbsp;".(($response->signed_up)?anchor($this->base.AMP.'method=type_details&id='.$mailing_id."&type=signups", $response->signed_up, 'title="Details"'):'0')."<span>",
        );         
        $rows2[]= array(
          "<span style='float: left;width: 140px;'>".lang('Total Emails Sent')."</span>:&nbsp;&nbsp;&nbsp;".$response->sent,
        );    
        $rows2[]= array(
          "<span style='float: left;width: 140px;'>".lang('Total Emails Bounced')."</span>:&nbsp;&nbsp;&nbsp;".$response->bounced,
        ); 
        $rows2[]= array(
          "<span style='float: left;width: 140px;'>".lang('Total Emails received')."</span>:&nbsp;&nbsp;&nbsp;".$response->recipient_count,
        ); 
        $rows2[]= array(
          "<span style='float: left;width: 140px;'>".lang('Total Emails forwarded')."</span>:&nbsp;&nbsp;&nbsp;".$response->forwarded,
        );    
            
        $vars=array(
            'rows1' => $rows,
            'rows2' => $rows2
        );
        $this->EE->cp->set_variable('cp_page_title', $response->name." ".lang('Report'));
       // $this->EE->session->set_userdata( array('emma_stats_group_name',  $response->name." ".lang('Report')) );
		$this->EE->cp->set_variable('cp_breadcrumbs', array(
			$this->base => lang('Emma'),
            $this->base.AMP.'method=emma_stats'=> lang('Mailings'),
            ));               
        return $this->EE->load->view('mailing_report', $vars, TRUE);        
    }    
    
    public function type_details()
    {
        $this->EE->load->library('table');
        $mailing_id=$_GET['id'];
        $type=$_GET['type'];
        $response=$this->EE->emma_model->getTypeDetails($mailing_id,$type);   
        $extra_info=array();
        $rows=array();    
        switch($type)
        {   
            case "signups":
            case "optouts":
            case "opens":
                        $title_page=$title=ucfirst($type). " Report";
                        $header =array(lang('No:'),lang('Name'), lang('Email'), lang('When'));
                        $i=1;
                        foreach($response as $info)
                        {
                            $timestamp=str_replace('@D:','',$info->timestamp);
                            $timestamp=str_replace('T',' ', $timestamp);
                            $timestamp=date('l jS \of F Y h:i:s A', strtotime($timestamp));   
                            $user_details=$this->EE->emma_model->get_member_detail($info->member_id);
                            $rows[] = array(
                              $i,  
                              ((isset($user_details->fields->first_name))?$user_details->fields->first_name:'')." ".((isset($user_details->fields->last_name))?$user_details->fields->last_name:''),
                              $info->email,
                              $timestamp,
                            );  
                            $i++;                            
                        }
                        break;   
            case "clicks":
                        $title_page=lang("Clicks Report");
                        $title=lang("Links Details");
                        $header=array(lang('No:'),lang('Name'), lang('Target'), lang('Unique Clicks'),lang('Total Clicks'));
                        $links=$this->EE->emma_model->get_links($mailing_id);
                        $links_row=array();
                        foreach($links as $link)
                        {
                            $i=1;
                            $links_row[$link->link_id] = array(
                              $i,  
                              'link_name'=>$link->link_name ,
                              'link_target'=>$link->link_target,
                              $link->unique_clicks,
                              $link->total_clicks,
                            );  
                            $i++;                            
                        }
                        $extra_info['title']="Clicks Report";   
                        $extra_info['header'] = array(lang('No:'),lang('Name'), lang('Email'), lang('When'),lang('Url Name'),lang('Url Target'));                        
                        $i=1;
                        foreach($response as $info)
                        {
                            $timestamp=str_replace('@D:','',$info->timestamp);
                            $timestamp=str_replace('T',' ', $timestamp);
                            $timestamp= date('l jS \of F Y h:i:s A', strtotime($timestamp));   
                            $user_details=$this->EE->emma_model->get_member_detail($info->member_id);
                            $rows[] = array(
                              $i,  
                              ((isset($user_details->fields->first_name))?$user_details->fields->first_name:'')." ".((isset($user_details->fields->last_name))?$user_details->fields->last_name:''),
                              $info->email,
                              $timestamp,
                              $links_row[$info->link_id]['link_name'],
                              $links_row[$info->link_id]['link_target'],
                            );  
                            $i++;                            
                        }
                        $extra_info['rows']=$rows;
                        $rows=array();
                        $rows=$links_row;
                        break;

            case "shares":
                        $title_page=$title=lang("Shares Report");
                        $header=array(lang('No:'),lang('Name'), lang('Email'), lang('When'),lang('Network'),lang('Visits from Network'));
                        $i=1;
                        foreach($response as $info)
                        {
                            $timestamp=str_replace('@D:','',$info->timestamp);
                            $timestamp=str_replace('T',' ', $timestamp);
                            $timestamp= date('l jS \of F Y h:i:s A', strtotime($timestamp));   
                            $user_details=$this->EE->emma_model->get_member_detail($info->member_id);
                            $rows[] = array(
                              $i,  
                              ((isset($user_details->fields->first_name))?$user_details->fields->first_name:'')." ".((isset($user_details->fields->last_name))?$user_details->fields->last_name:''),
                              $info->email,
                              $timestamp,
                              ucfirst($info->network),
                              $info->share_clicks,
                            );  
                            $i++;                            
                        }
                        break;                       
                         
        }      
        $vars=array(
            'rows' => $rows,
            'header' => $header,
            'title' => $title,
            'extra_info' => $extra_info,
        );
		$this->EE->cp->set_variable('cp_page_title', $title_page);
		$this->EE->cp->set_variable('cp_breadcrumbs', array(
			$this->base => lang('Emma'),
            $this->base.AMP.'method=emma_stats'=> lang('Mailings'),
            $this->base.AMP.'method=mailing_report&id='.$mailing_id=> lang('Mailing Report'),
            ));           
        return $this->EE->load->view('type_details', $vars, TRUE);        
    }
    

    
}

/* End of file mcp.emma.php */ 
/* Location: ./system/expressionengine/third_party/emma/mcp.emma.php */
/* Generated by DevKit for EE - develop addons faster! */