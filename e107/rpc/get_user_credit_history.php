<?
header ( 'Content-type: text/xml' );

$cpid = $_GET ["cpid"];

print "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>\n";
print "<user_credit_history>\n";
print "<title>User Credit History</title>\n";

global $dbhandle;
global $dbhost;
global $dblogin;
global $dbpassword;
global $dbname;

include ('../dbconnect.php');

function escape_xml_special_chars($text) {
	// first unescape them in the case where they may 
	// already be escaped - don't want to do it twice
	$text = str_replace ( "&lt;", "<", $text );
	$text = str_replace ( "&gt;", ">", $text );
	$text = str_replace ( "&apos;", "'", $text );
	$text = str_replace ( "&amp;", "&", $text );
	
	// then escape them
	$text = str_replace ( "&", "&amp;", $text );
	$text = str_replace ( "<", "&lt;", $text );
	$text = str_replace ( ">", "&gt;", $text );
	$text = str_replace ( "'", "&apos;", $text );
	return $text;
}

$dbhandle = mysqli_connect ( $dbhost, $dblogin, $dbpassword );

if ($dbhandle) {
	mysqli_select_db ($dbhandle, $dbname );
	// get the pointer to the day
	$query = "select value from currentday where key_item='history90'";
	$res = mysqli_query ( $dbhandle, $query );
	if (! $res) {
		exit ();
	}
	
	$startday = 0;
	while ( ($row = mysqli_fetch_array ( $res )) ) {
		$startday = $row ["value"];
	}
	if (! $startday)
		$startday = 1;
	mysqli_free_result ( $res );
	
	// get the user info
	$query = "select a.user_name, a.project_count, a.active_project_count,a.active_computer_count, a.total_credit, a.rac, a.rac_time from cpid a where a.user_cpid='" . $cpid . "'";
	$res_user = mysqli_query ( $dbhandle, $query );
	if (! $res_user) {
		echo "<b>Error performing query: " . mysqli_error ($dbhandle) . "</b>";
		exit ();
	}
	while ( ($row_user = mysqli_fetch_array ( $res_user )) ) {
		$project_username = $row_user ["user_name"];
		$project_usertc = $row_user ["total_credit"];
		$project_userrac = $row_user ["rac"];
		$project_userractime = $row_user ["rac_time"];
		$project_projectcount = $row_user ["project_count"];
		$project_activeprojectcount = $row_user ["active_project_count"];
		$project_activecomputercount = $row_user ["active_computer_count"];
	}
	
	print "<cpid>$cpid</cpid>";
	print "<project_count>$project_projectcount</project_count>\n";
	print "<active_project_count>$project_projectcount</active_project_count>\n";
	print "<total_credit>$project_usertc</total_credit>\n";
	print "<expavg_credit>$project_userrac</expavg_credit>\n";
	print "<expavg_time>$project_userractime</expavg_time>\n";
	//print "<active_computer_count>$project_activecomputercount</active_computer_count>\n";
	print "<total_credit_history_last_91_days>\n";
	
	// Now get the list of projects they are participating in
	$query = "select a.project_id, b.shortname, a.user_id from cpid_map a, projects b where a.project_id=b.project_id and a.user_cpid='" . $cpid . "'";
	$res = mysqli_query ( $dbhandle, $query );
	if (! $res) {
		echo "<b>Error performing query: " . mysqli_error ( $dbhandle ) . "</b>";
		exit ();
	}
	
	for($count = 1; $count <= 91; $count ++)
		$totals [$count] = 0;
	
	while ( ($row = mysqli_fetch_array ( $res )) ) {
		$project_id = $row ["project_id"];
		$project_shortname = $row ["shortname"];
		$project_userid = $row ["user_id"];
		
		// Fetch project history numbers
		$query = "select * from history_user_tc_$project_shortname a where user_id=$project_userid";
		$res_data = mysqli_query ( $dbhandle, $query );
		if (! $res_data) {
			exit ();
		}
		
		while ( ($row = mysqli_fetch_array ( $res_data )) ) {
			for($count = 1; $count <= 91; $count ++) {
				$rstring = "d_$count";
				$totals [$count] = $totals [$count] + $row [$rstring];
			}
		}
	}
	mysqli_free_result ( $res );
	
	// spit out results
	$startday += 1;
	if ($startday > 91)
		$startday = 1;
	
	for($count = 1; $count < 91; $count ++) {
		
		print "      <day_$count>$totals[$startday]</day_$count>\n";
		$startday ++;
		if ($startday > 91)
			$startday = 1;
	}
	print "      <day_91>$project_usertc</day_91>\n";
	print "   </total_credit_history_last_91_days>\n";

} else {
	print "   <database_error/>\n";
}

print "</user_credit_history>\n";
?>
