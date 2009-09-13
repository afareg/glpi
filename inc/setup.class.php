<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}

class SetupSearchDisplay extends CommonDBTM{

	/**
	 * Constructor
	**/
	function __construct () {
		$this->table="glpi_displayprefs";
		$this->type=-1;
	}

	function prepareInputForAdd($input) {
		global $DB;
		$query="SELECT MAX(rank) 
			FROM ".$this->table." 
			WHERE itemtype='".$input["itemtype"]."' AND users_id='".$input["users_id"]."'";
		$result=$DB->query($query);
		$input["rank"]=$DB->result($result,0,0)+1;

		return $input;
	}

	/**
	 * Active personal config based on global one
	 *
	 *@param $input parameter array (itemtype,users_id)
	 *
	 **/
	function activatePerso($input){
		global $DB,$SEARCH_OPTION;

		if (!haveRight("search_config","w")) return false;

		$query="SELECT * 
			FROM ".$this->table." 
			WHERE itemtype='".$input["itemtype"]."' AND users_id='0'";
		$result=$DB->query($query);
		if ($DB->numrows($result)){
			while ($data=$DB->fetch_assoc($result)){
				unset($data["id"]);
				$data["users_id"]=$input["users_id"];
				$this->fields=$data;
				$this->addToDB();
			}
		} else {
			// No items in the global config
			if (count($SEARCH_OPTION[$input["itemtype"]])>1){
				$done=false;
				foreach($SEARCH_OPTION[$input["itemtype"]] as $key => $val)
					if (is_array($val)&&$key!=1&&!$done){
						$data["users_id"]=$input["users_id"];
						$data["itemtype"]=$input["itemtype"];
						$data["rank"]=1;
						$data["num"]=$key;
						$this->fields=$data;
						$this->addToDB();
						$done=true;
					}


			}

		}

	}

	/**
	 * Move up an item
	 *
	 *@param $input parameter array (id,itemtype,users_id)
	 *
	 **/
	function up($input){
		global $DB;
		// Get current item
		$query="SELECT rank 
			FROM ".$this->table." 
			WHERE id='".$input['id']."';";
		$result=$DB->query($query);
		$rank1=$DB->result($result,0,0);
		// Get previous item
		$query="SELECT id, rank 
			FROM ".$this->table." 
			WHERE itemtype='".$input['itemtype']."' AND users_id='".$input["users_id"]."' AND rank<'$rank1'
			ORDER BY rank DESC;";
		$result=$DB->query($query);
		$rank2=$DB->result($result,0,"rank");
		$ID2=$DB->result($result,0,"id");
		// Update items
		$query="UPDATE ".$this->table." 
			SET rank='$rank2' 
			WHERE id ='".$input['id']."'";
		$DB->query($query);
		$query="UPDATE ".$this->table." 
			SET rank='$rank1' 
			WHERE id ='$ID2'";
		$DB->query($query);
	}

	/**
	 * Move down an item
	 *
	 *@param $input parameter array (id,itemtype,users_id)
	 *
	 **/
	function down($input){
		global $DB;

		// Get current item
		$query="SELECT rank 
			FROM ".$this->table." 
			WHERE id='".$input['id']."';";
		$result=$DB->query($query);
		$rank1=$DB->result($result,0,0);
		// Get next item
		$query="SELECT id, rank 
			FROM ".$this->table." 
			WHERE itemtype='".$input['itemtype']."' AND users_id='".$input["users_id"]."' AND rank>'$rank1'
			ORDER BY rank ASC;";
		$result=$DB->query($query);
		$rank2=$DB->result($result,0,"rank");
		$ID2=$DB->result($result,0,"id");
		// Update items
		$query="UPDATE ".$this->table." 
			SET rank='$rank2' 
			WHERE id ='".$input['id']."'";
		$DB->query($query);
		$query="UPDATE ".$this->table." 
			SET rank='$rank1' 
			WHERE id ='$ID2'";
		$DB->query($query);
	}

	/**
	 * Print the search config form
	 *
	 *@param $target form target
	 *@param $itemtype item type
	 *
	 *@return nothing
	 **/
	function showFormPerso($target,$itemtype){
		global $SEARCH_OPTION,$CFG_GLPI,$LANG,$DB;

		if (!isset($SEARCH_OPTION[$itemtype])) {
			return false;
		}

		$IDuser=$_SESSION["glpiID"];

		echo "<div class='center' id='tabsbody' >";
		// Defined items
		$query="SELECT * 
			FROM ".$this->table." 
			WHERE itemtype='$itemtype' AND users_id='$IDuser'
			ORDER BY rank";

		$result=$DB->query($query);
		$numrows=0;
		$numrows=$DB->numrows($result);
		if ($numrows==0){
			checkRight("search_config","w");
			echo "<table class='tab_cadre_fixe' cellpadding='2' ><tr><th colspan='4'>";
			echo "<form method='post' action=\"$target\">";
			echo "<input type='hidden' name='itemtype' value='$itemtype'>";
			echo "<input type='hidden' name='users_id' value='$IDuser'>";

			echo $LANG['setup'][241];
			echo "&nbsp;&nbsp;&nbsp;<input type='submit' name='activate' value=\"".$LANG['buttons'][2]."\" class='submit' >";
			echo "</form></th></tr></table>";

		} else {

			echo "<table class='tab_cadre_fixe' cellpadding='2' ><tr><th colspan='4'>";
			echo $LANG['setup'][252].": </th></tr>";
			echo "<tr class='tab_bg_1'><td colspan='4' align='center'>";
			echo "<form method='post' action=\"$target\">";
			echo "<input type='hidden' name='itemtype' value='$itemtype'>";
			echo "<input type='hidden' name='users_id' value='$IDuser'>";
			echo "<select name='num'>";
			$first_group=true;
			$searchopt=cleanSearchOption($itemtype);
			foreach ($searchopt as $key => $val)
				if (!is_array($val)){
					if (!$first_group) echo "</optgroup>";
					else $first_group=false;
					echo "<optgroup label=\"".$val."\">";
				} else 	if ($key!=1){
					echo "<option value='$key'>".$val["name"]."</option>";
				}
			if (!$first_group) echo "</optgroup>";

			echo "</select>";
			echo "&nbsp;&nbsp;&nbsp;<input type='submit' name='add' value=\"".$LANG['buttons'][8]."\" class='submit' >";
			echo "</form>";
			echo "</td></tr>";


			// print first element 
			echo "<tr class='tab_bg_2'><td  align='center' width='50%'>";
			echo $SEARCH_OPTION[$itemtype][1]["name"];


				echo "</td><td colspan='3'>&nbsp;</td>";
			echo "</tr>";
			$i=0;
			if ($numrows){
				while ($data=$DB->fetch_array($result)){
					if ($data["num"]!=1 && isset($SEARCH_OPTION[$itemtype][$data["num"]])){
						echo "<tr class='tab_bg_2'><td align='center' width='50%' >";
						echo $SEARCH_OPTION[$itemtype][$data["num"]]["name"];
						echo "</td>";
						if ($i!=0){
							echo "<td align='center' valign='middle'>";
							echo "<form method='post' action=\"$target\">";
							echo "<input type='hidden' name='id' value='".$data["id"]."'>";
							echo "<input type='hidden' name='users_id' value='$IDuser'>";

							echo "<input type='hidden' name='itemtype' value='$itemtype'>";
							echo "<input type='image' name='up'  value=\"".$LANG['buttons'][24]."\"  src=\"".$CFG_GLPI["root_doc"]."/pics/puce-up2.png\" alt=\"".$LANG['buttons'][24]."\"  title=\"".$LANG['buttons'][24]."\" >";	
							echo "</form>";
							echo "</td>";
						} else echo "<td>&nbsp;</td>";
						if ($i!=$numrows-1){
							echo "<td align='center' valign='middle'>";
							echo "<form method='post' action=\"$target\">";
							echo "<input type='hidden' name='id' value='".$data["id"]."'>";
							echo "<input type='hidden' name='users_id' value='$IDuser'>";

							echo "<input type='hidden' name='itemtype' value='$itemtype'>";
							echo "<input type='image' name='down' value=\"".$LANG['buttons'][25]."\" src=\"".$CFG_GLPI["root_doc"]."/pics/puce-down2.png\" alt=\"".$LANG['buttons'][25]."\"  title=\"".$LANG['buttons'][25]."\" >";	
							echo "</form>";
							echo "</td>";
						} else echo "<td>&nbsp;</td>";

						echo "<td align='center' valign='middle'>";
						echo "<form method='post' action=\"$target\">";
						echo "<input type='hidden' name='id' value='".$data["id"]."'>";
						echo "<input type='hidden' name='users_id' value='$IDuser'>";

						echo "<input type='hidden' name='itemtype' value='$itemtype'>";
						echo "<input type='image' name='delete' value=\"".$LANG['buttons'][6]."\"src=\"".$CFG_GLPI["root_doc"]."/pics/puce-delete2.png\" alt=\"".$LANG['buttons'][6]."\"  title=\"".$LANG['buttons'][6]."\" >";	
						echo "</form>";
						echo "</td>";
						echo "</tr>";
						$i++;
					}
				}
			}
		echo "</table>";
		}			
		echo "</div>";
	}

	/**
	 * Print the search config form
	 *
	 *@param $target form target
	 *@param $itemtype item type
	 *
	 *@return nothing
	 **/
	function showFormGlobal($target,$itemtype){
		global $SEARCH_OPTION,$CFG_GLPI,$LANG,$DB;

		if (!isset($SEARCH_OPTION[$itemtype])) {
			return false;
		}
		$IDuser=0;

		$global_write=haveRight("search_config_global","w");

		echo "<div class='center' id='tabsbody' >";
		// Defined items
		$query="SELECT * 
			FROM ".$this->table." 
			WHERE itemtype='$itemtype' AND users_id='$IDuser'
			ORDER BY rank";

		$result=$DB->query($query);
		$numrows=0;
		$numrows=$DB->numrows($result);

		echo "<table class='tab_cadre_fixe' cellpadding='2' ><tr><th colspan='4'>";
		echo $LANG['setup'][252].": </th></tr>";
		if ($global_write){
			echo "<tr class='tab_bg_1'><td colspan='4' align='center'>";
			echo "<form method='post' action=\"$target\">";
			echo "<input type='hidden' name='itemtype' value='$itemtype'>";
			echo "<input type='hidden' name='users_id' value='$IDuser'>";
			echo "<select name='num'>";
			$first_group=true;
			$searchopt=cleanSearchOption($itemtype);
			foreach ($searchopt as $key => $val)
				if (!is_array($val)){
					if (!$first_group) echo "</optgroup>";
					else $first_group=false;
					echo "<optgroup label=\"".$val."\">";
				} else 	if ($key!=1){
					echo "<option value='$key'>".$val["name"]."</option>";
				}
			if (!$first_group) echo "</optgroup>";

			echo "</select>";
			echo "&nbsp;&nbsp;&nbsp;<input type='submit' name='add' value=\"".$LANG['buttons'][8]."\" class='submit' >";
			echo "</form>";
			echo "</td></tr>";
		}

		// print first element 
		echo "<tr class='tab_bg_2'><td  align='center' width='50%'>";
		echo $SEARCH_OPTION[$itemtype][1]["name"];


		if ($global_write){
			echo "</td><td colspan='3'>&nbsp;</td>";
		}
		echo "</tr>";
		$i=0;
		if ($numrows){
			while ($data=$DB->fetch_array($result)){
				if ($data["num"]!=1 && isset($SEARCH_OPTION[$itemtype][$data["num"]])){
					echo "<tr class='tab_bg_2'><td align='center' width='50%' >";
					echo $SEARCH_OPTION[$itemtype][$data["num"]]["name"];
					echo "</td>";
					if ($global_write){
						if ($i!=0){
							echo "<td align='center' valign='middle'>";
							echo "<form method='post' action=\"$target\">";
							echo "<input type='hidden' name='id' value='".$data["id"]."'>";
							echo "<input type='hidden' name='users_id' value='$IDuser'>";

							echo "<input type='hidden' name='itemtype' value='$itemtype'>";
							echo "<input type='image' name='up'  value=\"".$LANG['buttons'][24]."\"  src=\"".$CFG_GLPI["root_doc"]."/pics/puce-up2.png\" alt=\"".$LANG['buttons'][24]."\"  title=\"".$LANG['buttons'][24]."\" >";	
							echo "</form>";
							echo "</td>";
						} else echo "<td>&nbsp;</td>";
						if ($i!=$numrows-1){
							echo "<td align='center' valign='middle'>";
							echo "<form method='post' action=\"$target\">";
							echo "<input type='hidden' name='id' value='".$data["id"]."'>";
							echo "<input type='hidden' name='users_id' value='$IDuser'>";

							echo "<input type='hidden' name='itemtype' value='$itemtype'>";
							echo "<input type='image' name='down' value=\"".$LANG['buttons'][25]."\" src=\"".$CFG_GLPI["root_doc"]."/pics/puce-down2.png\" alt=\"".$LANG['buttons'][25]."\"  title=\"".$LANG['buttons'][25]."\" >";	
							echo "</form>";
							echo "</td>";
						} else echo "<td>&nbsp;</td>";

						echo "<td align='center' valign='middle'>";
						echo "<form method='post' action=\"$target\">";
						echo "<input type='hidden' name='id' value='".$data["id"]."'>";
						echo "<input type='hidden' name='users_id' value='$IDuser'>";

						echo "<input type='hidden' name='itemtype' value='$itemtype'>";
						echo "<input type='image' name='delete' value=\"".$LANG['buttons'][6]."\"src=\"".$CFG_GLPI["root_doc"]."/pics/puce-delete2.png\" alt=\"".$LANG['buttons'][6]."\"  title=\"".$LANG['buttons'][6]."\" >";	
						echo "</form>";
						echo "</td>";
					}
					echo "</tr>";
					$i++;
				}
			}
		}
	echo "</table>";
	echo "</div>";
	}

}

?>
