<?php
class AdminController extends Zend_Controller_Action
{
  public function init()
	 {
    $this->view->headTitle()->set('Admin Page');
 		$ajaxContext = $this->_helper->getHelper('AjaxContext');
   $ajaxContext->addActionContext('clientlist', 'html');
   $ajaxContext->addActionContext('list', 'html')
	                ->initContext();
		}
  public function indexAction()
  { 
    $request = $this->getRequest();  
    $auth		= Zend_Auth::getInstance(); 
	   if(!$auth->hasIdentity()){
	   $this->_redirect('/admin/loginform');
	   }else{
      $this->_redirect('/admin/userpage');
	   }
  }
    
  public function userpageAction(){

  $auth		= Zend_Auth::getInstance(); 
	
 	if(!$auth->hasIdentity()){
	   $this->_redirect('/admin/loginform');
	 }

  
   $request = $this->getRequest(); 
	  $user		= $auth->getIdentity();
	  $real_name	= $user->first_name.' '. $user->last_name;
	  $username	= $user->user_email_address;
	  $logoutUrl  = $request->getBaseURL().'/user/logout';

 	 $this->view->assign('real_name', $real_name);
	  $this->view->assign('username', $username);
	  $this->view->assign('urllogout',$logoutUrl);
   $user_link='<a href="logout">Logout</a> |  <a href="list">Admin Member List</a> |  <a href="clientlist">Client Member List</a>';
			//$this->view->assign('urllogout',$logoutUrl);
			$this->view->assign('user_link',$user_link);

	  $this->view->assign('edit',$logoutUrl);
  }
  
  public function loginformAction()
  {
			 $request = $this->getRequest();  
	   $this->view->assign('action', $request->getBaseURL()."/admin/auth");  
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
					  $this->_redirect('/admin/loginform');
    	}					
	   }
				else
			 {
				  $this->_redirect('/admin/loginform');
   	}
    $registry 	= Zend_Registry::getInstance();
    $auth		= Zend_Auth::getInstance(); 
	   $DB = $registry['DB'];
		
	   $authAdapter = new Zend_Auth_Adapter_DbTable($DB);
    $authAdapter->setTableName(ADMIN_TABLE)
                ->setIdentityColumn('user_email_address')
                ->setCredentialColumn('password')
            				->setCredentialTreatment('? and status ="active"');    
	// Set the input credential values
	   $uname = $request->getParam('username');
	   $paswd = $request->getParam('password');
    $authAdapter->setIdentity($uname);
    $authAdapter->setCredential(md5($paswd));
			//	print_r($authAdapter);die();
    // Perform the authentication query, saving the result
     $result = $auth->authenticate($authAdapter);
    if($result->isValid()){
      //print_r($result);	
	  $data = $authAdapter->getResultRowObject(null,'password');
	  $auth->getStorage()->write($data);
   $user_session = new Zend_Session_Namespace('UserSession');
   $user_session->UserType = 'admin';
   $this->_redirect('/admin/');
	}
	else
	{
	  $this->_redirect('/admin/loginform');
	}
    	
  }
  
  public function logoutAction()
  {
  
    $auth		= Zend_Auth::getInstance(); 
	   if(!$auth->hasIdentity())
				{
	    $this->_redirect('/admin/loginform');
				}
				$auth->clearIdentity();
				Zend_Session::destroy(); 
   	$this->_redirect('/admin');
  }
  
  public function nameAction()
  {
    $auth		= Zend_Auth::getInstance(); 
	  	if(!$auth->hasIdentity()){
	   $this->_redirect('/admin/loginform');
	   }

  
    $request = $this->getRequest();
    $this->view->assign('name', $request->getParam('username'));
    $this->view->assign('gender', $request->getParam('gender'));	  
		
    $this->view->assign('title', 'User Name');
  }  
  
  public function registerAction()
  {
						/*
			$auth		= Zend_Auth::getInstance(); 
			
			if(!$auth->hasIdentity()){
					$this->_redirect('/user/loginform');
			}
						*/
				
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
			$this->view->assign('title','Admin Registration');
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
			if(!$auth->hasIdentity())
			{
	   $this->_redirect('/admin/loginform');
  	}
   $request = $this->getRequest();
   $id 	 = $request->getParam("id");
 	 if($id <= 0)
	 	{
		  $this->_redirect('/admin/list');
   }
		 $validator = new Zend_Validate_Digits();
			if (!$validator->isValid($id))
			{
				$this->_redirect('/admin/list');
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
													->from(array('u' => ADMIN_TABLE))
													->where($DB->quoteInto('id = ? ',$id));
				//echo $sql;die();
			// $sql = "SELECT * FROM `user` WHERE id='".addslashes($id)."'";
				$DB->setFetchMode(Zend_Db::FETCH_BOTH);
				$result = $DB->fetchRow($sql);
				//print_r($result);die();
				$this->view->assign('data',$result);
			}
			$this->view->assign('action', $request->getBaseURL()."/admin/processedit");
			$this->view->assign('title','Admin Editing');
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
            ->from(array('u' => ADMIN_TABLE))
							    ->where($DB->quoteInto('user_email_address = ? ',$user_email_address));

	     //$result = $DB->fetchAssoc($sql);
     	$DB->setFetchMode(Zend_Db::FETCH_BOTH);
						$result = $DB->fetchAll($sql);
				  if(count($result)>0)
						{
							$error_message .='This user name allready exist.'."\n";
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
				  $this->_redirect('/admin/loginform');
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
     $DB->insert(ADMIN_TABLE, $data);
	 			$this->view->assign('title','Registration Process');
				 $this->view->assign('description','Registration succes');  		
			 }	
  }
  
  public function listAction()
  {
  $auth		= Zend_Auth::getInstance(''); 
	 if(!$auth->hasIdentity())
		{
	  $this->_redirect('/admin/loginform');
	 }

		//if (!$this->getRequest()->isXmlHttpRequest()) 
		 {
  //    print_r($this->getRequest());
    }
    
 $registry = Zend_Registry::getInstance();  
	$DB = $registry['DB'];
	$DB->setFetchMode(Zend_Db::FETCH_BOTH);
//$sql = "SELECT * FROM `user` ORDER BY user_email_address ASC";
	$sql = $DB->select()
            ->from(array('u' => ADMIN_TABLE))
            ->order('user_email_address');
	$adapter = new Zend_Paginator_Adapter_DbSelect($sql);
	$paginator = new Zend_Paginator($adapter);
	$paginator->setItemCountPerPage(2);
	$paginator->setCurrentPageNumber($this->_getParam('page',1));
 $result =  $paginator->getCurrentItems();
	$request = $this->getRequest();
	$base_url= $request->getBaseURL()."/admin/list";
 $ajax_url= $request->getBaseURL()."/jscript/jquery-1.7.2.min.js";
	$projectUrl =$request->getScheme().'://'. $request->getHttpHost().$request->getBaseUrl().'/';

	//Zend_View_Helper_PaginationControl::setDefaultViewPartial('controls.phtml');
//	$this->view->paginator = $paginator;
  $paginator->setPageRange(5);
		$paginator->getPages('Sliding');
		$page =	$paginator->getPages();
	//	print_r($page);
		
//		die();
		$display_page='';
		//print_r($page);
		if(isset($page->previous))
// 	 $display_page='&lt;&lt; <a href="'.$base_url.'/page/'.$page->previous.'" > Previous Page</a>&nbsp;|&nbsp; ';
		   $display_page='&lt;&lt; <a  onclick="getContents(\''.$base_url.'/page/'.$page->previous.'\');">Previous Page</a>&nbsp;|&nbsp;';
		
	  if($page->pageCount>1)
  	foreach ($page->pagesInRange as $page1ist)
			{
		 	if($page1ist != $page->current)
				{
  	   $display_page.=' <a  onclick="getContents(\''.$base_url.'/page/'.$page1ist.'\');"   >'.$page1ist.'</a>&nbsp;|&nbsp;';
				}
				else 
				{
 	   $display_page.='<b>'.$page1ist.'</b>&nbsp;';
					if($page1ist != $page->last)
		   $display_page.='|&nbsp;';
				}
			}
		if(isset($page->next))
// 	 $display_page.='&nbsp;&nbsp;<a href="'.$base_url.'/page/'.$page->next.'" onclick="request_action(\'.'.$base_url.'/page/'.$page->next.'.\',\'use_list_data\');" >Next Page</a>&gt;&gt;';
// 	 $display_page.='&nbsp;&nbsp;<a onclick="request_action(\''.$base_url.'/page/'.$page->next.'.\',\'use_list_data\');" >Next Page</a>&gt;&gt;';
 	 $display_page.='&nbsp;&nbsp;<a onclick="getContents(\''.$base_url.'/page/'.$page->next.'.\',\'use_list_data\');" >Next Page</a>&gt;&gt;';

  $display_page.='<br>Display Page '.$page->current.' out of '.$page->last.' page';
//	echo $this->paginationControl($this->paginator,'Sliding','controls.phtml'); 
// echo paginationControl($this->paginator, ‘Sliding’, ‘pagination.phtml’); 
//	echo$this->paginationControl($this->paginator);
//
//	$result = $DB->fetchAssoc($sql);
	//$DB->setFetchMode(Zend_Db::FETCH_BOTH);
	//$result = $DB->fetchAll($sql);
//
//	print_r($result);
//print_r($_SERVER);
    $this->view->assign('base_url',dirname($base_url));
    $this->view->assign('projectUrl',$projectUrl);
    $this->view->assign('ajax_url',$ajax_url);
    $this->view->assign('title','Admin List');
	   $this->view->assign('description','Below, our members:');
    $this->view->assign('datas',$result);		  
    $this->view->assign('display_page',$display_page);		  
  }
   public function processeditAction()
  {
   $auth		= Zend_Auth::getInstance(); 
   if(!$auth->hasIdentity())
			{
	   $this->_redirect('/admin/loginform');
			}
   $user_session = new Zend_Session_Namespace('UserSession');

			$registry = Zend_Registry::getInstance();  
	  $DB = $registry['DB'];
			$request = $this->getRequest();
			$first_name 	       = tep_db_prepare_input($request->getPost('first_name'));
			$last_name 	        = tep_db_prepare_input($request->getPost('last_name'));
   $user_email_address = tep_db_prepare_input($request->getPost('user_email_address'));
 	 $id 	 = tep_db_prepare_input($request->getPost('id'));
   if($id <= 0)
		 {
		  $this->_redirect('/admin/list');
   }
			$validator = new Zend_Validate_Digits();
	  if (!$validator->isValid($id))
	 	{
		  $this->_redirect('/admin/list');
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
            ->from(array('u' => ADMIN_TABLE))
	 						    ->where($DB->quoteInto('user_email_address = ? ',$user_email_address))
	 						    ->where($DB->quoteInto('id  != ? ',$id));
    //$result = $DB->fetchAssoc($sql);
				$DB->setFetchMode(Zend_Db::FETCH_BOTH);
				$result = $DB->fetchAll($sql);
				if(count($result)>0)
				{
					$error_message .='This user name allready exist.'."\n";
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
    $DB->update(ADMIN_TABLE, $data,'id = '.$id);	
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

   $registry = Zend_Registry::getInstance();  
  	$DB = $registry['DB'];
	
	  $request = $this->getRequest();

   $DB->delete(ADMIN_TABLE, 'id = '.$request->getParam('id'));	
	
   $this->view->assign('title','Delete Data');
	  $this->view->assign('description','Deleting succes');  		  
   $this->view->assign('list',$request->getBaseURL()."/user/list");    
  }
		public function clientlistAction()
		{
  $auth		= Zend_Auth::getInstance(''); 
	 if(!$auth->hasIdentity())
		{
	  $this->_redirect('/admin/loginform');
	 }
  $user_session = new Zend_Session_Namespace('UserSession');
  if($user_session->UserType != 'admin')
  $this->_redirect('/admin/');
    
 $registry = Zend_Registry::getInstance();  
	$DB = $registry['DB'];

	$DB->setFetchMode(Zend_Db::FETCH_BOTH);
//$sql = "SELECT * FROM `user` ORDER BY user_email_address ASC";
	$sql = $DB->select()
            ->from(array('u' => USER_TABLE))
            ->order('user_email_address');
	$adapter = new Zend_Paginator_Adapter_DbSelect($sql);
	$paginator = new Zend_Paginator($adapter);
	$paginator->setItemCountPerPage(2);
	$paginator->setCurrentPageNumber($this->_getParam('page',1));
 $result =  $paginator->getCurrentItems();
	$request = $this->getRequest();
 $projectUrl =$request->getScheme().'://'. $request->getHttpHost().$request->getBaseUrl().'/';

 $base_url= $request->getBaseURL()."/admin/clientlist";
 $ajax_url= $request->getBaseURL()."/jscript/jquery-1.7.2.min.js";
	//Zend_View_Helper_PaginationControl::setDefaultViewPartial('controls.phtml');
//	$this->view->paginator = $paginator;
  $paginator->setPageRange(5);
		$paginator->getPages('Sliding');
		$page =	$paginator->getPages();
	//	print_r($page);
		
//		die();
		$display_page='';
		//print_r($page);
		if(isset($page->previous))
// 	 $display_page='&lt;&lt; <a href="'.$base_url.'/page/'.$page->previous.'" > Previous Page</a>&nbsp;|&nbsp; ';
		   $display_page='&lt;&lt; <a  onclick="getContents(\''.$projectUrl.'admin/clientlist/page/'.$page->previous.'\',\'use_list_data\');">Previous Page</a>&nbsp;|&nbsp;';
		
		foreach ($page->pagesInRange as $page1ist)
			{
		 	if($page1ist != $page->current)
				{
  	   $display_page.=' <a  onclick="getContents(\''.$projectUrl.'admin/clientlist/page/'.$page1ist.'\',\'use_list_data\');"   >'.$page1ist.'</a>&nbsp;|&nbsp;';
				}
				else 
				{
 	   $display_page.='<b>'.$page1ist.'</b>&nbsp;';
					if($page1ist != $page->last)
		   $display_page.='|&nbsp;';
				}
			}
		if(isset($page->next))
// 	 $display_page.='&nbsp;&nbsp;<a href="'.$base_url.'/page/'.$page->next.'" onclick="request_action(\'.'.$base_url.'/page/'.$page->next.'.\',\'use_list_data\');" >Next Page</a>&gt;&gt;';
// 	 $display_page.='&nbsp;&nbsp;<a onclick="request_action(\''.$base_url.'/page/'.$page->next.'.\',\'use_list_data\');" >Next Page</a>&gt;&gt;';
 	 $display_page.='&nbsp;&nbsp;<a onclick="getContents(\''.$projectUrl.'admin/clientlist/page/'.$page->next.'.\',\'use_list_data\');" >Next Page</a>&gt;&gt;';

  $display_page.='<br>Display Page '.$page->current.' out of '.$page->last.' page';
//	echo $this->paginationControl($this->paginator,'Sliding','controls.phtml'); 
// echo paginationControl($this->paginator, ‘Sliding’, ‘pagination.phtml’); 
//	echo$this->paginationControl($this->paginator);
//
//	$result = $DB->fetchAssoc($sql);
	//$DB->setFetchMode(Zend_Db::FETCH_BOTH);
	//$result = $DB->fetchAll($sql);
//
//	print_r($result);
	   $this->view->assign('projectUrl',$projectUrl);
    $this->view->assign('ajax_url',$ajax_url);
    $this->view->assign('title','Client  List');
	   $this->view->assign('description','Below, our members:');
    $this->view->assign('datas',$result);		  
    $this->view->assign('display_page',$display_page);		  
  }
  public function cactiveAction()
	 {
			$auth		= Zend_Auth::getInstance(''); 
 	 if(!$auth->hasIdentity())
	 	{
	   $this->_redirect('/admin/loginform');
	  }
   $user_session = new Zend_Session_Namespace('UserSession');
   if($user_session->UserType != 'admin')
   $this->_redirect('/admin/');
   $registry = Zend_Registry::getInstance();  
   $request = $this->getRequest();
		 $projectUrl =$request->getScheme().'://'. $request->getHttpHost().$request->getBaseUrl().'/';

   $id 	  =tep_db_prepare_input($request->getParam("id"));
			$DB = $registry['DB'];
  	$data = array('status' =>'active');
   $DB->update(USER_TABLE, $data,$DB->quoteInto('id = ? ',$id));	
   if (!$this->getRequest()->isXmlHttpRequest()) 
   $this->_redirect('/admin/clientlist');
 		die('<a onclick="getContents(\''.$projectUrl.'admin/cinactive/id/'.$id.'\',\'status_'.$id.'\')">Inactivate</a>');
		}
	  public function cinactiveAction()
	 {
			$auth		= Zend_Auth::getInstance(''); 
 	 if(!$auth->hasIdentity())
	 	{
	   $this->_redirect('/admin/loginform');
	  }
   $user_session = new Zend_Session_Namespace('UserSession');
   if($user_session->UserType != 'admin')
   $this->_redirect('/admin/');
   $registry = Zend_Registry::getInstance();  
   $request = $this->getRequest();
		 $projectUrl =$request->getScheme().'://'. $request->getHttpHost().$request->getBaseUrl().'/';
   $id 	  =tep_db_prepare_input($request->getParam("id"));
			$DB = $registry['DB'];
  	$data = array('status' =>'inactive');
   $DB->update(USER_TABLE, $data,$DB->quoteInto('id = ? ',$id));	
   if (!$this->getRequest()->isXmlHttpRequest()) 
   $this->_redirect('/admin/clientlist');
 		die('<a onclick="getContents(\''.$projectUrl.'admin/cactive/id/'.$id.'\',\'status_'.$id.'\')">Activate</a>');
		}		
}
?>
