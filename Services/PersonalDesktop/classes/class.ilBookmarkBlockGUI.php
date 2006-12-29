<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2006 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/

include_once("Services/Block/classes/class.ilBlockGUI.php");

/**
* BlockGUI class for Bookmarks block
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*/
class ilBookmarkBlockGUI extends ilBlockGUI
{
	
	/**
	* Constructor
	*/
	function ilBookmarkBlockGUI($a_parent_class, $a_parent_cmd = "")
	{
		global $ilCtrl, $lng, $ilUser;
		
		parent::ilBlockGUI($a_parent_class, $a_parent_cmd);
		
		$this->setImage(ilUtil::getImagePath("icon_bm_s.gif"));
		$this->setTitle($lng->txt("my_bms"));
		$this->setEnableNumInfo(false);
		$this->setLimit(99999);
		$this->setBlockIdentification("bm", $ilUser->getId());
		$this->setPrefix("pdbm");
		$this->setAvailableDetailLevels(3);
		
		$this->id = (empty($_GET["bmf_id"]))
			? $bmf_id = 1
			: $_GET["bmf_id"];
	}
	
	function getHTML()
	{
		if ($this->getCurrentDetailLevel() == 0)
		{
			return "";
		}
		else
		{
			return parent::getHTML();
		}
	}
	
	/**
	* Fill data section
	*/
	function fillDataSection()
	{
		global $ilUser;
		
		include_once("./Services/PersonalDesktop/classes/class.ilBookmarkFolder.php");
		$bm_items = ilBookmarkFolder::_getNumberOfObjects();
		$this->num_bookmarks = $bm_items["bookmarks"];
		$this->num_folders = $bm_items["folders"];

		if ($this->getCurrentDetailLevel() > 1 &&
			($this->num_bookmarks > 0 || $this->num_folders > 0))
		{
			if ($ilUser->getPref("il_pd_bkm_mode") == 'tree')
			{
				$this->setDataSection($this->getPDBookmarkListHTMLTree());
				$this->fillBlockFooter();
			}
			else
			{
				$this->setRowTemplate("tpl.bookmark_pd_list.html", "Services/PersonalDesktop");
				$this->getListRowData();
				$this->setColSpan(2);
				parent::fillDataSection();
				$this->fillBlockFooter();
			}
		}
		else
		{
			$this->setDataSection($this->getOverview());
		}
	}
	
	/**
	* get tree bookmark list for personal desktop
	*/
	function getPDBookmarkListHTMLTree()
	{
		global $ilCtrl, $ilUser;
		
		$showdetails = ($this->getCurrentDetailLevel() > 2);
		$tpl = new ilTemplate("tpl.bookmark_pd_tree.html", true, true,
			"Services/PersonalDesktop");

		$exp = new ilBookmarkExplorer($ilCtrl->getParentReturnByClass("ilbookmarkadministrationgui"),
			$_SESSION["AccountId"]);
		$exp->setAllowedTypes(array('dum','bmf','bm'));
		$exp->setEnableSmallMode(true);
		$exp->setTargetGet("bmf_id");
		$exp->setSessionExpandVariable('mexpand');
		$ilCtrl->setParameterByClass("ilbookmarkadministrationgui", "bmf_id", $this->id);
		$exp->setExpandTarget($ilCtrl->getParentReturnByClass("ilbookmarkadministrationgui"));
		if ($_GET["mexpand"] == "")
		{
			$expanded = $this->id;
		}
		else
		{
			$expanded = $_GET["mexpand"];
		}
		$exp->setExpand($expanded);
		$exp->setShowDetails($showdetails);

		// build html-output
		$exp->setOutput(0);
		return $exp->getOutput();
	}

	/**
	* block footer
	*/
	function fillBlockFooter()
	{
		global $ilCtrl, $lng, $ilUser;
		
		// flat
		if ($ilUser->getPref("il_pd_bkm_mode") == 'tree')
		{
			$this->tpl->setCurrentBlock("foot_link");
			$this->tpl->setVariable("FHREF", $ilCtrl->getLinkTargetByClass("ilbookmarkadministrationgui",
				"setPdFlatMode"));
			$this->tpl->setVariable("FLINK", $lng->txt("flatview"));
		}
		else
		{
			$this->tpl->setCurrentBlock("foot_text");
			$this->tpl->setVariable("FTEXT", $lng->txt("flatview"));
		}
		$this->tpl->parseCurrentBlock();
		$this->tpl->touchBlock("foot_item");
		
		$this->tpl->touchBlock("foot_delim");
		$this->tpl->touchBlock("foot_item");

		// as tree
		if ($ilUser->getPref("il_pd_bkm_mode") == 'tree')
		{
			$this->tpl->setCurrentBlock("foot_text");
			$this->tpl->setVariable("FTEXT", $lng->txt("treeview"));
		}
		else
		{
			$this->tpl->setCurrentBlock("foot_link");
			$this->tpl->setVariable("FHREF", $ilCtrl->getLinkTargetByClass("ilbookmarkadministrationgui",
				"setPdTreeMode"));
			$this->tpl->setVariable("FLINK", $lng->txt("treeview"));
		}
		$this->tpl->parseCurrentBlock();
		$this->tpl->touchBlock("foot_item");

		$this->tpl->setCurrentBlock("block_footer");
		$this->tpl->setVariable("FCOLSPAN", $this->getColSpan());
		$this->tpl->parseCurrentBlock();
	}

	/**
	* Get list data (for flat list).
	*/
	function getListRowData()
	{
		global $ilUser, $lng, $ilCtrl;
		
		include_once("./Services/PersonalDesktop/classes/class.ilBookmarkFolder.php");

		$data = array();
		
		$bm_items = ilBookmarkFolder::getObjects($_SESSION["ilCurBMFolder"]);

		if (!ilBookmarkFolder::isRootFolder($_SESSION["ilCurBMFolder"])
			&& !empty($_SESSION["ilCurBMFolder"]))
		{			
			$ilCtrl->setParameterByClass("ilbookmarkadministrationgui", "curBMFolder",
				ilBookmarkFolder::_getParentId($_SESSION["ilCurBMFolder"]));

			$data[] = array(
				"img" => ilUtil::getImagePath("icon_cat_s.gif"),
				"alt" => $lng->txt("bmf"),
				"title" => "..",
				"link" => $ilCtrl->getLinkTargetByClass("ilbookmarkadministrationgui", "setCurrentBookmarkFolder"));

			$this->setTitle($this->getTitle().": ".ilBookmarkFolder::_lookupTitle($_SESSION["ilCurBMFolder"]));
		}

		foreach ($bm_items as $bm_item)
		{
			switch ($bm_item["type"])
			{
				case "bmf":
					$ilCtrl->setParameterByClass("ilbookmarkadministrationgui", "curBMFolder", $bm_item["obj_id"]);
					$data[] = array(
						"img" => ilUtil::getImagePath("icon_cat_s.gif"),
						"alt" => $lng->txt("bmf"),
						"title" => ilUtil::prepareFormOutput($bm_item["title"]),
						"desc" => ilUtil::prepareFormOutput($bm_item["desc"]),
						"link" => $ilCtrl->getLinkTargetByClass("ilbookmarkadministrationgui",
							"setCurrentBookmarkFolder"),
						"target" => "");
					break;

				case "bm":
					$data[] = array(
						"img" => ilUtil::getImagePath("spacer.gif"),
						"alt" => $lng->txt("bm"),
						"title" => ilUtil::prepareFormOutput($bm_item["title"]),
						"desc" => ilUtil::prepareFormOutput($bm_item["desc"]),
						"link" => ilUtil::prepareFormOutput($bm_item["target"]),
						"target" => "_blank");
					break;
			}
		}
		
		$this->setData($data);
	}
	
	/**
	* get flat bookmark list for personal desktop
	*/
	function fillRow($a_set)
	{
		global $ilUser;
		
		$this->tpl->setVariable("IMG_BM", $a_set["img"]);
		$this->tpl->setVariable("IMG_ALT", $a_set["alt"]);
		$this->tpl->setVariable("BM_TITLE", $a_set["title"]);
		$this->tpl->setVariable("BM_LINK", $a_set["link"]);
		$this->tpl->setVariable("BM_TARGET", $a_set["target"]);

		if ($this->getCurrentDetailLevel() > 2)
		{
			$this->tpl->setVariable("BM_DESCRIPTION", ilUtil::prepareFormOutput($a_set["desc"]));
		}
		else
		{
			$this->tpl->setVariable("BM_TOOLTIP", ilUtil::prepareFormOutput($a_set["desc"]));
		}
	}

	/**
	* Get overview.
	*/
	function getOverview()
	{
		global $ilUser, $lng, $ilCtrl;
				
		return '<div class="small">'.$this->num_bookmarks." ".$lng->txt("bm_num_bookmarks").", ".
			$this->num_folders." ".$lng->txt("bm_num_bookmark_folders")."</div>";
	}

}

?>
