<?php
	
	/**
		* Contact Controller
		* @author James Fairhurst <info@jamesfairhurst.co.uk>
	*/
	class LeadsController extends AppController {
		/**
			* Components$this->loadModel('Authake.tblOrder');
		*/
		var $uses = array('Authake.Group','Authake.User','Authake.tblProfile','Authake.tblOrder','OrderLeads','Authake.tblLineItem', 'Authake.Rule', 'Authake.tblUserConnect');
		var $components = array('RequestHandler','Authake.Filter','Session','Commonfunction');// var $layout = 'authake';
		var $paginate = array('limit' => 1000, 'order' => array('User.login' => 'asc'));//var $scaffold;
		/**
			* Before Filter callback
		*/
		public function beforeFilter() {
			parent::beforeFilter();
			// Change layout for Ajax requests
			if ($this->request->is('ajax')) {
				$this->layout = 'ajax';
			}
		}
		
		/**
			* Main index action
		*/
		private function getAllOrderIds(){
			$userId = $this->Authake->getUserId();
			$options['conditions'] = array('tblOrder.isActive' => 1 );
			if(!empty($user_id)){
				$options['conditions'] = array('tblOrder.isActive'=>1,'tblp.user_id' =>$user_id);
			}
			$options['conditions']['NOT'] =array('tblOrder.dfp_order_id'=>'');
			$options['joins'] = array(
			array('table' => 'tbl_profiles',
			'alias' => 'tblp',
			'type' => 'INNER',
			'conditions' => array(
			'tblOrder.advertiser_id = tblp.dfp_company_id',
			'tblOrder.owner_user_id' => array($userId)
			)));
			 $options['order'] = array('tblOrder.created_at' => 'DESC' );
			 $options['fields'] = array('tblOrder.dfp_order_id','tblOrder.dfp_order_id');
			 return $this->tblOrder->find('list', $options);
		}
		public function index() {
			// form posted
			$this->loadModel('Authake.tblCostStructure');
			$this->set('title_for_layout','All Companies');
			$userId = $this->Authake->getUserId();
			$options['conditions'] = array('tblOrder.isActive' => 1 );
			if(!empty($user_id)){
				$options['conditions'] = array('tblOrder.isActive'=>1,'tblp.user_id' =>$user_id);
			}
			$options['conditions']['NOT'] =array('tblOrder.dfp_order_id'=>'');
			$options['joins'] = array(
			array('table' => 'tbl_profiles',
			'alias' => 'tblp',
			'type' => 'INNER',
			'conditions' => array(
			'tblOrder.advertiser_id = tblp.dfp_company_id',
			'tblOrder.owner_user_id' => array($userId)
			)));
			 $options['order'] = array('tblOrder.created_at' => 'DESC' );
			 $options['fields'] = array('tblOrder.dfp_order_id','tblOrder.order_name');
			 $orderlist = $this->tblOrder->find('list', $options);
			 $option['all']="All";
			 $this->set('orderlist',$option+$orderlist);
			 $companylist = $this->tblProfile->getListConnectedProfiles();//('tblProfile.dfp_company_id')
			 $this->set('companylist',$option+$companylist);
			 // get all line item
			 
			 $tblLineItems = $this->tblLineItem->getListLineItems();
			 
			 $this->set('tblLineItems',$option+$tblLineItems);
			 $costStructure = $this->tblCostStructure->find('list',array('order'=>array('tblCostStructure.cs_name' => 'asc'),'fields'=>array('tblCostStructure.cs_id','tblCostStructure.cs_name')));
			 $this->set('costStructure', $costStructure);
			 // filter 
			 
			 if(!empty($this->request->data)){
				 
				 // get date 
				 $start_date =$end_date ='';
				 if(!empty($this->request->data['Lead']['daterange'])){
					 $daterange =explode('-',$this->request->data['Lead']['daterange']);
					$start_date = $this->Commonfunction->edit_datepickerFormat($daterange[0]);
					$end_date = $this->Commonfunction->edit_datepickerFormat($daterange[1]);
				 }
				 $options =array(); // clear options here 
				
				 // condition here 
				  
				 if(!empty($this->request->data['Lead']['od_isRejected'])){
					 $options['conditions']['Or'][] =array('OrderLeads.od_isRejected'=>0);
				 }
				 if(!empty($this->request->data['Lead']['od_return'])){
					 $options['conditions']['Or'][] =array('OrderLeads.od_isreturn'=>1);
				 }
				 if(!empty($this->request->data['Lead']['od_isTest'])){
					 
					 $options['conditions']['Or'][] =array('OrderLeads.od_isTest'=>1);
				 }
				 if(!empty($this->request->data['Lead']['line_item'])){
					 if(!in_array("all",$this->request->data['Lead']['line_item']))
					 $options['conditions'][] =array('OrderLeads.od_ad_id'=>$this->request->data['Lead']['line_item']);
				 }
				 if(!empty($this->request->data['Lead']['email'])){
					$options['conditions'][]= array('OrderLeads.od_email like'=>'%'.$this->request->data['Lead']['email'].'%');
				 }
				 if(!empty($this->request->data['Lead']['daterange'])){
					$options['conditions'][]= array( 'DATE(OrderLeads.od_create) between ? and ?'=> array($start_date, $end_date));
				 }
				 
				
				 /*$this->OrderLeads->bindModel(array('hasMany' => array('tblLineItem' => 
						array('foreignKey' => 'od_lid','conditions'=>array(),'fields'=>array('tblLineItem.camp_name', 'dependent' => false)))));*/
				 
				 $options['joins'] = array(
					array('table' => 'das_digitaladvertising.tbl_creatives',
					'alias' => 'Creative',
					'type' => 'INNER',
					'conditions' => array(
					'OrderLeads.od_cid = Creative.cr_lid',
					)),
					array('table' => 'das_digitaladvertising.tbl_orders',
					'alias' => 'tblOrder',
					'type' => 'INNER',
					'conditions' => array(
					'OrderLeads.od_lid = tblOrder.dfp_order_id',
					)),
					array('table' => 'das_digitaladvertising.tbl_line_items',
					'alias' => 'tblLineItem',
					'type' => 'INNER',
					'conditions' => array(
					'OrderLeads.od_lid = tblLineItem.li_order_id',
					))
					
					
					);
				 $options['fields']= array('tblOrder.dfp_order_id','tblOrder.order_name','tblLineItem.li_name','OrderLeads.od_country_name','OrderLeads.od_source','OrderLeads.od_email','OrderLeads.od_create','OrderLeads.fp_browser','OrderLeads.fp_os','OrderLeads.od_isDuplicate','OrderLeads.od_ipaddress','OrderLeads.od_isRejected','OrderLeads.od_isTest','Creative.cr_name','Creative.cr_header');	
				 $options['group']= array('OrderLeads.od_id');
				 $ordersRecords =array();
				 if(!empty($this->request->data['Lead']['company_id'])){
					 
					 
					 if(!empty($this->request->data['Lead']['order_id'])){
						if(in_array("all",$this->request->data['Lead']['order_id'])){
						 $orders =self::getAllOrderIds();
						}else{
						 
						  $orders =$this->request->data['Lead']['order_id'];
						}
					}else{
						$orders =self::getAllOrderIds();
					} $order_data= array(); 
					foreach($orders as $order_id){
						 // if get all then continue 
						if($order_id=="all") continue;
						 
						$tble =$this->OrderLeads->useTable =   "order_".$order_id;
						$tble =$this->OrderLeads->table =   "order_".$order_id;
						$order_ids = $OrderLeads=array(); 
						
						$OrderLeads = $this->OrderLeads->find('all',$options);
						$results = $this->OrderLeads->find('all', array(
							'contain' => array('OrderLeads')
						));
						
						if(!empty($OrderLeads)){
							 foreach($OrderLeads as $orderlead){
								  // this line get all order id for get line item name 
			
								$order_data[$orderlead['tblOrder']['dfp_order_id']]= $orderlead['tblOrder']['dfp_order_id']; 
						 
								$ordersRecords[]=$orderlead;
							}
						 }
					 } 
					 
					 $this->set('ordersRecords',$ordersRecords);
					 
					 
				 }else{
					  
					$this->Session->setFlash(__('Please select company name'), 'error');
					$this->redirect(array('controller' => 'leads','action'=>'index'));
					}
				 
		 	 }
	 	}
	 	private function lineitemName($order_ids =array()){
			 
			$orderByList=array();		
			$lineitems =$this->tblLineItem->find('all',array('fields'=>array('tblLineItem.li_name','tblLineItem.*'),'conditions'=>array('tblLineItem.li_order_id'=>$order_ids)));
			 
			if( !empty($lineitems)){
				foreach($lineitems as $lineitem){
						$orderByList[$lineitem['tblLineItem']['li_order_id']][] =$lineitem['tblLineItem']['li_name'];
				}
			}
			return $orderByList; 
		}
		public function reConciliation(){
			
			// load model of order 
			
			$userId = $this->Authake->getUserId();
			$options['conditions'] = array('tblOrder.isActive' => 1 );
			if(!empty($user_id)){
				$options['conditions'] = array('tblOrder.isActive'=>1,'tblp.user_id' =>$user_id);
			}
			$options['joins'] = array(
			array('table' => 'tbl_profiles',
			'alias' => 'tblp',
			'type' => 'INNER',
			'conditions' => array(
			'tblOrder.advertiser_id = tblp.dfp_company_id',
			'tblOrder.owner_user_id' => array($userId)
			)));
			 $options['order'] = array('tblOrder.created_at' => 'DESC' );
			$options['fields'] = array('tblOrder.dfp_order_id','tblOrder.order_name');
			$orderlist = $this->tblOrder->find('list', $options);
			$this->set('orderlist',$orderlist);
			if(!empty($this->request->data)){
				if($this->request->data['Lead']['csv_file']['error']==0){
					$_upload_csv= self::_upload_csv($this->request->data['Lead']['csv_file']);
				}
			if(!empty($_upload_csv) and !empty($this->request->data['Lead']['order_id']) and !empty($this->request->data['Lead']['line_id'])){ 
				$destination = Configure::read('Path.csvPath').$_upload_csv;
				//
			 $fh = fopen($destination, 'r') or die("can't open file");
			 $emailvalid =array();
			 $emailinvalid =array();
			 while ( ($data = fgetcsv($fh) ) !== FALSE ) {
					if (!filter_var($data[0], FILTER_VALIDATE_EMAIL) === false) {
						$emailvalid[] =$data[0];
					} else {
					  $emailinvalid[] =$data[0];
					}
				
			  }
			  // update order  od_isreturn when check email 
			  $updated_emails = $notupdated_email =array();
			  if(!empty($emailvalid)){
				  foreach($emailvalid as $email){
					  $this->OrderLeads->useTable =   "order_".$this->request->data['Lead']['order_id'] ;	//das_email
						$OrderLeads = $this->OrderLeads->find('first',array('fields'=>array('OrderLeads.od_id'),'conditions'=>array('OrderLeads.od_email like'=>$email , 'OrderLeads.od_lid'=>$this->request->data['Lead']['order_id'])));
						if(!empty($OrderLeads)){
							$this->OrderLeads->updateAll(array('od_isreturn' => 1),array('OrderLeads.od_id' => $OrderLeads['OrderLeads']['od_id']));
							$updated_emails[] = $email;
						}else{
							$notupdated_email[] = $email;
						}
						
						
				  }
			  } 
			  $message ='';
			  if(!empty($updated_emails)){
				  $message .=' Upload emails:- '.count($updated_emails);
			  }
			  if(!empty($notupdated_email)){
				  $message .='  Not Upload emails:- '.count($notupdated_email);
			  }
			  if(!empty($emailinvalid)){
				  $message .='  Invalid emails:- '.count($emailinvalid);
			  }
			  
			  
			   $this->Session->setFlash(__($message), 'success');
			   unlink($destination); //delete after read file 
			   fclose($fh);
			  
			}else{
			  $this->Session->setFlash(__('Please select order list, line item and csv file'), 'error');
			}
		  }
		}
		
		private function _upload_csv($csv = array()) {
			if ($csv['error'] > 0) {
				return null;
				} else {
				$existing_csv = array();
				if ($csv['error'] > 0) {
					return $existing_csv['Lead']['csv_file'];
					} else {
					$destination = Configure::read('Path.csvPath');
					if (!file_exists($destination)) {
						$createStructure = mkdir($destination, 0777, true);
					}
					 $ext = explode('.', $csv['name']);
					$get_ext = array_pop($ext);
					$name = basename($csv['name'],'.'.$get_ext);
					
					
					$csv_name = self::clean($name). '_' . time() . '.' . $get_ext;
					//$image_name = time() . '_' . time() . '.' . array_pop($ext);
					move_uploaded_file($csv['tmp_name'], $destination . $csv_name);
					if (!empty($existing_csv)) {
						unlink($destination . $existing_csv['Lead']['csv_file']);
					}
					//move_uploaded_file($filename, $destination);
					return $csv_name;
				}
			}
		}
		private function clean($string) {
			$string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
			return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
		}
		public function order_line_item($order_id =null){
			$this->autoRender = false;
			$options['joins'] = array(
			array('table' => 'tbl_cost_structures',
			'alias' => 'tcs',
			'type' => 'INNER',
			'conditions' => array(
			'tcs.cs_id = tblLineItem.li_cs_id'
			)),
			array('table' => 'tbl_lineitem_status',
			'alias' => 'tls',
			'type' => 'INNER',
			'conditions' => array(
			'tls.ls_id = tblLineItem.li_status_id'
			))
			);
			
			$options['fields'] = array('tblLineItem.li_id','tblLineItem.li_name');
			
			$options['conditions'] = array('tblLineItem.li_order_id' => $order_id );
			$tblLineItems = $this->tblLineItem->find("list",$options);
			$line_items[0]['id'] = "0";
			$line_items[0]['text'] = "Select (or start typing) the line item";
			
			if(!empty($tblLineItems)){
				$count = 1;
				foreach($tblLineItems as $key=>$value){
					
					$line_items[$count]['id'] = $key;
					$line_items[$count]['text'] = $value;
					$count++;
				}
			}
			return json_encode($line_items);
			
		}
		
		function samplecsv(){
			//$this->autoRender = false;
			header('Content-Type: application/csv');
			$destination = Configure::read('Path.url_csvPath').'sample.csv';
			header('Content-Disposition: attachment; filename='.$destination);
			header('Pragma: no-cache');
			readfile("/");
			die;
		}
		function getAllOrderList(){
			$this->autoRender = false;
			$user_id =array(); 
			if(!empty($this->request->data['user_id'])){
				$user_id = explode(',', $this->request->data['user_id']);
			}
			
			$userId = $this->Authake->getUserId();
			$options['conditions'] = array('tblOrder.isActive' => 1 );
			if(!in_array('all',$user_id) and !empty($user_id)){
				$options['conditions'] = array('tblOrder.isActive'=>1,'tblp.user_id' =>$user_id);
			}
			$options['conditions']['NOT'] =array('tblOrder.dfp_order_id'=>'');
			$options['joins'] = array(
			array('table' => 'tbl_profiles',
			'alias' => 'tblp',
			'type' => 'INNER',
			'conditions' => array(
			'tblOrder.advertiser_id = tblp.dfp_company_id',
			'tblOrder.owner_user_id' => array($userId)
			)));
			
			$options['fields'] = array('tblOrder.*', 'tblp.CompanyName');
			
			$options['order'] = array(
			'tblOrder.created_at' => 'DESC' );
			
			$options['fields'] = array('tblOrder.dfp_order_id','tblOrder.order_name');
			
			$orders = $this->tblOrder->find('list', $options);
			$order_list =array();
			 
			$order_list[0]['id'] = "all";
			$order_list[0]['text'] = "All";
			if(!empty($orders)){
				$count = 1;
				foreach($orders as $key=>$value){
					
					$order_list[$count]['id'] = $key;
					$order_list[$count]['text'] = $value;
					$count++;
				}
			}
			 
			return json_encode($order_list);
			
		}
		function getAllLineItemByOrder(){
			$this->autoRender = false;
			$order_id =array(); 
			if(!empty($this->request->data['order_id'])){
				$order_id = explode(',', $this->request->data['order_id']);
			}
			 
			$options =array();
			$options['joins'] = array(
			array('table' => 'tbl_cost_structures',
				'alias' => 'tcs',
				'type' => 'INNER',
				'conditions' => array(
				'tcs.cs_id = tblLineItem.li_cs_id'
			)),
			array('table' => 'tbl_lineitem_status',
				'alias' => 'tls',
				'type' => 'INNER',
				'conditions' => array(
				'tls.ls_id = tblLineItem.li_status_id'
				))
			);
			
			 if(!in_array('all',$order_id) and !empty($order_id))
				$options['conditions'] = array('tblLineItem.li_order_id' => $order_id );
			$options['fields'] = array('tblLineItem.li_dfp_id','tblLineItem.li_name');
			$tblLineItems = $this->tblLineItem->find("list",$options);
			$line_items[0]['id'] = "all";
			$line_items[0]['text'] = "All";
			
			if(!empty($tblLineItems)){
				$count = 1;
				foreach($tblLineItems as $key=>$value){
					$line_items[$count]['id'] = $key;
					$line_items[$count]['text'] = $value;
					$count++;
				}
			}
			return json_encode($line_items);
			
		}
		
		function table(){
			 
			
			$tablestructure = array(
 			'od_lid'=>" ADD `od_lid` int NOT NULL DEFAULT 0 AFTER `od_create`",
			'od_cid'=>" ADD  `od_cid` int NOT NULL DEFAULT 0 AFTER `od_create`",
			'od_ad_id'=>" ADD  `od_ad_id` int NOT NULL DEFAULT 0 AFTER `od_create`",
			'od_mailing_id'=>" ADD  `od_mailing_id` int DEFAULT 0 AFTER `od_create`",
			'od_map_id'=>" ADD  `od_map_id` char(36) NOT NULL AFTER `od_create`",
			'od_country_name'=>	" ADD `od_country_name` varchar(200) NOT NULL AFTER `od_create`",
			'od_source'=>" ADD  `od_source` varchar(200) NOT NULL  AFTER `od_create`",
			'od_email'=>" ADD  `od_email` varchar(100) NOT NULL AFTER `od_create`",
			'od_phone'=>" ADD  `od_phone` varchar(20) NOT NULL  AFTER `od_create`",
			'od_fname'=>" ADD  `od_fname` varchar(100) DEFAULT NULL  AFTER `od_create`",
			'od_lname'=>" ADD  `od_lname` varchar(100) DEFAULT NULL  AFTER `od_create`",
			'od_site'=>" ADD  `od_site` varchar(100) DEFAULT NULL  AFTER `od_create`",
			'device_type'=>" ADD  `device_type` varchar(100) DEFAULT NULL  AFTER `od_create`",
			'od_create'=>" ADD  `od_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `od_create`",
			'fp_browser'=>" ADD  `fp_browser` varchar(200) DEFAULT NULL  AFTER `od_create`",
			'fp_connection'=>" ADD  `fp_connection` varchar(200) DEFAULT NULL  AFTER `od_create`",
			'fp_display'=>" ADD  `fp_display` varchar(200) DEFAULT NULL  AFTER `od_create`",
			'fp_flash'=>" ADD  `fp_flash` varchar(100) DEFAULT NULL  AFTER `od_create`",
			'fp_language'=>" ADD  `fp_language` varchar(200) DEFAULT NULL  AFTER `od_create`",
			'fp_os'=>" ADD  `fp_os` varchar(200) DEFAULT NULL  AFTER `od_create`",
			'fp_timezone'=>" ADD  `fp_timezone` varchar(100) DEFAULT NULL  AFTER `od_create`",
			'fp_useragent'=>" ADD  `fp_useragent` varchar(500) DEFAULT NULL  AFTER `od_create`",
			'od_isDuplicate'=>" ADD  `od_isDuplicate` tinyint(1) DEFAULT 0 AFTER `od_create`",
			'od_isTest'=>" ADD  `od_isTest` tinyint(1) DEFAULT 0 AFTER `od_create`",
			'od_ipaddress'=>" ADD  `od_ipaddress` int(10) unsigned DEFAULT NULL  AFTER `od_create`",
			'od_isreturn'=>" ADD  `od_isreturn` tinyint(2) DEFAULT 0 AFTER `od_create`",
			'od_isRejected'=>" ADD  `od_isRejected` tinyint(1) DEFAULT 0 AFTER `od_create`" 
			 
			 ) ;
 
		  $orders = $this->OrderLeads->query('SHOW TABLES');
		 
		  $sql="";
		  $addField=array();
		  if(!empty($orders)){
			  foreach($orders as $order){
				  $tablename = $order['TABLE_NAMES']['Tables_in_das_order_leads'];
				  $results = $this->OrderLeads->query("DESCRIBE ".$tablename); 
				  $addField =array();
				  if(!empty($results)){
						foreach($results as $result){
							$addField[$result['COLUMNS']['Field']] = $result['COLUMNS']['Field'];
						}
					 $result=array_diff_key($tablestructure,$addField);
					 if(!empty($result))	
						$sql .= "ALTER TABLE ".$tablename."  ". implode(',',$result).";";
					 
					 
					}
					
			 }
			 if(!empty($result))$results = $this->OrderLeads->query($sql); 
		  }
		  pr($sql);
		  die;
	}
}																						
