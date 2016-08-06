<?php 
	include("lib/variables.php");
	include("lib/opencon.php");
	include("lib/functions.php");
	include("lib/error.php");
	
	$Error = "";
	$Records = 10;
	$Added = $Exist = 0;
	if(isset($_REQUEST['cboRecords']))
		$Records = $_REQUEST['cboRecords'];

	if(isset($_REQUEST['btnSubmit']))
	{
		require_once('recaptcha/recaptchalib.php');
		$privatekey = "6LcwZbwSAAAAAB38LEXOHEa8oBuJBJs10nwsS020 ";
		$resp = recaptcha_check_answer ($privatekey,
																	$_SERVER["REMOTE_ADDR"],
																	$_POST["recaptcha_challenge_field"],
																	$_POST["recaptcha_response_field"]);
		if (!$resp->is_valid)
		{
			// What happens when the CAPTCHA was entered incorrectly
			$Error = "The reCAPTCHA code was not entered correctly. Please try it again";
			//die ("The reCAPTCHA wasn't entered correctly. Go back and try it again." ."(reCAPTCHA said: " . $resp->error . ")");
		}
		else
		{
			$Query = "SELECT questionid FROM question WHERE UCASE(question) = '".strtoupper(trim($_REQUEST['txtNewQuestion']))."'";
			if (mysql_num_rows(mysql_db_query(DBName,$Query)) > 0)
				$Error = "Spørsmål ekisterer allerede";
			else
			{
				$UserID2 = 0;
				if(isset($_SESSION['UserID']) && $_SESSION['UserID'] > 0)
					$UserID2 = $_SESSION['UserID'];
				$QuestionID = getMaximum("question","questionid");
				$Qry = "INSERT INTO question(questionid, userid, genre, qtype, difficulty, dateadded, question, ipaddress)
					VALUES($QuestionID, $UserID2,'Q',3,6,'".date("Y-m-d H:i:s")."', '".addslashes($_REQUEST['txtNewQuestion'])."','".$_SERVER['REMOTE_ADDR']."')";
				if(mysql_db_query(DBName,$Qry))
				{
					if($UserID2 > 0)
					{
						$Added = 1;
						$Qry = "INSERT INTO userquestions(questionid, userid, email, sms, dateadded, noofreplies)
							VALUES($QuestionID, $UserID2, '".$_REQUEST['txtPhone']."','".$_REQUEST['txtSMS']."','".date("Y-m-d H:i:s")."',1)";
						mysql_db_query(DBName,$Qry);
					}
					else if($UserID2 <= 0 && $_REQUEST['txtPhone'] != "")
					{
						$Query = "SELECT userid FROM user WHERE email = '".trim($_REQUEST['txtPhone'])."'";
						if (mysql_num_rows(mysql_db_query(DBName,$Query)) > 0)
							$Added = $Exist = 1;
						else
						{
							header("Location: registrer.php?QuestionID=$QuestionID&txtPhone=".$_REQUEST['txtPhone']);
							exit;
						}
					}
					else if($UserID2 <= 0 && $_REQUEST['txtPhone'] == "")
						$Added = 1;
				}
				else
					$Error = "Transaction error!";
			}
		}	
	}
	
	if(isset($_REQUEST['btnAnswer']))
	{
		$Query = "SELECT questionid FROM question WHERE UCASE(question) = '".strtoupper(trim($_REQUEST['hdnSearch']))."'";
		if (mysql_num_rows(mysql_db_query(DBName,$Query)) > 0)
			$Error = "Spørsmål ekisterer allerede2 : ".$_REQUEST['hdnSearch'];
		else
		{
			$UserID2 = 0;
			if(isset($_SESSION['UserID']) && $_SESSION['UserID'] > 0)
				$UserID2 = $_SESSION['UserID'];
			$QuestionID = getMaximum("question","questionid");
			$Qry = "INSERT INTO question(questionid, userid, genre, qtype, difficulty, dateadded, question, ipaddress)
				VALUES($QuestionID, $UserID2,'Q',3,6,'".date("Y-m-d H:i:s")."', '".addslashes($_REQUEST['hdnSearch'])."','".$_SERVER['REMOTE_ADDR']."')";
			if(mysql_db_query(DBName,$Qry))
			{
				$AnswerID = getMaximum("answer","answerid");
				$Qry = "INSERT INTO answer(answerid, questionid, userid, dateadded, answer, ipaddress)
					VALUES($AnswerID, $QuestionID, $UserID2, '".date("Y-m-d H:i:s")."', '".addslashes($_REQUEST['txtAnswer'])."','".$_SERVER['REMOTE_ADDR']."')";
				mysql_db_query(DBName,$Qry);
				header("Location: sok.php?txtSearch=".$_REQUEST['hdnSearch']);
				exit;
			}
		}	
	}

	$strMessage="";
	if (@strlen($_GET['msg']) > 0)
		$strMessage=returnMessage($_GET['msg']);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
<title>Søk</title>
<meta name="author" content="<?=$a1?>" />
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<link href="style.css" rel="stylesheet" type="text/css" />
<link rel="shortcut icon" type="image/x-icon" href="./images/favicon.ico">
<script type="text/javascript" src="js/functions.js"></script>
<script type="text/javascript" src="js/behaviour.js"></script>
<script type="text/javascript" src="js/prototype.js"></script>
<script type="text/javascript" src="js/voter.js"></script>
<script type="text/javascript" src="js/cookies.js"></script>
<script type="text/javascript">

	var myrules = {
		'.thumberup' : function(element){
		element.onclick = function(){
			Voter.vote(this.id);
			return false;
		}
		},
		'.thumberdown' : function(element){
		element.onclick = function(){
			Voter.vote(this.id);
			return false;
		}
		}
	};
	Behaviour.register(myrules);

	function Copy()
	{
		if(document.frmQuestion.chkCopy.checked == true)
			document.frmQuestion.txtNewQuestion.value = document.Form.txtSearch.value;
		else
			document.frmQuestion.txtNewQuestion.value = "";
	}
	function Verify()
	{
		if(document.frmQuestion.txtNewQuestion.value == "")
		{
			alert("Skriv inn spørsmål");
			document.frmQuestion.txtNewQuestion.focus();
			return false;
		}
		else
			return true;
	}
	function Verify2()
	{
		if(document.frmAnswer.txtAnswer.value == "")
		{
			alert("Skriv inn Svar");
			document.frmAnswer.txtAnswer.focus();
			return false;
		}
		else
			return true;
	}

</script>
</head>
<body>
<?php include("include/adshowtop.php");?>
<div class="main">
  <div class="blok_header">
    <div class="header">
      <div class="logo"><a href="index.php"><img src="images/Banner.gif" width="400" height="80" border="0" alt="logo" style="border:none; margin-bottom:8px;" /></a></div>
			<?php include("include/login.php");?>
      <div class="clr"></div>
    </div>
  </div>
  <div class="clr"></div>
  <div class="body_bottom">
  <div class="menu">
      <ul>
        <li><a href="index.php"><span>Forside</span></a></li>
        <li><a href="quiz.php"><span>Quiz</span></a></li>
        <li><a href="#"><span>Om oss</span></a></li>
        <li><a href="admain.php"><span>Annonsere</span></a></li>
        <!--
        <li><a href="virker.php"><span>Hvordan virker dette?</span></a></li>
        <li><a href="jobb.php"><span>Jobb</span></a></li>
        <li><a href="faq.php"><span>FAQ</span></a></li>
        -->
      </ul>
    </div>
  </div>
  <div class="clr"></div>
  <div class="body">
  <div class="left">
		<div id="Search">  
      <h2>Spør om hva som helst</h2>
      <p style="margin-top:-15px;">Eksempel: Hvem sang Achy Breaky Heart?</p>
      <div class="search2">
      <form id="Form" name="Form" method="post" action="">
        <label>
         		<span><input name="txtSearch" type="text" class="keywords" id="textfield" maxlength="255" value="<?php if(isset($_REQUEST['btnSubmit'])) print(trim(stripslashes($_REQUEST['txtNewQuestion']))); else if(isset($_REQUEST['txtSearch'])) print(stripslashes($_REQUEST['txtSearch'])); else if(isset($_REQUEST['hdnSearch'])) print(stripslashes($_REQUEST['hdnSearch'])); else print("&nbsp;");?>" onfocus="Erase3('Form','txtSearch','Søk...');" onblur="Reverse3('Form','txtSearch','Søk...');"/></span>
            <input name="b" type="image" src="images/search2.gif" class="button" onclick="return VerifySearch();" />
        </label>
        <div style="margin:35px 0 0 435px;">
        	<select name="cboRecords" onchange="document.Form.submit();"><option value="10">10 svar</option><option value="50">50 svar</option><option value="100">100 svar</option></select>
          <script type="text/javascript" language="javascript">document.Form.cboRecords.value = <?=$Records?>;</script>
        </div>
      </form>
      </div>
      <br clear="all" />
      <!--<p style="position:absolute; margin-top:-45px;"><?=number_format(GetValue("COUNT(answerid)","answer","1"));?> Spørsmål/svar er registerert så langt</p>-->
  	</div><br clear="all" />
    
    <table class="answers" style="margin-top:15px;">
    <?php
			if((isset($_REQUEST['txtSearch']) || $_REQUEST['hdnSearch']) || isset($_REQUEST['btnSubmit']))
			{
				$Search = "";
				if(isset($_REQUEST['btnSubmit']))
					$Search = trim($_REQUEST['txtNewQuestion']);
				else if(isset($_REQUEST['hdnSearch']) && $_REQUEST['hdnSearch'] != "")
					$Search = $_REQUEST['hdnSearch'];
				else
					$Search = $_REQUEST['txtSearch'];
					
				$Length = strlen($Search);
				//$Words = count(explode(" ", $Search));				
				
				$Qry = "SELECT questionid, question, dateadded, canchange, quizid, ABS(LENGTH(question)-$Length) AS LenDif, MATCH (question) AGAINST ('".addslashes($Search)."' IN BOOLEAN MODE) AS Relevance
					FROM question
					HAVING Relevance > 0
					ORDER BY Relevance DESC, LenDif
					LIMIT 0,$Records";
				$Rst = mysql_db_query(DBName,$Qry);
				$Copy = 0;
				if(mysql_num_rows($Rst) > 0)
				{					
					while($obj = mysql_fetch_object($Rst))
					{
						$Back = "";
						$Svar = $Thumbsup = $Thumbsdown = 0;
						if(isset($_REQUEST['btnSubmit']) && isset($_REQUEST['txtNewQuestion']) && $_REQUEST['txtNewQuestion'] == stripslashes($obj->question))
						{
							$Copy = 1;
							$Back = "style=\"background-color:#FFFF00;\"";
						}
						else if(isset($_REQUEST['hdnSearch']) && strtoupper(stripslashes($_REQUEST['hdnSearch'])) == strtoupper($obj->question))
						{
							$Copy = 1;
							$Back = "style=\"background-color:#FFFF00;\"";
						}
						else if(isset($_REQUEST['txtSearch']) && strtoupper(stripslashes($_REQUEST['txtSearch'])) == strtoupper($obj->question))
						{
							$Copy = 1;
							$Back = "style=\"background-color:#FFFF00;\"";
						}
						$Qry2 = "SELECT answerid, answer, dateadded, thumbsup, thumbsdown FROM answer WHERE questionid = ".$obj->questionid." AND incorrect = 0 ORDER BY thumbsup DESC, dateadded DESC";
						$Rst2 = mysql_db_query(DBName,$Qry2);
						$Svar = mysql_num_rows($Rst2);
						if($Svar > 0)
						{
							$obj2 = mysql_fetch_object($Rst2);							
							$Thumbsup = $obj2->thumbsup;
							$Thumbsdown = $obj2->thumbsdown;
						}
						if(isset($_REQUEST['txtSearch']))
							$Percent = MatchPercentage($_REQUEST['txtSearch'],$obj->question);
						else if(isset($_REQUEST['hdnSearch']))
							$Percent = MatchPercentage($_REQUEST['hdnSearch'],$obj->question);						
		?>
    	<tr style="padding:5px 0 0 0;">
      	<td align="center">&nbsp;</td><!--<p><?=$Percent?>%</p>-->
        <td>
        	<div class="svar">
          <p class="psvar2" <?=$Back?>><?=$obj->question?></p>
          <?php if($Svar <= 0){?>
          <p class="psvar" style="color:#0066FF;">Spørsmål er stilt tidligere også, men er fortsatt ubesvart</p>
          <?php }else{?>
          <p class="psvar2"><strong>Svar: &nbsp; <?=stripslashes($obj2->answer)?></strong><?php if($obj->canchange == 1){?> &nbsp;<span style="color:#469aac;">(Svar kan forandre seg over tid)</span><?php }?></p>
					<?php }?>
          <p class="pdate"><?php print($obj->dateadded); if($Svar > 1){?> &nbsp; <a href="svar.php?QuestionID=<?=$obj->questionid?>" style="color:#469aac;">Se alle svarene</a><?php } if($obj->quizid == 0){?> &nbsp; <a href="svar.php?QuestionID=<?=$obj->questionid?>">Vil du også svare?</a><?php }?></p>
          <?php if($Svar != ""){?>
          <div class="thumbdiv">
            <a id="<?=$obj2->answerid?>.up" href="#" class="thumberup" style="color:#999999;"><?=$Thumbsup?></a> &nbsp; &nbsp; 
            <a id="<?=$obj2->answerid?>.down" href="#" class="thumberdown" style="color:#999999;"><?=$Thumbsdown?></a><br />
            <span style="font-size:10px; color:#FF0000;" id="<?=$obj2->answerid?>.msg"></span>
          </div>
          <?php }?>
         	</div>
        </td>
      </tr>
      <tr><td colspan="2"><div class="bg2">&nbsp;</div></td></tr>
		<?php
					}
				}
			}
		?>
    </table>
  	<div class="clr"></div>	
  </div>
  
  <div class="right">
  <form name="frmAnswer" method="post" action="">
  <?php if(!isset($_REQUEST['btnSubmit'])){ if($Copy == 0){?>
    <div style="position:absolute; margin:111px 0 0 -586px; color:#eeeeee; font:normal 14px Arial, Helvetica, sans-serif;">Kan du svare? <input type="text" name="txtAnswer" style="width:167px;"  /> <input type="submit" name="btnAnswer" value="Send" onclick="return Verify2();" />
    <input type="hidden" name="hdnSearch" value="<?php if(isset($_REQUEST['btnSubmit'])) print(trim($_REQUEST['txtNewQuestion'])); else if(isset($_REQUEST['txtSearch'])) print($_REQUEST['txtSearch']);?>" /></div>
    <div style="position:absolute; margin:124px 0 0 -594px;"><img src="images/Arrow.png" style="width:600px;" /></div>
  <?php }}?>
  </form>
  <div class="right_big">
  <div class="right_small">
  <?php if($Added >= 1){?>
	<div style="margin:0 0 10px 0; background-color:#8064a1;">
  	<p style="color:#FFFFFF; font-size:20px; font-weight:bold; text-align:center; line-height:20px;">Vi holder nå på å lete opp svar manuelt</p>
    <p style="color:#FFFFFF; font-size:14px; padding:0 2px 10px 2px; text-align:center; line-height:18px;">
		<?php if(isset($_SESSION['UserID']) && $_SESSION['UserID'] > 0) print("Svar vil bli sendt ".$_SESSION['email']." så snart som mulig.");?>
    Tjenesten vår er helt gratis :-)<br />    
    <?php if($Exist >= 1){print("<p style=\"color:#ff0000;\">Tips: Logg inn, så slipper du å skrive telefonnummer hver gang</p>");}?>
    </p>
  </div>
  <?php }else{?>
  <div style="background:url(images/Back4.gif) no-repeat; margin:0 0 0 7px; height:110px; color:#FFFFFF;">
  <p style="color:#FFFFFF; font-size:18px; font-weight:bold; text-align:center;">Ikke fornøyd med svaret?&nbsp; &nbsp; &nbsp;</p>
  <p style="color:#FFFFFF; padding:0 25px 0 5px; text-align:center; margin-top:-7px; line-height:18px;">Send inn spørsmålet så leter vi det opp for deg helt gratis og sender til din mail:</p>
  </div>
  <?php }?>
  <?php if($Error != ""){print("<p style=\"color:#FF0000;\">$Error</p>");}?>
  <form name="frmQuestion" method="post" style="margin:0 0 0 6px; font: normal 12px Arial, Helvetica, sans-serif; color:#555555;">
    <textarea name="txtNewQuestion" style="width:260px; height:80px; background:url(images/sok_back.png) repeat-x;"><?php if(!isset($_REQUEST['btnSubmit'])){ if($Copy == 0) print(stripslashes($_REQUEST['txtSearch']));}?></textarea><br />
    <?php if(!isset($_REQUEST['btnSubmit'])){ if($Copy == 1){?><input type="checkbox" name="chkCopy" onclick="Copy();" />&nbsp; Kopier spørsmål fra søkemotor?<?php }}?>
	<?php 
		$Phone = $Email = $Readonly = "";
		if(isset($_SESSION['UserID']) && $_SESSION['UserID'] > 0)
		{
			$Query = "SELECT phone, email FROM user WHERE userid = ".$_SESSION['UserID'];
			$obj = mysql_fetch_object(mysql_db_query(DBName,$Query));
			$Phone = $obj->phone;
			$Email = $obj->email;
			$Readonly = "readonly";
		}
	?>  
  <p style="margin-left:-6px;"><strong><a href="#">Send svar til:</a></strong></p>
  <table class="regtable">
    <tr height="30">
      <td class="regtd">Mail : </td>
      <td><input type="text" name="txtPhone" class="regtxtbox2" style="width:225px;" value="<?=$Email?>" <?=$Readonly?>/></td>
    </tr>
    <tr height="30">
      <td class="regtd">SMS : </td>
      <td><input type="text" name="txtSMS" class="regtxtbox2" style="width:225px;" value="<?=$Phone?>" readonly="readonly"/></td>
    </tr>
    <!--Gratistjenesten på SMS kommer snart-->
    <tr height="150">    	
      <td colspan="2">
      <div style="margin-left:-27px;">
			<?php
        require_once('recaptcha/recaptchalib.php');
        $publickey = "6LcwZbwSAAAAAA2GhV16P1OgGgO3qQ7TLBLRCOpv";
        echo recaptcha_get_html($publickey);
      ?>
      </div>
     	</td>
    </tr>
    <tr height="30">
      <td>&nbsp;</td>
      <td><input type="submit" name="btnSubmit" value="Send" onclick="return Verify();" /></td>
    </tr>
  </table>
  <?php if($Added >= 1){?>
	<div style="margin:0 0 10px 0; background-color:#8064a1;">
  	<p style="color:#FFFFFF; font-size:20px; font-weight:bold; text-align:center;">Takk for at du anbefaler gratistjenesten til andre!</p>
  </div>
  <?php }?>
  
  <input type="hidden" name="txtSearch" value="<?= isset($_REQUEST['txtSearch'])?$_REQUEST['txtSearch']:"&nbsp;"?>" />
  </form>
  </div>
  </div>
  </div>
    <div class="clr"></div>
  </div>
  <div class="clr"></div>
</div>
  <div class="footer_resize">
		<?php include("include/footer.php");?>
    <div class="clr"></div>
  </div>
<?php include("include/adshowbottom.php");?>
</body>
</html>