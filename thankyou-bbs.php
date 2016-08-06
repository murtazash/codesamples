<?php
	session_start();
	include("lib/opencon-mysqli.php");
	include("lib/variables.php");
	include("lib/functions.php");
	include("lib/combos.php");

	$CustomerID = $_SESSION['CustomerID'];
	$Error = "";
/*================== BBS Section ===================================================================================================*/
	
require_once("payment-bbs/Parameters.php");
require_once("payment-bbs/classes/ClassQueryRequest.php");
require_once("payment-bbs/classes/ClassProcessRequest.php");

$Amount = 0;
$transactionId = $responseCode = $webshopParameter = "";
if (isset($_GET['transactionId']))
  $transactionId = $_GET['transactionId'];

if (isset($_GET['responseCode']))
  $responseCode = $_GET['responseCode'];

if (isset($_GET['OrderID']))
  $OrderID = $_GET['OrderID'];

if (isset($_GET['CustomerID']))
  $CustomerID = $_GET['CustomerID'];

if (isset($_GET['Amount']))
  $Amount = $_GET['Amount'];


if ($responseCode == "OK")
{
	$description = "description of AUTH operation";
	$operation = "AUTH";
	$transactionAmount = $Amount;
	$transactionReconRef = "";
	
	####  PROCESS OBJECT  ####
	$ProcessRequest = new ProcessRequest(
		$description,
		$operation,
		$transactionAmount,
		$transactionId,
		$transactionReconRef
	);
	
	
	$InputParametersOfProcess = array
	(
		"token"       => $token,
		"merchantId"  => $merchantId,
		"request"     => $ProcessRequest 
	);
	
	try 
	{
		if (strpos($_SERVER["HTTP_HOST"], 'uapp') > 0)
		{// Creating new client having proxy
			$client = new SoapClient($wsdl, array('proxy_host' => "isa4", 'proxy_port' => 8080, 'trace' => true,'exceptions' => true));
		}
		else
		{// Creating new client without proxy
			$client = new SoapClient($wsdl, array('trace' => true,'exceptions' => true ));
		}
		
		$OutputParametersOfProcess = $client->__call('Process' , array("parameters"=>$InputParametersOfProcess));
		
		$ProcessResult = $OutputParametersOfProcess->ProcessResult; 
		
		/*
		echo "<h3><font color='gray'>Output parameters:</font></h3>";
		echo "<pre>"; 
		print_r($ProcessResult);
		echo "</pre>";
		*/
		
		$process_parameters = "?transactionId=" .  $ProcessResult->TransactionId;	
		if ($ProcessResult->ResponseCode == "OK")
		{

			$CustomerEmail = "";
			if($CustomerID == "" || $CustomerID <= 0)
				$CustomerID = GetValuei("customers_id","orders","orders_id = $OrderID",$db);
			$CustomerEmail = GetValuei("customers_email_address","customers","customers_id = $CustomerID",$db);
				
			$Qry = "UPDATE orders SET paid = 1 WHERE orders_id = $OrderID";
			$db->query($Qry);
			
			$Qry = "UPDATE giftcard_transactions SET used = 1 WHERE customerid = $CustomerID AND orderid = $OrderID";
			$db->query($Qry);

			$Body = "<table width=\"750\" bgcolor=\"#FFFFFF\" style=\"border:1px solid #666666;\">
								 <tr><td colspan=\"7\" style=\"height:90px;\"><img src=\"".strOrderEmailHeader."\"></td></tr>									
								 <tr><td colspan=\"7\" style=\"height:40px; font-size:16px; color:#990000; font-weight:bold; text-align:center;\"><u>".strCompany." : Ordre nr. $OrderID</u></td></tr>
								 <tr><td colspan=\"7\" style=\"height:10px;\"></td></tr>
								 <tr style=\"background-color:#cccccc;\">
									<td style=\"width:50px;  text-align:center;\">Sr#</td>
									<td style=\"width:80px;  text-align:center;\">Varenr.</td>
									<td style=\"width:290px;\">&nbsp;Produkt(er)</td>
									<td style=\"width:60px;  text-align:center;\">Antall</td>
									<td style=\"width:90px; text-align:center;\">Pris</td>
									<td style=\"width:90px; text-align:center;\">Rabatt</td>
									<td style=\"width:90px; text-align:center;\">Netto</td>									
								</tr>";
			
			$QryEmail = "SELECT P.products_id, P.products_price, P.products_oldprice, PD.products_name, PD.suppliercode, CB.quantity,  PC.color, PS.size
				FROM products_description PD
				INNER JOIN products P ON PD.products_id = P.products_id 
				INNER JOIN customers_basket CB ON PD.products_id = CB.products_id
				LEFT OUTER JOIN pcolor PC ON CB.colorid = PC.colorid
				LEFT OUTER JOIN psize PS ON CB.sizeid = PS.sizeid
				WHERE CB.customers_id = $CustomerID";
			$Rst = $db->query($QryEmail);
			$Count = $Total = $TotalQty = 0;
			while($objEmail = $Rst->fetch_object())
			{
				$Count++;
				$Discount = 0;
				$OldPrice = $objEmail->products_price;
				if($objEmail->products_oldprice > 0 && $objEmail->products_oldprice > $objEmail->products_price)
				{
					$OldPrice = $objEmail->products_oldprice;
					$Discount = $objEmail->products_oldprice - $objEmail->products_price;
				}
				$NetPrice = $objEmail->products_price * $objEmail->quantity;
				$Total += $objEmail->products_price * $objEmail->quantity;
				$TotalQty += $objEmail->quantity;
				$ColorSize = "";
				if($objEmail->color != "" || $objEmail->size != "")
				{
					$ColorSize .= " (";
					if($objEmail->color != "")
						$ColorSize .= $objEmail->color." - ";
					if($objEmail->size != "")
						$ColorSize .= $objEmail->size;
					$ColorSize .= ")";
				}
				
				$Qry = "UPDATE products SET products_quantity = products_quantity - ".$objEmail->quantity." WHERE products_id = ".$objEmail->products_id;
				$db->query($Qry);
				
				$Body .= "<tr style=\"background-color:#f0f0f0; font-size:14px;\">
										<td style=\"text-align:center;\">$Count</td>
										<td style=\"text-align:center;\">".$objEmail->suppliercode."</td>
										<td>&nbsp;".$objEmail->products_name.$ColorSize."</td>
										<td style=\"text-align:center;\">".$objEmail->quantity."</td>
										<td style=\"text-align:right;\">".$OldPrice."</td>
										<td style=\"text-align:right;\">".sprintf("%0.2f",$Discount)."</td>
										<td style=\"text-align:right;\">".sprintf("%0.2f",$NetPrice)."&nbsp;</td>
									</tr>";
			}

			$Qry = "SELECT * FROM offers WHERE quantity <= $TotalQty ORDER BY quantity DESC";
			$Rst4 = $db->query($Qry);
			if($Rst4->num_rows > 0)
			{							
				$obj4 = $Rst4->fetch_object();
				$Discount2 = 0;
				if($obj4->discount > 0)
					$Discount2 = $obj4->discount;
				else if($obj4->percentage > 0)
					$Discount2 = round(($Total * $obj4->percentage)/100,2);
				$Body .=	"<tr style=\"background-color:#cccccc; font-size:14px;\"><td colspan=\"6\" style=\"width:640px; text-align:right;\">Nettosalg : </td><td style=\"width:110px; text-align:right;\">Kr. ".sprintf("%0.2f",$Total)."&nbsp;</td></tr>";
				$Body .=	"<tr style=\"background-color:#cccccc; font-size:14px;\"><td colspan=\"6\" style=\"width:640px; text-align:right;\">Andre rabatt (".$obj4->discount>0?"Kr. ".$obj4->discount:$obj4->percentage." %) : </td><td style=\"width:110px; text-align:right;\">Kr. -".sprintf("%0.2f",$Discount2)."&nbsp;</td></tr>";
				$Total -= $Discount2;
			}
			$Qry = "SELECT * FROM orders WHERE orders_id = $OrderID";
			$obj5 = mysqli_fetch_object($db->query($Qry));
			if($obj5->shipping > 0)
			{
				$Body .=	"<tr style=\"background-color:#cccccc; font-size:14px;\"><td colspan=\"6\" style=\"width:640px; text-align:right;\">Porto og ekspedisjonsgebyr : </td><td style=\"width:110px; text-align:right;\">Kr. +".$obj5->shipping."&nbsp;</td></tr>";
				$Total += $obj5->shipping;
			}
									 
			$Body .=	"<tr style=\"background-color:#cccccc; font-size:14px;\"><td colspan=\"6\" style=\"width:640px; text-align:right;\">MVA (25%) : </td><td style=\"width:110px; text-align:right;\">Kr. ".sprintf("%0.2f",($Total*20)/100)."&nbsp;</td></tr>";
			//$Total += sprintf("%0.2f",($Total*25)/100);
			$Body .=	"<tr style=\"background-color:#cccccc; font-size:16px;\"><td colspan=\"6\" style=\"width:640px; text-align:right;\">Totalt : </td><td style=\"width:110px; text-align:right;\">Kr. ".sprintf("%0.2f",$Total)."&nbsp;</td></tr>";

			$Qry = "SELECT * FROM giftcard_transactions WHERE customerid = ".$CustomerID." AND orderid = ".$OrderID." ORDER BY cardid";
			$Rst = $db->query($Qry);
			if($Rst->num_rows > 0)
			{
				while($obj6 = $Rst->fetch_object())
				{
					$Body .=	"<tr style=\"background-color:#cccccc; font-size:16px;\"><td colspan=\"6\" style=\"width:640px; text-align:right;\">Gavekort : </td><td style=\"width:110px; text-align:right;\">Kr. ".sprintf("%0.2f",$obj6->transactionamount)."&nbsp;</td></tr>";
					$Total -= $obj6->transactionamount;
				}
				$Body .=	"<tr style=\"background-color:#cccccc; font-size:16px;\"><td colspan=\"6\" style=\"width:640px; text-align:right;\">Totalt : </td><td style=\"width:110px; text-align:right;\">Kr. ".sprintf("%0.2f",$Total)."&nbsp;</td></tr>";
			}

			$Body .=	"<tr><td colspan=\"7\" style=\"height:20px;\"></td></tr>
								 <tr><td colspan=\"7\" style=\"width:750px;\">Tusen takk for at du handler hos ".strCompany."</td></tr>
								 <tr><td colspan=\"7\" style=\"width:750px;\">Leveringstiden på varene Deres er 5-14 arbeidsdager.</td></tr>
								 <tr><td colspan=\"7\" style=\"width:750px;\">Vennligst husk Deres ordrenummer for enhver forespørsel.</td></tr>
								 <tr><td colspan=\"7\" style=\"height:10px;\"></td></tr>
							 </table>";
			//sendMail(strCompanyEmailStart."@".strCompanyShortURL,"murtaza.sh@hotmail.com",strCompany." - Ordre nr. $OrderID",$Body,strCompany,0);
			sendMail(strCompanyEmailStart."@".strCompanyShortURL,strCompanyEmailStart."@".strCompanyShortURL,strCompany." Order # $OrderID",$Body,strCompany,0);			
			sendMail(strCompanyEmailStart."@".strCompanyShortURL,$CustomerEmail,strCompany." Order # $OrderID",$Body,strCompany,0);

			$Qry = "DELETE FROM customers_basket WHERE customers_id = $CustomerID";
			$db->query($Qry);

		}
		else
		{
			$Error  = "Online payment failed : Process Authuntication Code 111";
			QueryRequest($transactionId);
			sendMail(strCompanyEmailStart."@".strCompanyShortURL,"murtaza.sh@hotmail.com","Online payment failed : Process Authuntication Code 111",$Error,strCompany,0);
		}
			
	} // End try
	catch (SoapFault $fault) 
	{
		// Do some error handling in here...
		/*
		echo "<br/><font color='red'>EXCEPTION!";   
		echo "<br/><br/><h3><font color='red'>Process call failed</font></h3>";
		echo "<pre>"; 
		print_r($fault);
		echo "</pre>";
		*/
		$Insufficient = "";
		if ($fault->detail->BBSException->Result->ResponseCode == '99')
			$Insufficient = $fault->detail->BBSException->Result->ResponseText." due to insufficient funds";
		else
			$Insufficient = $fault->detail->BBSException->Result->ResponseText." (".$fault->detail->BBSException->Result->ResponseCode.")";
		$Error  = "<h1>Insufficient Funds</h1><h2><font color='red'>$Insufficient</font></h2>";
		sendMail(strCompanyEmailStart."@".strCompanyShortURL,"murtaza.sh@hotmail.com","Insufficient Funds",$Error,strCompany,0);		
		//$Error .= "<span>".print_r($fault)."</span>";
	} // End catch
	####  END   QUERY CALL  ####
}
else
{
	//QueryRequest($transactionId);
	## Do some error handling in here...
	$Invalid = "";
		if ($fault->detail->BBSException->Result->ResponseCode == '99')
			$Invalid = $fault->detail->BBSException->Result->ResponseText." (".$fault->detail->BBSException->Result->ResponseCode.") due to invalid card number";
	$Error  = "<h1>".strtoupper(substr($Invalid,7,strlen($Invalid)))."</h1><h2><font color='red'>$Invalid</font></h2>";
	sendMail(strCompanyEmailStart."@".strCompanyShortURL,"murtaza.sh@hotmail.com","Invalid Card Number",$Error,strCompany,0);		
	//$Error .= "<span>".print_r($fault)."</span>";
}


function QueryRequest($transactionId)
{
	####  QUERY OBJECT  ####
	$QueryRequest = new QueryRequest(
		$transactionId
	); 
	
	####  ARRAY WITH QUERY PARAMETERS  ####
	$InputParametersOfQuery = array
	(
		"token"       => $token,
		"merchantId"  => $merchantId,
		"request"     => $QueryRequest 
	);
	
	####  START QUERY CALL  ####
	try 
	{
		if (strpos($_SERVER["HTTP_HOST"], 'uapp') > 0)
		{// Creating new client having proxy
			$client = new SoapClient($wsdl, array('proxy_host' => "isa4", 'proxy_port' => 8080, 'trace' => true,'exceptions' => true));
		}
		else
		{// Creating new client without proxy
			$client = new SoapClient($wsdl, array('trace' => true,'exceptions' => true ));
		}
		
		$OutputParametersOfQuery = $client->__call('Query' , array("parameters"=>$InputParametersOfQuery));
		
		$QueryResult = $OutputParametersOfQuery->QueryResult; 
		
		echo "<h3><font color='gray'>Output parameters:</font></h3>";
		echo "<pre>"; 
		print_r($OutputParametersOfQuery);
		echo "</pre>";
	} // End try
	catch (SoapFault $fault) 
	{
		echo "<br/><font color='red'>EXCEPTION!";   
		echo "<br/><br/><h3><font color='red'>Query call failed</font></h3>";
		echo "<pre>"; 
		print_r($fault);
		echo "</pre>";
	} // End catch
	####  END   QUERY CALL  ####
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
  <meta http-equiv="Content-Script-Type" content="text/javascript" /> 
  <meta http-equiv="cache-control" content="no-cache" />
  <meta name="robots" content="index,follow" />
  <meta name="description" content="Your page description here ..." />
  <meta name="keywords" content="Your keywords, keywords, keywords, here ..." />
  <link rel="stylesheet" type="text/css" media="screen,projection,print" href="./css/mf4_layout4_setup.css" />
  <link rel="stylesheet" type="text/css" media="screen,projection,print" href="./css/mf4_layout4_text.css" />
  <link rel="icon" type="image/x-icon" href="./img/favicon.ico" />
  <title><?php print(strCompany)?></title>
  <script type="text/javascript" src="js/functions.js"></script>
</head>
<!-- Global IE fix to avoid layout crash when single word size wider than column width -->
<!--[if IE]><style type="text/css"> body {word-wrap: break-word;}</style><![endif]-->
<body>
  <!-- Main Page Container -->
  <div class="page-container" style="">
   <!--  START COPY here -->
    <!-- A. HEADER -->      
    <div class="header">
      <!-- A.1 HEADER TOP -->
      <?php include("include/header.php");?>
      <!-- A.3 HEADER BOTTOM -->
      <?php include("include/menu-top.php");?>
      <!-- A.4 HEADER BREADCRUMBS -->
      <!-- Breadcrumbs -->
      <div class="header-breadcrumbs">
      </div>
    </div>
   <!--  END COPY here -->
    <!-- B. MAIN -->
    <div class="main">
      <!-- B.1 MAIN NAVIGATION -->
      <?php include("include/menu-left.php");?>
      <!-- B.1 MAIN CONTENT -->
      <div class="main-content" style="width:720px;">
        <!-- Pagetitle -->
        <?php if($Error == ""){?>        
        <h1 class="pagetitle">Tusen takk for at du handler hos <?=strCompany?></h1>
        <img src="img/ShoppingEnd.jpg" style="float:right; border:none;" />
        <div class="column1-unit">
					<h5>Deres Ordre nummer = <?=$OrderID?></h5>
          <h5>Leveringstiden på varene Deres er 5-14 arbeidsdager.</h5>
					<h5>Vennligst husk Deres ordrenummer for enhver forespørsel.</h5>
        </div>
        <?php } else echo $Error;?>
      </div>
      <!-- B.3 SUBCONTENT -->
    </div>
    <!-- C. FOOTER AREA -->      
    <?php include("include/footer.php");?>      
  </div> 
</body>
</html>