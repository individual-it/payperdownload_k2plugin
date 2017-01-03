<?php
// import the JPlugin class
jimport('joomla.event.plugin');

global $mainframe;

class plgPayperDownloadplusK2 extends JPlugin
{
	public function __construct(&$subject, $config = array())
    {
        parent::__construct($subject);
		// load the language file
		$lang = JFactory::getLanguage();
		$lang->load('plg_payperdownloadplus_k2', JPATH_SITE.DS.'administrator');
	}

	function onIsActive(&$plugins)
	{
		$version = new JVersion;
		$component = JComponentHelper::getComponent('com_k2', true);
		if($component->enabled)
		{
			jimport('joomla.filesystem.file');
			$image = "";
			if($version->RELEASE == "1.5")
			{
				if(JFile::exists(JPATH_ROOT . DS . 'plugins' . DS . 'payperdownloadplus' . DS . 'k2.jpg'))
					$image = "plugins/payperdownloadplus/k2.jpg";
			}
			else
			{
				if(JFile::exists(JPATH_ROOT . DS . 'plugins' . DS . 'payperdownloadplus' . DS . 'k2' . DS . 'k2.jpg'))
					$image = "plugins/payperdownloadplus/k2/k2.jpg";
			}
				
			$plugins[] = array("name" => "K2", "description" => JText::_("K2 item"), 
				"image" => $image);
		}
	}
	
	function reorderCats(&$cats_ordered, $cats, $parent_id, $depth)
	{
		$count = count($cats);
		for($i = 0; $i < $count; $i++)
		{
			$cat = $cats[$i];
			if($cat->parentid == $parent_id)
			{
				$cat->depth = $depth;
				$cats_ordered[] = $cat;
				$this->reorderCats($cats_ordered, $cats, $cat->id, $depth + 1);
			}
		}
	}
	
	function getCategories()
	{
		$db = JFactory::getDBO();
		$db->setQuery('SELECT id, name, parent as parentid FROM #__k2_categories WHERE published <> 0 and trash = 0');
		$cats = $db->loadObjectList();
		$cats_ordered = array();
		$this->reorderCats($cats_ordered, $cats, 0, 0);
		return $cats_ordered;
	}
	
	function getFiles($cat_id)
	{
		$db = JFactory::getDBO();
		$db->setQuery('SELECT id, title FROM #__k2_items WHERE catid = ' . (int)$cat_id);
		return $db->loadObjectList();
	}
	
	function getAttachments($file_id)
	{
		$db = JFactory::getDBO();
		$db->setQuery('SELECT id, filename FROM #__k2_attachments WHERE itemID = ' . (int)$file_id);
		return $db->loadObjectList();
	}
	
	function onRenderConfig($pluginName, $resource)
	{
		if($pluginName == "K2")
		{
			$files = null;
			$paytoreadmore = "";
			if($resource)
			{
				$file_id = $resource->resource_id;
				if($file_id)
					$attachments = $this->getAttachments($file_id);
				list($category_id, $attachment_id, $paytoreadmore) = explode('_', $resource->resource_params);
				if($category_id)
					$files = $this->getFiles($category_id);
			}
			$uri = JURI::root();
			$scriptPath = "administrator/components/com_payperdownload/js/";
			JHTML::script($scriptPath . 'ajax_source.js');
			$version = new JVersion;
			if($version->RELEASE >= "1.6")
				$plugin_path = "plugins/payperdownloadplus/k2/";
			else
				$plugin_path = "plugins/payperdownloadplus/";
			$scriptPath = $uri . $plugin_path;
			JHTML::script($scriptPath . 'k2_plugin.js');
			$cats = $this->getCategories();
			?>
			<tr>
			<td  width="100" align="left" class="key"><?php echo htmlspecialchars(JText::_("PAYPERDOWNLOADPLUS_K2_PLUGIN_CATEGORY"));?></td>
			<td>
			<select id="k2_category" name="k2_category" onchange="k2_plugin_category_change();">
			<option value="0"><?php echo JText::_("PAYPERDOWNLOADPLUS_K2_PLUGIN_ALL_CATEGORIES");?></option>
			<?php
			foreach($cats as $cat)
			{
				$space = '';
				for($i = 0; $i < $cat->depth; $i++)
					$space .= '&nbsp;&nbsp;&nbsp;&nbsp;';
				$selected = $cat->id == $category_id ? "selected":"";
				echo "<option value=\"" . htmlspecialchars($cat->id) . "\" $selected>" . $space . htmlspecialchars($cat->name) . "</option>";
			}
			?>
			</select>
			</td>
			</tr>
			<tr>
			<td  width="100" align="left" class="key">
				<?php echo htmlspecialchars(JText::_("PAYPERDOWNLOADPLUS_K2_PLUGIN_ALLOW_READING_HEADER"));?>
			</td>
			<?php
			$checked = $paytoreadmore == "1" ? "checked" : "";
			?>
			<td><input type="checkbox" name="paytoreadmore" <?php echo $checked;?> value="1"/></td>
			</tr>
			<tr>
			<td  width="100" align="left" class="key"><?php echo htmlspecialchars(JText::_("PAYPERDOWNLOADPLUS_K2_PLUGIN_DOWNLOAD"));?></td>
			<td>
			<select id="k2_file" name="k2_file" onchange="k2_plugin_file_change();">
			<option value="0"><?php echo JText::_("PAYPERDOWNLOADPLUS_K2_PLUGIN_ALL_DOWNLOADS");?></option>
			<?php
			if($files)
			foreach($files as $file)
			{
				$selected = $file->id == $file_id ? "selected":"";
				echo "<option value=\"" . htmlspecialchars($file->id) . "\" $selected>" . htmlspecialchars($file->title) . "</option>";
			}
			?>
			</select>
			</td>
			</tr>
			<tr>
			<td  width="100" align="left" class="key"><?php echo htmlspecialchars(JText::_("PAYPERDOWNLOADPLUS_K2_PLUGIN_ATTACHMENT"));?></td>
			<td>
			<select id="k2_attachement" name="k2_attachement">
			<option value="0"><?php echo JText::_("PAYPERDOWNLOADPLUS_K2_PLUGIN_ALL_ATTACHMENTS");?></option>
			<?php
			if($attachments)
			foreach($attachments as $attachment)
			{
				$selected = $attachment->id == $attachment_id ? "selected":"";
				echo "<option value=\"" . htmlspecialchars($attachment->id) . "\" $selected>" . htmlspecialchars($attachment->filename) . "</option>";
			}
			?>
			</select>
			</td>
			</tr>
			<?php
		}
	}
	
	function onGetSaveData(&$resourceId, 
		$pluginName, &$resourceName, &$resourceParams, &$optionParameter,
		&$resourceDesc)
	{
		if($pluginName == "K2")
		{
			$optionParameter = "com_k2";
			$resourceId = JRequest::getInt('k2_file');
			$categoryId = JRequest::getInt('k2_category');
			$attachmentId = JRequest::getInt('k2_attachement');
			$paytoreadmore = JRequest::getInt('paytoreadmore', 0);
			$db =& JFactory::getDBO();
			$query = "";
			$resourceDesc = JText::_("PAYPERDOWNLOADPLUS_K2_PLUGIN_ALL_DOWNLOADS");
			if($attachmentId)
			{
				$query = 'SELECT id, filename as title FROM #__k2_attachments WHERE id = ' . $attachmentId;
				$resourceDesc = JText::_("PAYPERDOWNLOADPLUS_K2_PLUGIN_ATTACHMENT");
			}
			else
			if($resourceId)
			{
				$attachmentId = -1;
				$query = "SELECT id, title FROM #__k2_items WHERE id = " . $resourceId;
				$resourceDesc = JText::_("PAYPERDOWNLOADPLUS_K2_PLUGIN_DOWNLOAD");
			}
			else if($categoryId)
			{
				$resourceId = -1;
				$attachmentId = -1;
				$query = "SELECT id, name as title FROM #__k2_categories WHERE id = " . $categoryId;
				$resourceDesc = JText::_("PAYPERDOWNLOADPLUS_K2_PLUGIN_CATEGORY");
			}
			else
			{
				$resourceId = -1;
			}
			$resourceName = JText::_("PAYPERDOWNLOADPLUS_K2_PLUGIN_ALL_DOWNLOADS");
			if($query)
			{
				$db->setQuery($query);
				$resource = $db->loadObject();
				if($resource)
					$resourceName = $resource->title;
			}
			$resourceParams = $categoryId . "_" . $attachmentId . "_" . $paytoreadmore;
		}
	}
	
	function onValidateAccess($option, $resources, &$allowAccess, &$requiredLicenses, &$resourcesId)
	{
		if($option == 'com_k2')
		{
			$view = JRequest::getVar('view', 'item');
			$task = JRequest::getVar('task', '');
			if($view == 'item' && $task != 'download' && $task != 'save')
			{
				$requiredLicenses = array();
				$resourcesId = array();
				$paytoreadmore = 0;
				$item = JRequest::getInt('id', 0);
				
				foreach($resources as $resource)
				{
					list($categoryId, $attachmentId, $paytoreadmore) = explode('_', $resource->resource_params);
					if(isset($paytoreadmore) && (int)$paytoreadmore == 1)
						continue;
					if($resource->resource_id == $item && $attachmentId == -1)
					{
						if($resource->license_id)
						{
							if(array_search($resource->license_id, $requiredLicenses) === false)
								$requiredLicenses[] = $resource->license_id;
						}
						else
						{
							if(array_search($resource->resource_license_id, $resourcesId) === false)
								$resourcesId[] = $resource->resource_license_id;
						}
						$allowAccess = false;
					}
				}
				
				if(count($requiredLicenses) == 0)
				{
					$db = JFactory::getDBO();
					$db->setQuery("SELECT catid FROM #__k2_items WHERE id =" . (int)$item);
					$cats = $db->loadResultArray();
					if(count($cats) == 0)
						return;
					foreach($resources as $resource)
					{
						list($categoryId, $attachmentId) = explode('_', $resource->resource_params);
						if($resource->resource_id == -1 && array_search($categoryId, $cats) !== false)
						{
							if($resource->license_id)
							{
								if(array_search($resource->license_id, $requiredLicenses) === false)
									$requiredLicenses[] = $resource->license_id;
							}
							else
							{
								if(array_search($resource->resource_license_id, $resourcesId) === false)
									$resourcesId[] = $resource->resource_license_id;
							}
							$allowAccess = false;
						}
					}
				}
			}
			else
			if($view == 'item' && $task == 'download')
			{
				$requiredLicenses = array();
				$resourcesId = array();
				$attachment = JRequest::getInt('id', 0);
				
				foreach($resources as $resource)
				{
					list($categoryId, $attachmentId) = split('_', $resource->resource_params);
					if($attachmentId == $attachment)
					{
						if($resource->license_id)
						{
							if(array_search($resource->license_id, $requiredLicenses) === false)
								$requiredLicenses[] = $resource->license_id;
						}
						else
						{
							if(array_search($resource->resource_license_id, $resourcesId) === false)
								$resourcesId[] = $resource->resource_license_id;
						}
						$allowAccess = false;
					}
				}
				
				$db = JFactory::getDBO();
				$db->setQuery('SELECT itemID FROM #__k2_attachments WHERE id = ' . $attachment);
				$item = (int)$db->loadResult();
				
				if(count($requiredLicenses) == 0)
				{
					foreach($resources as $resource)
					{
						list($categoryId, $attachmentId) = split('_', $resource->resource_params);
						if($resource->resource_id == $item && $attachmentId == -1)
						{
							if($resource->license_id)
							{
								if(array_search($resource->license_id, $requiredLicenses) === false)
									$requiredLicenses[] = $resource->license_id;
							}
							else
							{
								if(array_search($resource->resource_license_id, $resourcesId) === false)
									$resourcesId[] = $resource->resource_license_id;
							}
							$allowAccess = false;
						}
					}
				}
				
				if(count($requiredLicenses) == 0)
				{
					$db->setQuery('SELECT catid FROM #__k2_items WHERE id = ' . $item);
					$cat = (int)$db->loadResult();
					
					foreach($resources as $resource)
					{
						list($categoryId, $attachmentId) = split('_', $resource->resource_params);
						if($categoryId == $cat && $resource->resource_id == -1 && $attachmentId == -1)
						{
							if($resource->license_id)
							{
								if(array_search($resource->license_id, $requiredLicenses) === false)
									$requiredLicenses[] = $resource->license_id;
							}
							else
							{
								if(array_search($resource->resource_license_id, $resourcesId) === false)
									$resourcesId[] = $resource->resource_license_id;
							}
							$allowAccess = false;
						}
					}
				}
			}
		}
	}
	
	function onAjaxCall($plugin, &$output)
	{
		if($plugin == "k2")
		{
			$x = JRequest::getInt('x', 0);
			$t = JRequest::getVar('t', 'f');
			$db = JFactory::getDBO();
			if($t=='a')
			{
				$db->setQuery('SELECT id, filename FROM #__k2_attachments WHERE itemID = ' . $x);
				$files = $db->loadObjectList();
				$output = '<<' . count($files);
				foreach($files as $file)
				{
					$output .= '>' . htmlspecialchars($file->id) . "<" . htmlspecialchars($file->filename);
				}
				$output .= '>>';
			}
			else if($t=='f') 
			{
				$db->setQuery('SELECT id, title FROM #__k2_items WHERE catid = ' . $x);
				$files = $db->loadObjectList();
				$output .= '<<' . count($files);
				foreach($files as $file)
				{
					$output .= '>' . htmlspecialchars($file->id) . "<" . htmlspecialchars($file->title);
				}
				$output .= '>>';
			}
		}
	}
	
	function getReturnPage($option, &$returnPage)
	{
		if($option == "com_k2")
		{
			$task = JRequest::getVar('task', '');
			$item = JRequest::getInt('id', 0);
			if($task == 'download')
			{
				$db =& JFactory::getDBO();
				$db->setQuery('SELECT itemID FROM #__k2_attachments WHERE id = ' . $item);
				$item = (int)$db->loadResult();
			}
			$returnPage = "index.php?option=com_k2&view=item&layout=item&id=" . urlencode($item);
		}
	}
	
	/*
	Returns item id for k2 item
	*/
	function onGetItemId($option, &$itemId)
	{
		if($option == 'com_k2')
		{
			$itemId = JRequest::getInt('id', 0);
		}
	}
}
?>