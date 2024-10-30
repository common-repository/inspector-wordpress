<?php
/*
Plugin Name: InspectorWordpress
Plugin URI: http://schipplock.de
Description: Monitors hack attempts
Author: Andreas Schipplock
Version: 1.3
Author URI: http://schipplock.de
*/
/*  Copyright 2008  Andreas Schipplock  (email : andreas@schipplock.de)
**
**  This program is free software; you can redistribute it and/or modify
**  it under the terms of the GNU General Public License Version 2, June 1991 as published by
**  the Free Software Foundation
**
**  This program is distributed in the hope that it will be useful,
**  but WITHOUT ANY WARRANTY; without even the implied warranty of
**  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
**  GNU General Public License for more details.
**
**  You should have received a copy of the GNU General Public License
**  along with this program; if not, write to the Free Software
**  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
require_once(dirname(__FILE__).'/../../../wp-config.php');
require_once(dirname(__FILE__).'/../../../wp-admin/upgrade-functions.php');
require_once(dirname(__FILE__).'/geoip.inc');

$iwp_db_version = "1.3";

class InspectorWordpress {
	function InspectorWordpress() {
		if (isset($this)) {
			
			if (get_option("InspectorWordpressV1_hackAttempts")=="") {
				add_option("InspectorWordpressV1_hackAttempts", "0", "hack attempt counter", "no");
			}
			
			if (get_option("InspectorWordpressV1_showFooter")=="") {
				add_option("InspectorWordpressV1_showFooter", "true", "footer", "no");
			}
			
			if (get_option("InspectorWordpressV1_showVisualWarning")=="") {
				add_option("InspectorWordpressV1_showVisualWarning", "true", "warning message", "no");
			}
			
			add_action('init', array(&$this, 'doMonitor'));
			
			if (get_option("InspectorWordpressV1_showFooter")=="true") {
				add_action('wp_footer', array(&$this, 'doPrintStats'));
			}
			add_action('admin_menu', array(&$this, 'doAdminPages'));
			add_action('activate_inspector-wordpress/InspectorWordpress.php', array(&$this, 'iwp_install'));
		}
	}
	
	function iwp_install() {
		global $wpdb;
		global $iwp_db_version;

		$table_name = $wpdb->prefix . "iwp_attempts";

		if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
			$sql = "CREATE TABLE ".$table_name." (
						id BIGINT NOT NULL AUTO_INCREMENT,
						datetime VARCHAR( 50 ) NOT NULL,
						ip VARCHAR( 100 ) NOT NULL,
						country VARCHAR( 100 ) NOT NULL,
						querystring TEXT NOT NULL,
						referer VARCHAR( 255 ) NOT NULL,
						useragent VARCHAR( 255 ) NOT NULL,
						UNIQUE KEY id (id)
					);";
			dbDelta($sql);
			
			//adding initial test data
			$datetime = $wpdb->escape("24.06.2007 / 12:28 am (UTC)");
			$ip = $wpdb->escape("127.0.0.1");
			$country = $wpdb->escape("Argentina (AR)");
			$querystring = $wpdb->escape("page=cp(");
			$referer = $wpdb->escape("some referer ;)");
			$useragent = $wpdb->escape("Mozilla gecko bla");

			$insert = "INSERT INTO " . $table_name .
			            " (datetime,ip,country,querystring,referer,useragent) " .
			            "VALUES ('" . $datetime . "','" . $ip . "','" . $country . "','". $querystring ."','". $referer ."','". $useragent ."')";

			$results = $wpdb->query( $insert );
			if (get_option("iwp_db_version")=="") {
				add_option("iwp_db_version", $iwp_db_version);
			} else {
				update_option("iwp_db_version", $iwp_db_version);
			}
		}
	}
	
	function doMonitor() {
		$querystring = $_SERVER['QUERY_STRING'];
		$conditions = file(dirname(__FILE__)."/conditions.txt");
		
		$myQueryString = str_replace('?', '&', $querystring);
		$queryElements = explode("&", $myQueryString);
		
		$conditionLength = count($conditions);
		$queryElementsLength = count($queryElements);
		
		$run = 0;
		
		for ($run=0;$run<$queryElementsLength;$run++) {
			$queryElementSplit = split("=", $queryElements[$run]);
			$elementId = $queryElementSplit[0];
			$elementValue = $queryElementSplit[1];
		
			$irun = 0;
			for ($irun=0;$irun<$conditionLength;$irun++) {
				$amountOfSameChars = similar_text($elementId, $conditions[$irun], $percent);
				if ($percent > 80) { $this->doYell("80"); }
				if ($_GET["debug"]=="true") {
					print "$elementId <-> ".rtrim($conditions[$irun])." : $percent\n"; 
				}
				
				$amountOfSameChars = similar_text($elementValue, $conditions[$irun], $percent);
				if ($percent > 80) { $this->doYell("80"); }
				if ($_GET["debug"]=="true") {
					print "$elementValue <-> ".rtrim($conditions[$irun])." : $percent\n"; 
				}
				
				$amountOfSameChars = similar_text($elementId."=".$elementValue, $conditions[$irun], $percent);
				if ($percent > 80) { $this->doYell("80"); }
				if ($_GET["debug"]=="true") {
					print "$elementId=$elementValue <-> ".rtrim($conditions[$irun])." : $percent\n"; 
				}
				
				$amountOfSameChars = similar_text($elementId."=", $conditions[$irun], $percent);
				if ($percent > 80) { $this->doYell("80"); }
				if ($_GET["debug"]=="true") {
					print "$elementId= <-> ".rtrim($conditions[$irun])." : $percent\n"; 
				}
			}
		}
	}	

	function doPrintStats() {
		$hackAttempts = get_option("InspectorWordpressV1_hackAttempts");
		print "<a href=\"http://schipplock.de\">InspectorWordpress</a> has prevented $hackAttempts attacks.";
	}
	
	function doYell($percentage) {
		global $wpdb;
		$table_name = $wpdb->prefix . "iwp_attempts";
		
		$gi = geoip_open(dirname(__FILE__)."/GeoIP.dat",GEOIP_STANDARD);
		$ip = $wpdb->escape($_SERVER['REMOTE_ADDR']);
		$referer = $wpdb->escape($_SERVER['HTTP_REFERER']);
		$useragent = $wpdb->escape($_SERVER['HTTP_USER_AGENT']);
		$countrycode = $wpdb->escape(geoip_country_code_by_addr($gi, $ip));
		$countryname = $wpdb->escape(geoip_country_name_by_addr($gi, $ip));
		$datetime = $wpdb->escape(date("d.m.Y / g:i a (e)"));
		$querystring = $wpdb->escape($_SERVER['QUERY_STRING']);
		geoip_close($gi);
		$country = "$countryname ($countrycode)";

		$insert = "INSERT INTO " . $table_name .
			      " (datetime,ip,country,querystring,referer,useragent) " .
			      "VALUES ('" . $datetime . "','" . $ip . "','" . $country . "','". $querystring ."','". $referer ."','". $useragent ."')";

		$results = $wpdb->query( $insert );
		
		if (get_option("InspectorWordpressV1_showVisualWarning")=="true") {
			print "Inspector Wordpress has detected the current request as dangerous (error-code:$percentage).";
		}
		
		$hackAttempts = get_option("InspectorWordpressV1_hackAttempts");
		$hackAttempts++;
		update_option("InspectorWordpressV1_hackAttempts", $hackAttempts);
		die();
	}
	
	function doAdminPages() {
	    add_submenu_page('index.php', 'Inspector Wordpress', 'Inspector Wordpress', 8, 'inspector-wordpress', array(&$this, 'doAttackOverview'));
		add_options_page('Inspector Wordpress', 'Inspector Wordpress', 8, 'inspector-wordpress-options', array(&$this,'doAdminOptions'));
		add_management_page('Logs', 'Logs', 8, 'iwplogs', array(&$this, 'doManageHackLogs'));
	}
	
	function doAttackOverview() {
		global $wpdb;
		$table_name = $wpdb->prefix . "iwp_attempts";
		
		print '<div class="wrap">';
		print '<h2>All logged hack attempts</h2>';
		
		print '
			<table border="0" width="100%" cellspacing="5" cellpadding="0">
			<tr>
				<td style="background-color:#f0f0f0">Date/Time</td>
				<td style="background-color:#f0f0f0">IP</td>
				<td style="background-color:#f0f0f0">Country</td>
				<td style="background-color:#f0f0f0">Querystring</td>
			</tr>
		';
		
		$sql = "select datetime,ip,country,querystring from ".$table_name." order by id desc;";
		$res = mysql_query($sql) or die("dberr");
		
		while(list($datetime,$ip,$country,$querystring)=mysql_fetch_array($res)) {
			print '<tr>';
			print '<td>'.$datetime.'</td>';
			print '<td>'.$ip.'</td>';
			print '<td>'.$country.'</td>';
			print '<td>'.$querystring.'</td>';
			print '</tr>';
		}
		print '</table></div>';
	}
	
	function doAdminOptions() {
		$conditions = implode("",file(dirname(__FILE__)."/conditions.txt"));
		
		if ($_POST["doSave"]=="true") {
			if ($_POST["showFooter"] == "yes") {
				update_option("InspectorWordpressV1_showFooter", "true");
			} else {
				update_option("InspectorWordpressV1_showFooter", "false");
			}
			if ($_POST["showWarning"] == "yes") {
				update_option("InspectorWordpressV1_showVisualWarning", "true");
			} else {
				update_option("InspectorWordpressV1_showVisualWarning", "false");
			}
			$newConditions = $_POST["conditions"];
			file_put_contents(dirname(__FILE__)."/conditions.txt", $newConditions);
		}
		
		print '<div class="wrap">';
		print '<h2>Inspector Wordpress Options</h2>';
		print '<form name="iwpOptions" method="post">';
		print '<input type="hidden" name="doSave" value="true" />';
		if (get_option("InspectorWordpressV1_showFooter")=="true") {
			print '<input type="checkbox" name="showFooter" value="yes" checked="checked" /> Show footer info of how many times Inspector Wordpress has prevented an attack<br />';
		} else {
			print '<input type="checkbox" name="showFooter" value="yes" /> Show footer info of how many times Inspector Wordpress has prevented an attack<br />';
		}
		if (get_option("InspectorWordpressV1_showVisualWarning")=="true") {
			print '<input type="checkbox" name="showWarning" value="yes" checked="checked" /> Show an info to the attacker that Inspector Wordpress has prevented their attack<br />';
		} else {
			print '<input type="checkbox" name="showWarning" value="yes" /> Show an info to the attacker that Inspector Wordpress has prevented their attack<br />';
		}
		print '<h3>Conditions</h3>';
		print 'Here you can define conditions on when Inspector Wordpress should prevent an attack:<br />';
		print '<textarea style="width:100%;height:400px" name="conditions">'.$conditions.'</textarea><br /><br />';
		print '<input type="submit" value="Update Options" />';
		print '</form>';
		print '</div>';
	}
	
	function doManageHackLogs() {
		print '<div class="wrap">';
		print '<h2>Inspector Wordpress Attack Logs</h2>';
		print 'Here you can delete available log entries.<hr />';
		global $wpdb;
		$table_name = $wpdb->prefix . "iwp_attempts";
		
		if ($_GET["deleteEntry"] != "") {
			if (is_numeric($_GET["deleteEntry"])==true) {
				$sql = "delete from ".$table_name." where id = ".$_GET["deleteEntry"].";";
				$res = mysql_query($sql) or die("dberr");
			}
		}
		
		print '
			<table border="0" width="100%" cellspacing="5" cellpadding="0">
			<tr>
				<td style="background-color:#f0f0f0">Date/Time</td>
				<td style="background-color:#f0f0f0">IP</td>
				<td style="background-color:#f0f0f0">Country</td>
				<td style="background-color:#f0f0f0">Querystring</td>
				<td style="background-color:#f0f0f0">Action</td>
			</tr>
		';
		
		$sql = "select id,datetime,ip,country,querystring from ".$table_name." order by id desc;";
		$res = mysql_query($sql) or die("dberr");
		
		while(list($id,$datetime,$ip,$country,$querystring)=mysql_fetch_array($res)) {
			print '<tr>';
			print '<td>'.$datetime.'</td>';
			print '<td>'.$ip.'</td>';
			print '<td>'.$country.'</td>';
			print '<td>'.$querystring.'</td>';
			print '<td><a href="edit.php?page=iwplogs&deleteEntry='.$id.'">delete</a></td>';
			print '</tr>';
		}
		print '</table>';
		print '</div>';
	}
}

$getTheBan = new InspectorWordpress();

?>
