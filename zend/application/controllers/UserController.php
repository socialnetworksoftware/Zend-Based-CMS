<?php
class UserController extends Zend_Controller_Action
{
  public function init()
	 {
   $this->view->headTitle()->set('User Page');
   $ajaxContext = $this->_helper->getHelper('AjaxContext');
   $ajaxContext->addActionContext('del', 'html')
	                ->initContext();
		}
  public function indexAction()
  { 
			$request = $this->getRequest();  
			$auth		= Zend_Auth::getInstance(); 
			if(!$auth->hasIdentity()){
			$this->_redirect('/user/loginform');
			}
			else
			{
					$this->_redirect('/user/userpage');
			}
  }
    
  public function userpageAction()
		{

			$auth		= Zend_Auth::getInstance(); 

			if(!$auth->hasIdentity()){
					$this->_redirect('/user/loginform');
			}

				$user_session = new Zend_Session_Namespace('UserSession');
				if(!$user_session->UserID)
					$this->_redirect('/user/loginform');
				$user_id=$user_session->UserID;
				$request = $this->getRequest(); 
				if($user_session->UserType=='admin')
			 {
					$registry 	= Zend_Registry::getInstance();
  			$DB = $registry['DB'];
     $sql = $DB->select()
													->from(array('u' => USER_TABLE))
													->where($DB->quoteInto('id = ? ',$user_id));
	 			$user = $DB->fetchRow($sql);

				}
				else
				$user		= $auth->getIdentity();

				$real_name	= $user->first_name.' '. $user->last_name;
				$username	= $user->user_email_address;
				$logoutUrl  = $request->getBaseURL().'/user/logout';

				$this->view->assign('real_name', $real_name);
				$this->view->assign('username', $username);
				$this->view->assign('urllogout',$logoutUrl);
				$user_link='<a href="logout">Logout</a> | <a href="edit">Edit Profile</a> | <a href="../website">Website</a>';
				$this->view->assign('user_link',$user_link);

				$this->view->assign('edit',$logoutUrl);
		}
  
  public function loginformAction()
  {
			 $request = $this->getRequest();  
	   $this->view->assign('action', $request->getBaseURL()."/user/auth");  
    $this->view->assign('title', 'Login Form');
    $this->view->assign('username', 'User Name');	
    $this->view->assign('password', 'Password');		    
  }
  
  public function authAction()
		{
			$request 	= $this->getRequest();

			if ($this->getRequest()->isPost())
			{
				$username 	  = tep_db_prepare_input($request->getPost('username'));
				$password 	  = tep_db_prepare_input($request->getPost('password'));
				if($username=='' || $password =='')
				{
				$this->_redirect('/user/loginform');
				}					
			}
			else
			{
			 $this->_redirect('/user/loginform');
			}
			$registry 	= Zend_Registry::getInstance();
			$auth		= Zend_Auth::getInstance(); 
			$DB = $registry['DB'];

			$authAdapter = new Zend_Auth_Adapter_DbTable($DB);
			$authAdapter->setTableName(USER_TABLE)
															->setIdentityColumn('user_email_address')
															->setCredentialColumn('password')
															->setCredentialTreatment('? and status ="active"');        

			// Set the input credential values
			$uname = $request->getParam('username');
			$paswd = $request->getParam('password');
			$authAdapter->setIdentity($uname);
			$authAdapter->setCredential(md5($paswd));

			// Perform the authentication query, saving the result
			$result = $auth->authenticate($authAdapter);

			if($result->isValid()){
			//print_r($result);	
			$data = $authAdapter->getResultRowObject(null,'password');
			$auth->getStorage()->write($data);
			$user_session = new Zend_Session_Namespace('UserSession');
			$user_session->UserID = $data->id;
			$this->_redirect('/user/');
			}
			else
			{
		 	$this->_redirect('/user/loginform');
			}

		}
  
  public function logoutAction()
  {
  
    $auth		= Zend_Auth::getInstance(); 
	   if(!$auth->hasIdentity())
				{
	    $this->_redirect('/user/loginform');
				}
				$auth->clearIdentity();
				Zend_Session::destroy(); 
   	$this->_redirect('/user');
  }
  
  public function nameAction()
  {
    $auth		= Zend_Auth::getInstance(); 
	  	if(!$auth->hasIdentity()){
	   $this->_redirect('/user/loginform');
	   }

  
    $request = $this->getRequest();
    $this->view->assign('name', $request->getParam('username'));
    $this->view->assign('gender', $request->getParam('gender'));	  
		
    $this->view->assign('title', 'User Name');
  }  
  
  public function registerAction()
  {				
			$request = $this->getRequest();
   $error_message=$this->_getParam('error_message');
   if($error_message!='')
			{
  		$this->view->assign('error_message',($error_message));
    $first_name 	           = tep_db_prepare_input($request->getPost('first_name'));
    $last_name 	            = tep_db_prepare_input($request->getPost('last_name'));
    $user_email_address 	   = tep_db_prepare_input($request->getPost('user_email_address'));
		 	$this->view->assign('first_name',$first_name);
		 	$this->view->assign('last_name',$last_name);
		 	$this->view->assign('user_email_address',$user_email_address);
 	 }
			
			$this->view->assign('action',"process");
			$this->view->assign('title','Member Registration');
			$this->view->assign('label_fname','First Name');
			$this->view->assign('label_lname','Last Name');	
			$this->view->assign('label_uname','User E-mail Address');	
			$this->view->assign('label_pass','Password');
			$this->view->assign('label_submit','Register');		
			$this->view->assign('description','Please enter this form completely:');		
  }
  
  public function editAction()
		{
				$auth		= Zend_Auth::getInstance(); 
				if(!$auth->hasIdentity()){
				$this->_redirect('/user/loginform');
			}
				$user_session = new Zend_Session_Namespace('UserSession');
				$request = $this->getRequest();
				//$userInfo = $auth->getStorage()->read();
				if($user_session->UserType=='admin' )
				{
					$cid 	 = $request->getParam("id",null);
					if($cid)
						$user_session->UserID=$cid;
				}
 			$id 	 = $user_session->UserID;
			///////////// 
	//		if()
			if($id <= 0)
			{
				$this->_redirect('/user/list');
			}
			$validator = new Zend_Validate_Digits();
			if (!$validator->isValid($id))
			{
				$this->_redirect('/user/list');
			}
			if ($request->isPost() &&  $request->getParam("error_message") !='')
			{
				$first_name 	        = tep_db_prepare_input($request->getPost('first_name'));
				$last_name 	         = tep_db_prepare_input($request->getPost('last_name'));
				$user_email_address  = tep_db_prepare_input($request->getPost('user_email_address'));
				$error_message = $request->getParam("error_message");
				$post_data=array('id'=>$id,
																					'first_name'=>$first_name,
																					'last_name'=>$last_name,
																					'user_email_address'=>$user_email_address,
																									);
				$this->view->assign('data',$post_data);
				$this->view->assign('error_message',$error_message);

			}
			else
			{
				///////////////
				$registry = Zend_Registry::getInstance();  
				$DB = $registry['DB'];
			
				$sql = $DB->select()
													->from(array('u' => USER_TABLE))
													->where($DB->quoteInto('id = ? ',$id));
				//echo $sql;die();
			// $sql = "SELECT * FROM `user` WHERE id='".addslashes($id)."'";
				$DB->setFetchMode(Zend_Db::FETCH_BOTH);
				$result = $DB->fetchRow($sql);
				//print_r($result);die();
				$this->view->assign('data',$result);
			}
			$this->view->assign('action', $request->getBaseURL()."/user/processedit");
			$this->view->assign('title','Member Editing');
			$this->view->assign('label_fname','First Name');
			$this->view->assign('label_lname','Last Name');	
				$this->view->assign('label_uname','User E-mail Address');	
			$this->view->assign('label_pass','Password');
			$this->view->assign('label_submit','Edit');		
			$this->view->assign('description','Please update this form completely:');		
		}  
  
  public function processAction()
  {
    $request 	= $this->getRequest();
			 $registry = Zend_Registry::getInstance();  
    $DB = $registry['DB'];

 		//print_r($this->_helper);
    if ($this->getRequest()->isPost())
	   {
			  $first_name 	       = tep_db_prepare_input($request->getPost('first_name'));
			  $last_name 	        = tep_db_prepare_input($request->getPost('last_name'));
     $user_email_address = tep_db_prepare_input($request->getPost('user_email_address'));
     $password 	         = tep_db_prepare_input($request->getPost('password'));
					$error_message ='';
     if($first_name =='')
					{
				 	$error_message .='Please enter first name.'."\n";
    	}
					if($last_name =='')
					{
				 	$error_message .='Please enter last name.'."\n";
    	}
					$element_email = new Zend_Form_Element_Text('user_email_address');
     $element_email->addValidators(array(
            array('EmailAddress'),
											))
								->setRequired(true)
								->addFilter('StringTrim')
        ;
			 	if (!$element_email->isValid($user_email_address))
					{
		   	//$codes = $element_email->getErrors();	print_r($codes);
      	$message = $element_email->getMessages();
							foreach($message as $key => $value)
				 	 $error_message .=$value."\n";
 				 //	$error_message .='Please enter user name.'."\n";
					}
					else
					{
						//$sql = "SELECT * FROM `user` where user_email_address ='".addslashes($user_email_address)."'";
						$sql = $DB->select()
            ->from(array('u' => USER_TABLE))
							    ->where($DB->quoteInto('user_email_address = ? ',$user_email_address));

	     //$result = $DB->fetchAssoc($sql);
     	$DB->setFetchMode(Zend_Db::FETCH_BOTH);
						$result = $DB->fetchAll($sql);
				  if(count($result)>0)
						{
							$error_message .='This email address allready exist.'."\n";
    		}
					}
					if($password =='')
					{
				 	$error_message .='Please enter password.'."\n";
    	}
					elseif(preg_match('/\s/',$password))
					{
				 	$error_message .='Spaces are not allowed in password.'."\n";
    	}
					elseif(strlen($password)<5)
					{
				 	$error_message .='password must be atleast 5 characters.'."\n";
    	}					
	   }
				else
			 {
				  $this->_redirect('/user/loginform');
   	}
				$error_message=tep_db_prepare_input($error_message);
    if($error_message!='')
			 {
			 	$this->_forward('register',null, null, array('error_message' => $error_message));
				}
				else
			 {
					// $request = $this->getRequest();
	    $data = array('first_name' => $request->getParam('first_name'),
	                  'last_name' => $request->getParam('last_name'),
				               'user_email_address' => $request->getParam('user_email_address'),
             				  'password' => md5($request->getParam('password'))
	                 );
     $DB->insert(USER_TABLE, $data);
	 			$this->view->assign('title','Registration Process');
				 $this->view->assign('description','Registration succes');  		
			 }	
  }
  
  public function processeditAction()
  {
   $auth		= Zend_Auth::getInstance(); 
   if(!$auth->hasIdentity())
			{
	   $this->_redirect('/user/loginform');
			}
			$registry = Zend_Registry::getInstance();  
	  $DB = $registry['DB'];
			$request = $this->getRequest();
			$first_name 	       = tep_db_prepare_input($request->getPost('first_name'));
			$last_name 	        = tep_db_prepare_input($request->getPost('last_name'));
   $user_email_address = tep_db_prepare_input($request->getPost('user_email_address'));
	  $user_session = new Zend_Session_Namespace('UserSession');
  	if($user_session->UserType=='admin' )
   	 $id 	 = tep_db_prepare_input($request->getPost('id'));
			else
	 		 $id 	 = $user_session->UserID;
   if($id <= 0)
		 {
		  $this->_redirect('/user/list');
   }
			$validator = new Zend_Validate_Digits();
	  if (!$validator->isValid($id))
	 	{
		  $this->_redirect('/user/list');
   }
 		$error_message ='';
			if($first_name =='')
			{
				$error_message .='Please enter first name.'."\n";
			}					
			if($last_name =='')
			{
				$error_message .='Please enter last name.'."\n";
			}
				$element_email = new Zend_Form_Element_Text('user_email_address');
    $element_email->addValidators(array(
            array('EmailAddress'),
											))
								->setRequired(true)
								->addFilter('StringTrim');
			if (!$element_email->isValid($user_email_address))
			{
		  $message = $element_email->getMessages();
			 foreach($message as $key => $value)
		  $error_message .=$value."\n";
 		}
			else
			{
				//$sql = "SELECT * FROM `user` where user_email_address ='".addslashes($user_email_address)."' and id!='".addslashes($id)."'";
    $sql = $DB->select()
            ->from(array('u' => USER_TABLE))
	 						    ->where($DB->quoteInto('user_email_address = ? ',$user_email_address))
	 						    ->where($DB->quoteInto('id != ? ',$id));
    //$result = $DB->fetchAssoc($sql);
				$DB->setFetchMode(Zend_Db::FETCH_BOTH);
				$result = $DB->fetchAll($sql);
				if(count($result)>0)
				{
					$error_message .='This user email address allready exist.'."\n";
				}
			}
			$error_message=tep_db_prepare_input($error_message);
   if($error_message!='')
			{
				$this->_forward('edit',null, null, array('error_message' => $error_message));
   }
			else
			{
   	$data = array('first_name' => $request->getParam('first_name'),
	              'last_name' => $request->getParam('last_name'),
				           'user_email_address' => $request->getParam('user_email_address'),
	             );
    $DB->update(USER_TABLE, $data,'id = '.$id);	
   	if($user_session->UserType!='admin' )
		  $this->_redirect('/user/userpage');

    $this->view->assign('title','Editing Process');
   	$this->view->assign('description','Editing succes');  	
			}
		
  }
		public function delAction()
		{

			$auth		= Zend_Auth::getInstance(); 

			if(!$auth->hasIdentity())
			{
				$this->_redirect('/admin/loginform');
			}
			$user_session = new Zend_Session_Namespace('UserSession');
			if($user_session->UserType!='admin' )
			$this->_redirect('/admin/');


			$registry = Zend_Registry::getInstance();  
			$DB = $registry['DB'];

			$request = $this->getRequest();

			$DB->delete(USER_TABLE, 'id = '.$request->getParam('id'));	
			if (!$this->getRequest()->isXmlHttpRequest()) 
			$this->_redirect('/admin/clientlist/');	  
			$this->_helper->viewRenderer->setNoRender(); 
			$data = array('valid'=>true);
			$json = Zend_Json::encode($data);
			echo $json;
		}
}  
?>