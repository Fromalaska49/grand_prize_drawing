<?php
ini_set('MAX_EXECUTION_TIME', -1);
require('session_handler.php');
require('connect.php');
mysql_set_charset('UTF8');
/*
print_r($_FILES);
echo('<br />');
var_dump($_FILES);
echo('<br />');
*/
$table_name='';
if(isset($_POST['table_name'])&&$_POST['table_name']!=''){
	$table_name=$_POST['table_name'];
}
else{
	$table_name=basename($_FILES['filename']['name'],'.csv');
}
//echo('table_name='.$table_name.'<br />');
$sanitized_table_name=mysql_real_escape_string($table_name);
$num_tables_result=mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'mainall' AND table_name = '$sanitized_table_name'"));
if($num_tables_result['COUNT(*)']>0){
	//duplicate table name
	$duplicate_table_number=1;
	do{
		$duplicate_table_number++;
		$sanitized_table_name=mysql_real_escape_string($table_name.' '.$duplicate_table_number);
		$num_tables_result=mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'mainall' AND table_name = '$sanitized_table_name'"));
		$num_tables=$num_tables_result['COUNT(*)'];
	}while(!($num_tables==0));
	$table_name=$table_name.' '.$duplicate_table_number;
}
//echo('table_name='.$table_name.'<br />');
/*
if($num_tables_result['COUNT(*)']>0){
	//duplicate table name
	$duplicate_table_number=0;
	do{
		$duplicate_table_number++;
		$sanitized_table_name=mysql_real_escape_string($table_name.' '.$duplicate_table_number);
		$num_tables_result=mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'mainall' AND table_name = '$sanitized_table_name'"));
		$num_tables=$num_tables_result['COUNT(*)'];
	}while(!($num_tables==0));
	$table_name=$table_name.' '.$duplicate_table_number;
}
*/

//Upload File
if (is_uploaded_file($_FILES['filename']['tmp_name'])) {
	//echo("<h1>File <i>". $_FILES['filename']['name'] ."</i> uploaded successfully.</h1>");
	//echo "<h2>Displaying contents:</h2>";
	//readfile($_FILES['filename']['tmp_name']);
}
//Import uploaded file to Database
$handle = fopen($_FILES['filename']['tmp_name'], 'r');
$data = fgetcsv($handle, 0, ',');
$num_fields=count($data);
$field_max_len=array();
/**/
for($i=0;$i<$num_fields;$i++){
	$field_max_len[$i]=1;
	$handle = fopen($_FILES['filename']['tmp_name'], 'r');
	while ($data = fgetcsv($handle, 0, ',')) {
		if(strlen($data[$i])>$field_max_len[$i]){
			$field_max_len[$i]=strlen($data[$i]);
		}
	}
	unset($handle);
}
/**/

for($i=0;$i<$num_fields;$i++){
	$field_max_len[$i]=1;
}
$handle = fopen($_FILES['filename']['tmp_name'], 'r');
while ($data = fgetcsv($handle, 0, ',')) {
	for($i=0;$i<$num_fields;$i++){
		if(strlen($data[$i])>$field_max_len[$i]){
			$field_max_len[$i]=strlen($data[$i]);
		}
	}
}
unset($handle);
unset($data);

$sql="CREATE TABLE `".$sanitized_table_name."` (";
$sql.=" `id` int (8) NOT NULL AUTO_INCREMENT";
for($i=0;$i<$num_fields;$i++){
	$sql.=", `Field_".($i+1)."` varchar (".$field_max_len[$i].")";
}
$sql.=", PRIMARY KEY (id))";
mysql_query($sql);

sleep(1);
$handle = fopen($_FILES['filename']['tmp_name'], 'r');
$record_num = 1;
$import = "";
while($data = fgetcsv($handle, 0, ',')){
	if (!mysql_ping ($conn)) {
	   //here is the major trick, you have to close the connection (even though its not currently working) for it to recreate properly.
	   mysql_close($conn);
	   $conn = mysql_connect('localhost','mainall','Tuxedo1991ain\'tthepassword');
	   mysql_select_db('mainall',$conn);
	}
	if($record_num>1){
		$import.=", ('$record_num','";
		$import.=$data[0];
		for($i=1;$i<$num_fields;$i++){
			$import.="','".mysql_real_escape_string($data[$i]);
		}
		$import.="')";
	}
	else{
		$import="INSERT INTO `".$sanitized_table_name."` VALUES ('1','";
		$import.=$data[0];
		for($i=1;$i<$num_fields;$i++){
			$import.="','".mysql_real_escape_string($data[$i]);
		}
		$import.="')";
	}
	$record_num++;
}
if(mysql_query($import)){
	unset($import);
}
else{
	echo($import.'<br />');
	die(mysql_error());
}
fclose($handle);

mysql_query("INSERT INTO `tables` (`name`) VALUES ('$sanitized_table_name')");
$table_id_row=mysql_fetch_array(mysql_query("SELECT `id` FROM `tables` WHERE `name`='$sanitized_table_name'"));
$sanitized_table_id=(int)$table_id_row['id'];
$sanitized_uid=(int)$_SESSION['uid'];
mysql_query("INSERT INTO `table_permissions` (`uid`,`table_id`,`read_access`,`write_access`,`admin_access`) VALUES ('$sanitized_uid','$sanitized_table_id','1','1','1')");
unset($table_id_row);
unset($sanitized_uid);


$sanitized_read_access = 0;
$sanitized_write_access = 0;
$sanitized_admin_access = 0;
if(isset($_POST['read_access'])){
	$sanitized_read_access = 1;
}
if(isset($_POST['write_access'])){
	$sanitized_write_access = 1;
}
if(isset($_POST['admin_access'])){
	$sanitized_admin_access = 1;
}
$i=0;
$sql = 'INSERT INTO `table_permissions` (`uid`,`table_id`,`read_access`,`write_access`,`admin_access`) VALUES';
$result = mysql_query("SELECT `uid` FROM `users` WHERE `uid` != '$sanitized_uid'");
while($row = mysql_fetch_array($result)){
	$sanitized_uid = (int) $row['uid'];
	if($i>0){
		$sql.=', (\''.$sanitized_uid.'\',\''.$sanitized_table_id.'\',\''.$sanitized_read_access.'\',\''.$sanitized_write_access.'\',\''.$sanitized_admin_access.'\')';
	}
	else{
		$sql.=' (\''.$sanitized_uid.'\',\''.$sanitized_table_id.'\',\''.$sanitized_read_access.'\',\''.$sanitized_write_access.'\',\''.$sanitized_admin_access.'\')';
	}
	$i++;
}
mysql_query($sql);
unset($result);
unset($row);
unset($sql);

header('Location: view_table.php?table_name='.rawurlencode($table_name));
?>