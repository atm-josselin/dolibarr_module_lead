<?php
/* 
 * Copyright (C) 2014 Florian HENRY <florian.henry@open-concept.pro>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file lead/class/html.fromlead.class.php
 * \ingroup lead
 * \brief File of class with all html predefined components
 */
class FormLead extends Form
{

	var $db;

	var $error;

	var $num;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db
	 *        	handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Build Select List of element associable to a businesscase
	 *
	 * @param object $tablename
	 *        	to parse
	 * @param object $lead        	
	 * @param string $htmlname
	 *        	name of the component
	 * @return string HTML select list of element
	 */
	function select_element($tablename, $lead, $htmlname = 'elementselect')
	{
		global $langs, $conf;
		
		switch ($tablename) {
			case "facture":
				$sql = "SELECT rowid, facnumber as ref, total as total_ht, date_valid as date_element";
				break;
			default:
				$sql = "SELECT rowid, ref, total_ht, datep as date_element";
				break;
		}
		
		$sql .= " FROM " . MAIN_DB_PREFIX . $tablename;
		$sql .= " WHERE rowid NOT IN (SELECT fk_source FROM " . MAIN_DB_PREFIX . "element_element WHERE targettype='" . $lead->element . "')";
		$sql .= " AND fk_soc=" . $lead->fk_soc;
		// $sql.= " AND entity IN (".getEntity($object->element,1).")";
		$sql .= " ORDER BY ref DESC";
		
		dol_syslog(get_class($this) . "::select_element sql=" . $sql, LOG_DEBUG);
		
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num > 0) {
				$sellist = '<select class="flat" name="' . $htmlname . '">';
				while ($i < $num) {
					$obj = $this->db->fetch_object($resql);
					$sellist .= '<option value="' . $obj->rowid . '">' . $obj->ref . ' (' . dol_print_date($this->db->jdate ($obj->date_element), 'daytextshort') . ')-' . price($obj->total_ht) . $langs->getCurrencySymbol($conf->currency) . '</option>';
					$i ++;
				}
				$sellist .= '</select>';
			}
			return $sellist;
		}
		$this->db->free($resql);
	}

	/**
	 * Return a HTML area with the reference of object and a navigation bar for a business object
	 * To add a particular filter on select, you must set $object->next_prev_filter to SQL criteria.
	 *
	 * @param object $object
	 *        	show
	 * @param string $paramid
	 *        	of parameter to use to name the id into the URL link
	 * @param string $morehtml
	 *        	html content to output just before the nav bar
	 * @param int $shownav
	 *        	Condition (navigation is shown if value is 1)
	 * @param string $fieldid
	 *        	du champ en base a utiliser pour select next et previous
	 * @param string $fieldref
	 *        	du champ objet ref (object->ref) a utiliser pour select next et previous
	 * @param string $morehtmlref
	 *        	html supplementaire a afficher apres ref
	 * @param string $moreparam
	 *        	param to add in nav link url.
	 * @return tring Portion HTML avec ref + boutons nav
	 */
	function showrefnav($object, $paramid, $morehtml = '', $shownav = 1, $fieldid = 'rowid', $fieldref = 'ref', $morehtmlref = '', $moreparam = '')
	{
		global $langs, $conf;
		
		$ret = '';
		if (empty($fieldid))
			$fieldid = 'rowid';
		if (empty($fieldref))
			$fieldref = 'ref';
			
			// print "paramid=$paramid,morehtml=$morehtml,shownav=$shownav,$fieldid,$fieldref,$morehtmlref,$moreparam";
		$object->load_previous_next_ref_custom((isset($object->next_prev_filter) ? $object->next_prev_filter : ''), $fieldid);
		$previous_ref = $object->ref_previous ? '<a data-role="button" data-icon="arrow-l" data-iconpos="left" href="' . $_SERVER["PHP_SELF"] . '?' . $paramid . '=' . urlencode($object->ref_previous) . $moreparam . '">' . (empty($conf->dol_use_jmobile) ? img_picto($langs->trans("Previous"), 'previous.png') : '&nbsp;') . '</a>' : '';
		$next_ref = $object->ref_next ? '<a data-role="button" data-icon="arrow-r" data-iconpos="right" href="' . $_SERVER["PHP_SELF"] . '?' . $paramid . '=' . urlencode($object->ref_next) . $moreparam . '">' . (empty($conf->dol_use_jmobile) ? img_picto($langs->trans("Next"), 'next.png') : '&nbsp;') . '</a>' : '';
		
		// print "xx".$previous_ref."x".$next_ref;
		if ($previous_ref || $next_ref || $morehtml) {
			$ret .= '<table class="nobordernopadding" width="100%"><tr class="nobordernopadding"><td class="nobordernopadding">';
		}
		
		$ret .= $object->$fieldref;
		if ($morehtmlref) {
			$ret .= ' ' . $morehtmlref;
		}
		
		if ($morehtml) {
			$ret .= '</td><td class="nobordernopadding" align="right">' . $morehtml;
		}
		if ($shownav && ($previous_ref || $next_ref)) {
			$ret .= '</td><td class="nobordernopadding" align="center" width="20">' . $previous_ref . '</td>';
			$ret .= '<td class="nobordernopadding" align="center" width="20">' . $next_ref;
		}
		if ($previous_ref || $next_ref || $morehtml) {
			$ret .= '</td></tr></table>';
		}
		return $ret;
	}

	/**
	 * Return combo list of differents status
	 *
	 * @param string $selected
	 *        	value
	 * @param string $htmlname
	 *        	name of the component
	 * @param int $showempty
	 *        	row
	 * @return void
	 */
	function select_lead_status($selected = '', $htmlname = 'leadstatus', $showempty = 1)
	{
		require_once 'lead.class.php';
		$lead = new Lead($this->db);
		
		return $this->selectarray($htmlname, $lead->status, $selected, $showempty);
	}

	/**
	 * Return combo list of differents type
	 *
	 * @param string $selected
	 *        	value
	 * @param string $htmlname
	 *        	name of the component
	 * @param int $showempty
	 *        	row
	 * @return void
	 */
	function select_lead_type($selected = '', $htmlname = 'leadtype', $showempty = 1)
	{
		require_once 'lead.class.php';
		$lead = new Lead($this->db);
		
		return $this->selectarray($htmlname, $lead->type, $selected, $showempty);
	}
}