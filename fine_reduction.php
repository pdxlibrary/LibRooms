<?php

session_start();

require_once("config/config.inc.php");
require_once("includes/Database.php");
require_once("load.php");

require_once("includes/verify_access.php");
restrict_access($db,array("admin"));

$fine_id = $_GET['fine_id'];
$fines = load_fines(array('id'=>$fine_id));

if(count($fines) == 0)
{
	display_error("Fine could not be found.",array("fine_id"=>$fine_id));
	exit();
}
else if(count($fines) > 1)
{
	display_error("Multiple fines with the same id found.",$fines);
	exit();
}
else
{
	foreach($fines as $fine)
		break;
	
	if(isset($_GET['submitted']))
	{
		if(isset($_GET['reduction_amount']) && strcmp(trim($_GET['reduction_amount']),''))
		{
			$reduction_amount = $_GET['reduction_amount'];
			$reduction_reason = $_GET['reduction_reason'];
			
			$table = "fines_reductions";
			$fields = array('fine_id','amount','description','date_added','added_by');
			$values = array($fine->id,$reduction_amount,$reduction_reason,date('Y-m-d H:i:s'),$_SESSION['LibRooms']['UserID']);
			//pr($values);
			$insert_id = $db->insert($table,$fields,$values);
		}
		
		// reload fine
		$fines = load_fines(array('id'=>$fine_id));
		foreach($fines as $fine)
			break;
	}
		
	print("<h3>Fine</h3>\n");
	print("Amount: ".format2dollars($fine->amount)."<br>\n");
	print("Description: $fine->description<br>\n");
	if(is_array($fine->reductions))
	{
		print("<b>Reductions:</b><br>\n");
		foreach($fine->reductions as $reduction)
		{
			print("<div style='color:red'>".date('m/d/Y',strtotime($reduction->date_added))."&nbsp; -".format2dollars($reduction->amount)." $reduction->description</div>\n");
		}
	}
}







if(isset($_GET['remove_reduction']))
{
	$remove_reduction = $_GET['remove_reduction'];
	$db->update('fines_reductions','active','0',"id like '$remove_reduction'");
	
	// reload fine
	$fines = load_fines(array('id'=>$fine_id));
	
	// TODO: actually check the result to make sure the db change worked
	print("<font color='green'>Reduction successfully removed.</font><br>\n");
}

print("<hr>\n");

print("<form>\n");
print("<input type='hidden' name='submitted' value='1'>\n");
print("<input type='hidden' name='fine_id' value='$fine_id'>\n");
print("<h2>Reduce Fine</h2>\n");
print("Reduce Fine Amount By: <input type='text' size='10' name='reduction_amount'><br>\n");
print("Reason: <input type='text' size='60' name='reduction_reason'><br>\n");
print("<input type='submit' value='Reduce Fine'>\n");

print("</form>\n");

?>