<?php
/**
 *
 * MemberMouse(TM) (http://www.membermouse.com)
 * (c) MemberMouse, LLC. All rights reserved.
 */

 // TODO move to corepageview and protectedcontentview 
 class MM_PostHooks
 {	
 	public function pagesColumns($defaults)
 	{
 		$offset = 2; ///column offset
		$defaults = array_slice($defaults, 0, $offset, true) +
            array("core_page_type"=>__('Core Page Type')) +
            array("access_rights"=>__('Access Rights')) +
            array_slice($defaults, $offset, NULL, true);
            
		// verify core pages
		global $wpdb;
		
		$corruptCount = 0;
		
		$sql = "SELECT * FROM ".MM_TABLE_CORE_PAGES." WHERE (ref_type IS NULL or ref_type = '') AND (ref_id IS NULL or ref_id = '');";
		
		$results = $wpdb->get_results($sql);
		
		if($results)
		{
			foreach($results as $row)
			{
				if(!is_null($row->page_id) && (FALSE === get_post_status($row->page_id))) 
				{
					$corruptCount++;
				}
			}
		}
		
		if($corruptCount > 0)
		{
			$repairCorePagesUrl = MM_ModuleUtils::getUrl(MM_MODULE_GENERAL_SETTINGS, MM_MODULE_REPAIR_CORE_PAGES);
			MM_Messages::addError("<i class=\"fa fa-warning\"></i> MemberMouse has detected that {$corruptCount} of your default core pages are associated with missing WordPress pages. <a href='{$repairCorePagesUrl}'>Repair Core Pages</a>");
		}
		
	    return $defaults;
 	}
 	
 	public function checkPosts(){
 		global $current_user,$wp_query;
 		$userId = 0;
 		if(isset($current_user->ID)){
 			$userId = $current_user->ID;
 		}
 		if(is_home()){
			$protectedContent = new MM_ProtectedContentEngine();
	 		
			$posts = array();
	 		for($i=0; $i<count($wp_query->posts); $i++){
	 			$post = $wp_query->posts[$i];
	 			 
	 			if(!$protectedContent->canAccessPost($post->ID,$userId))
		 		{
		 		    if(!$protectedContent->hasDecisionTags($post->ID) && !$protectedContent->hasMoreTag($post->post_content))
		 		    {
		 		        $post->post_content = "You don't have access to view this content";
		 		    }
		 		}
		 		$posts[] = $post;
	 		}
	 		$wp_query->posts = $posts;
 		}
 	}
		
	public function doRestFilter($data, $post, $context) {
		global $current_user;
		
		$userId = 0;
		if (isset ( $current_user->ID )) {
			$userId = $current_user->ID;
		} 
		$postData = $data->get_data(); 
		if(!is_null($postData) && !empty($postData) && is_array($postData)){  
			$protectedContent = new MM_ProtectedContentEngine ();
			if (! $protectedContent->canAccessPost ( $post->ID, $userId )) {
				$postData["title"]["rendered"] = "You don't have access to view this content";
				$postData["content"]["rendered"] = "You don't have access to view this content";
				$data->set_data($postData);
			} else {
				$postData["title"]["rendered"] = MM_SmartTagUtil::processContent ( $post->post_title, new MM_Context() );
				$postData["content"]["rendered"] = MM_SmartTagUtil::processContent ( $post->post_content, new MM_Context() );
				$data->set_data($postData);
			}
		} 
		return $data;
	}
 	
 	public function postsColumns($defaults)
 	{
 		$offset = 2; ///column offset
 		
 		$addlColumns = array();
 		
 		if(isset($_GET["post_type"]) && MM_Utils::isCustomPostType($_GET["post_type"]))
 		{
 			$addlColumns["core_page_type"] = __('Core Page Type');
 		}
 		
 		$addlColumns["access_rights"] = __('Access Rights');
 		
		$defaults = array_slice($defaults, 0, $offset, true) +
			$addlColumns +
        	array_slice($defaults, $offset, NULL, true);
            
	    return $defaults;
 	}
 	
 	public function handlePostWhere($where)
 	{
 		global $wpdb;
 		if(is_admin())
 		{
	 		$mt_sql = "";
	 		$at_sql = "";
	 		$cp_sql = "";
	 		if(isset($_GET["member_types"]) && !empty($_GET["member_types"]) && preg_match("/^[0-9]+$/", $_GET["member_types"]))
	 		{
	 			$mt_sql = " AND (
	 								({$wpdb->posts}.id IN (select post_id from ".MM_TABLE_POSTS_ACCESS." where access_type='member_type' and access_id='".$_GET["member_types"]."' ))
	 								OR
	 								({$wpdb->posts}.id IN (select page_id from ".MM_TABLE_CORE_PAGES." where ref_type='member_type' and ref_id='".$_GET["member_types"]."'))
									OR
	 								({$wpdb->posts}.id IN (select page_id from ".MM_TABLE_CORE_PAGES." where ref_type='product' and ref_id IN (select default_product_id from ".MM_TABLE_MEMBERSHIP_LEVELS." where id='{$_GET["member_types"]}' )))
	 					) ";
	 		}
	 		if(isset($_GET["access_tags"]) && !empty($_GET["access_tags"]) && preg_match("/^[0-9]+$/", $_GET["access_tags"]))
	 		{
	 			$at_sql = " AND (
	 								({$wpdb->posts}.id IN (select post_id from ".MM_TABLE_POSTS_ACCESS." where access_type='access_tag' and access_id='".$_GET["access_tags"]."' ))
	 								OR
	 								({$wpdb->posts}.id IN (select page_id from ".MM_TABLE_CORE_PAGES." where ref_type='access_tag' and ref_id='".$_GET["access_tags"]."')) 
	 								OR
	 								({$wpdb->posts}.id IN (select page_id from ".MM_TABLE_CORE_PAGES." where ref_type='product' and ref_id IN (select product_id from ".MM_TABLE_BUNDLE_PRODUCTS." where bundle_id='{$_GET["access_tags"]}' )))
	 							) ";
	 		}
	 		if(isset($_GET["core_page_types"]) && !empty($_GET["core_page_types"]) && preg_match("/(core_pages|wp_pages)/", $_GET["core_page_types"]))
	 		{
	 			if($_GET["core_page_types"]=="core_pages")
	 			{
	 				$cp_sql  = " AND {$wpdb->posts}.id IN (select page_id from ".MM_TABLE_CORE_PAGES." where page_id IS NOT NULL ) ";	
	 			}
	 			else if($_GET["core_page_types"]=="wp_pages")
	 			{
	 				$cp_sql  = " AND {$wpdb->posts}.id NOT IN (select page_id from ".MM_TABLE_CORE_PAGES." where page_id IS NOT NULL ) ";	
	 			}
	 		}
	 		
	 		$where .= $mt_sql." ".$at_sql." ". $cp_sql;	
	 		//echo $where;
 		}
 		return $where;
 	}
 	
 	public function editPostsFilter()
 	{
 		global $post;
 		
 		$selectedMembership = (isset($_GET["member_types"])) ? $_GET["member_types"]:"";
 		$selectedBundle = (isset($_GET["access_tags"])) ? $_GET["access_tags"]:"";
 		
 		$select = "<select name='member_types'>
<option value=''>Show all Membership Levels</a>";
 		$select .= MM_HtmlUtils::getMemberships($selectedMembership);
 		$select .= "</select>";
 		
 		$select .= "<select name='access_tags'>
<option value=''>Show all Bundles</a>";
 		$select .= MM_HtmlUtils::getBundles($selectedBundle);
 		$select .= "</select>";
 		
 		if((isset($post->post_type) && $post->post_type=='page') || (isset($_GET["post_type"]) && $_GET["post_type"]=='page') )
 		{
 			$cpt = (isset($_GET["core_page_types"]))?$_GET["core_page_types"]:"";
 			$select .= "<select name='core_page_types'>
 <option value=''>Show all Pages</a>";
 			$select .= "<option value='core_pages' ".(($cpt=="core_pages")?"selected":"").">Show only MM Core Pages</a>";
 			$select .= "<option value='wp_pages' ".(($cpt=="wp_pages")?"selected":"").">Show only Standard Pages</a>";
 			$select.="</select>";
 		}
 		echo $select;
 	}
 	
 	public function postCustomColumns($column_name, $postId)
 	{
		if($column_name === 'core_page_type'){
			$data= "";
 			if(MM_CorePage::isDefaultCorePage($postId))
 			{
 				$data = MM_Utils::getDefaultFlag("", "", true, 'margin-right:5px;');
 			}
 			
 			$cp = MM_CorePage::getCorePageInfo($postId);
 			if(isset($cp->core_page_type_name))
 			{
 				switch($cp->core_page_type_id)
 				{
 					case MM_CorePageType::$FREE_CONFIRMATION:
 						$data .= "Confirmation (Free)";
 					break;
 					default:
 						$data .= $cp->core_page_type_name;
 					break;
 				}
 			}
 			
 			if(empty($data))
 			{
 				echo MM_NO_DATA;
 			}
 			else
 			{
 				echo $data;
 			}
		}
		else if($column_name === 'access_rights')
		{
			/// display access rights for post/page
			$associations = MM_ProtectedContentEngine::getAccessRights($postId);
			
			if(count($associations)<=0)
			{
				$memberTypesStr = "";
				$accessTagStr = "";
				$pages = MM_CorePage::getCorePagesByPageID($postId);
				if(is_array($pages))
				{
		 			foreach($pages as $page)
		 			{
		 				switch($page->ref_type)
		 				{
		 					case "product":
		 						$product = new MM_Product($page->ref_id);
		 						$membership = $product->getAssociatedMembership();
		 						if($membership->isValid())
			 					{
			 						if(empty($memberTypesStr))
			 						{
			 							$memberTypesStr = MM_Utils::getAccessIcon(MM_OrderItemAccess::$ACCESS_TYPE_MEMBERSHIP, '', 'margin-right:4px;');
			 						}
			 						$memberTypesStr .= $membership->getName().", ";
		 						}
		 						
		 						$bundle = $product->getAssociatedBundle();
		 						if($bundle->isValid()) 
		 						{
			 						if(empty($accessTagStr))
			 						{
			 							$accessTagStr = MM_Utils::getAccessIcon(MM_OrderItemAccess::$ACCESS_TYPE_BUNDLE, '', 'margin-right:4px;');
			 						}
			 						
			 						$accessTagStr.= $bundle->getName().", ";
		 						}
		 					break;
		 					case "member_type":
		 						if(empty($memberTypesStr))
		 						{
		 							$memberTypesStr = MM_Utils::getAccessIcon(MM_OrderItemAccess::$ACCESS_TYPE_MEMBERSHIP, '', 'margin-right:4px;');
		 						}
		 						$memberTypesStr.= $page->mt_name.", ";
		 					break;
		 					case "access_tag":
		 						if(empty($accessTagStr))
		 						{
		 							$accessTagStr = MM_Utils::getAccessIcon(MM_OrderItemAccess::$ACCESS_TYPE_BUNDLE, '', 'margin-right:4px;');
		 						}
		 						$accessTagStr.= $page->at_name.", ";
		 						
		 					break;
		 				}
		 			}
				}
	 			if(empty($memberTypesStr) && empty($accessTagStr))
	 			{
	 				echo MM_NO_DATA;	
	 			}
	 			else
	 			{
	 				if(strlen($memberTypesStr)>0)
	 					$memberTypesStr= substr($memberTypesStr, 0, strlen($memberTypesStr)-2);
	 				
	 				if(strlen($accessTagStr)>0)
	 					$accessTagStr= substr($accessTagStr, 0, strlen($accessTagStr)-2);
	 					
	 				echo $memberTypesStr." ".$accessTagStr;
	 			}	
			}
			else
			{
	        	$memberTypesStr = "";
	        	$accessTagStr = "";
	 			foreach($associations as $rights)
	 			{
	 				switch($rights->access_type)
	 				{
	 					case "member_type":
	 						if(empty($memberTypesStr))
	 						{
	 							$memberTypesStr = MM_Utils::getAccessIcon(MM_OrderItemAccess::$ACCESS_TYPE_MEMBERSHIP, '', 'margin-right:4px;');
	 						}
	 						$memberTypesStr.= $rights->mt_name.", ";
	 					break;
	 					case "access_tag":
	 						if(empty($accessTagStr))
	 						{
	 							$accessTagStr = MM_Utils::getAccessIcon(MM_OrderItemAccess::$ACCESS_TYPE_BUNDLE, '', 'margin-right:4px;');
	 						}
	 						$accessTagStr.= $rights->at_name.", ";
	 						
	 					break;
	 				}
	 			}
	 			
	 			if(empty($memberTypesStr) && empty($accessTagStr))
	 			{
	 				echo MM_NO_DATA;	
	 			}
	 			else
	 			{
	 				if(strlen($memberTypesStr)>0)
	 					$memberTypesStr= substr($memberTypesStr, 0, strlen($memberTypesStr)-2);
	 				
	 				if(strlen($accessTagStr)>0)
	 					$accessTagStr= substr($accessTagStr, 0, strlen($accessTagStr)-2);
	 					
	 				echo $memberTypesStr." ".$accessTagStr;
	 			}
			}
		}
 	}
 	
 	/** 
 	 * This function keeps default core page from being trashed
 	 */
 	public function trashPostHandler($post_id)
 	{
 		if(MM_CorePage::isDefaultCorePage($post_id))
 		{
 			MM_Messages::addError("MemberMouse default core pages cannot be deleted.");
 			wp_publish_post($post_id);
 			wp_redirect("edit.php?post_type=page");
 			exit;
 		}
		
		if(MM_CorePageEngine::isCorePage($post_id))
		{
			// We don't want non-default core pages sitting in the trash to retain their associations because it 
			// can cause issues with other non-default core pages that are currently published, so let's treat even 
			// sending a non-default core page to trash just the same as permanently deleting the page altogether 
			$this->deletePostHandler($post_id);
		}
 	}
 	
 	/** 
 	 * This function removes core page associations and access rights from posts when they're deleted
 	 */
 	public function deletePostHandler($post_id)
 	{
 		// remove access rights, if any
 		$protected_content = new MM_ProtectedContentEngine();
 		$protected_content->removeAllRights($post_id);
 		
 		// remove core page associations, if any
 		$corepage = new MM_CorePageEngine();
 		$corepage->removeCorePageById($post_id);
 	}
 	
 	
 	/**
 	 * Handler for actions that need to happen when a page is published, or is edited to have the status changed to published
 	 * 
 	 * @param int $ID
 	 * @param string $post
 	 */
 	public function publishPageHandler($ID, $post) 
 	{
 		if (MM_CorePageEngine::isCheckoutCorePage($ID))
 		{
 			//if a captcha is included on the checkout page, set flag in the database to enforce checking it
 			$hasCaptcha = preg_match("/\[\s*mm_form_field\s+(.*)(type=['\"]input['\"]){0,1}(name=['\"]captcha['\"])(.*)(type=['\"]input['\"]){0,1}(.*)\]/i",$post->post_content)?1:0;
 			MM_OptionUtils::setOption(MM_OptionUtils::$OPTION_KEY_CAPTCHA_ENABLED,$hasCaptcha);
 		}
 		
 		//rebuild core page cache in case the permalink has changed
 		MM_CorePageEngine::createCorePageCache();
 	}
 }
?>