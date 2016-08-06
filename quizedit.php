<?php 
	include("lib/variables.php");
	include("lib/opencon.php");
	include("lib/functions.php");
	include("lib/combos.php");
	include("lib/error.php");
	
	$Error = "";
	if(isset($_REQUEST['Qu']) && $_REQUEST['Qu'] > 0)
		$QuizID = $_REQUEST['Qu'];

	if(isset($_REQUEST['Q']) && $_REQUEST['Q'] > 0)
		$Questions = $_REQUEST['Q'];
	else
	{
		$Qry = "SELECT * FROM quiz WHERE quizid = $QuizID";
		$obj2 = mysql_fetch_object(mysql_db_query(DBName,$Qry));
		$QuizType = $obj2->quiztype;
		$Questions = $obj2->questions;
		$Answers = $obj2->alternatives;
	}
	
	if(isset($_REQUEST['A']) && $_REQUEST['A'] > 0)
		$Answers = $_REQUEST['A'];

	if(isset($_REQUEST['QuizType']) && $_REQUEST['QuizType'] > 0)
		$QuizType = $_REQUEST['QuizType'];
	
	$NumCats = 4;
	if(isset($_REQUEST['NumCats']) && $_REQUEST['NumCats'] > 0)
		$NumCats = $_REQUEST['NumCats'];
	else
	{
		$QryM = "SELECT messageid FROM q2messages WHERE quizid = $QuizID ORDER BY messageid";
		$RstM = mysql_db_query(DBName,$QryM);
		$NumCats = mysql_num_rows($RstM);					
	}
		
	if(isset($_REQUEST['btnNext']))
	{
		$Error = "";
		for($i=1; $i<=$_REQUEST['Q']; $i++)
		{
			///// Picture Upload /////////////////////////////////////////////////////////////////////
			$PicType = $PicQ = "";
			if(isset($_FILES['txtPic'.$i]) && $_FILES['txtPic'.$i] != "")
			{
				$PicType = substr($_FILES['txtPic'.$i]['type'],6,strlen($_FILES['txtPic'.$i]['type']));
				if($PicType == "pjpeg")
					$PicType = "jpg";
				else if($PicType == "x-png")
					$PicType = "png";
				if($PicType != "")
					$PicQ = ", pictype = '$PicType'";
			}
			//////////////////////////////////////////////////////////////////////////////////////////

			if($_REQUEST['hdnQ'.$i] > 0)
			{
				$QuestionID = $_REQUEST['hdnQ'.$i];
				$QueryUpdate = "UPDATE q2questions SET question = '".addslashes($_REQUEST['Q'.$i])."' $PicQ WHERE questionid = $QuestionID AND quizid = $QuizID";
				if(!mysql_db_query(DBName,$QueryUpdate)) $Error .= "1";
			}
			else
			{
				$QuestionID = getMaximum("q2questions","questionid");
				$Qry = "INSERT INTO q2questions(questionid, quizid, quiztype, question, pictype)
					VALUES($QuestionID, $QuizID,2,'".addslashes($_REQUEST['Q'.$i])."','$PicType')";
				if(!mysql_db_query(DBName,$Qry))
					$Error .= "2";
			}

			///// Picture Upload /////////////////////////////////////////////////////////////////////
			if(isset($_FILES['txtPic'.$i]) && $_FILES['txtPic'.$i] != "")
			{						
				$UploadPath = QuizImagesPath.$QuizID."-".$QuestionID.".".$PicType;
				UploadFile('txtPic'.$i,$UploadPath,300,200); //Maximum Width x Maximum Height. For original size, send 0 for both. Work for jpg, gif and png
			}
			//////////////////////////////////////////////////////////////////////////////////////////
			
			for($j=1; $j<=$_REQUEST['A']; $j++)
			{
				if($_REQUEST['A'.$i."-".$j] != "Alternativ : ".$j)
				{
					if($_REQUEST['hdnA'.$i."-".$j] > 0)
					{
						$AnswerID = $_REQUEST['hdnA'.$i."-".$j];
						$QueryUpdate = "UPDATE q2answers SET answer = '".addslashes($_REQUEST['A'.$i."-".$j])."', points = ".$_REQUEST['cbo'.$i."-".$j]." WHERE quizid = $QuizID AND questionid = $QuestionID AND answerid = $AnswerID";
						//echo $QueryUpdate."<br><br>";
						if(!mysql_db_query(DBName,$QueryUpdate)) $Error .= "4";							
					}
					else
					{					
						$AnswerID = getMaximum("q2answers","answerid");
						$Qry = "INSERT INTO q2answers(answerid, questionid, quizid, quiztype, answer, points)
							VALUES($AnswerID, $QuestionID, $QuizID,2,'".addslashes($_REQUEST['A'.$i."-".$j])."','".$_REQUEST['cbo'.$i."-".$j]."')";
						//echo $Qry."<BR><BR>";
						if(!mysql_db_query(DBName,$Qry)) $Error .= "5";
					}
				}					
			}
		}

		for($i=1; $i<=$_REQUEST['NumCats']; $i++)
		{
			if($_REQUEST['hdnM'.$i] > 0)
			{
				$MessageID = $_REQUEST['hdnM'.$i];
				$QueryUpdate = "UPDATE q2messages SET fromnum = ".$_REQUEST['txtFra'.$i].", tonum = ".$_REQUEST['txtTil'.$i].", message = '".addslashes($_REQUEST['txtMessage'.$i])."' WHERE quizid = $QuizID AND messageid = $MessageID";
				//echo $QueryUpdate." - ".$_REQUEST['NumCats']."<br><br>";
				if(!mysql_db_query(DBName,$QueryUpdate)) $Error .= "6";
			}
			else
			{					
				$MessageID = getMaximum("q2messages ","messageid");
				$Qry = "INSERT INTO q2messages(messageid, quizid, fromnum, tonum, message)
					VALUES($MessageID,$QuizID,'".$_REQUEST['txtFra'.$i]."','".$_REQUEST['txtTil'.$i]."','".addslashes($_REQUEST['txtMessage'.$i])."')";
				//echo $Qry."<BR><BR>";
				if(!mysql_db_query(DBName,$Qry)) $Error .= "7";
			}
		}

		$Qry = "DELETE FROM q2questions WHERE questionid > $QuestionID AND quizid = $QuizID";
		mysql_db_query(DBName,$Qry);
		$Qry = "DELETE FROM q2answers WHERE answerid > $AnswerID AND quizid = $QuizID";
		mysql_db_query(DBName,$Qry);
		$Qry = "DELETE FROM q2messages WHERE messageid > $MessageID AND quizid = $QuizID";
		mysql_db_query(DBName,$Qry);

		$UQ = "";
		$OldQ = GetValue("questions","quiz","quizid = $QuizID");
		$UseQ	= GetValue("usequestions","quiz","quizid = $QuizID");
		if($OldQ == $UseQ)
			$UQ = ", usequestions = ".$_REQUEST['Q'];
		$Qry = "UPDATE quiz SET questions = ".$_REQUEST['Q']." $UQ WHERE quizid = $QuizID";
		mysql_db_query(DBName,$Qry);

		if($Error == "")
		{
			header("Location: nyquiz1-3.php?QuizID=$QuizID");
			exit;
		}
		else
		{
			die;
			header("Location: nyquiz1-1.php?ErrorCode=$Error");
			exit;
		}
	}		
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
<title>Ny quiz - <?=strCompany?></title>
<meta name="author" content="<?=$a1?>" />
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<link href="style.css" rel="stylesheet" type="text/css" />
<link rel="shortcut icon" type="image/x-icon" href="./images/favicon.ico">
<script type="text/javascript" src="js/functions.js"></script>
<script type="text/javascript" src="js/behaviour.js"></script>
<script type="text/javascript" src="js/prototype.js"></script>
<script type="text/javascript" src="js/voter.js"></script>
<script type="text/javascript" src="js/cookies.js"></script>
<script type="text/javascript" language="javascript">
	function VerifyQuestions()
	{
		var Index = Radio = 0;
		for(i=1; i<=document.Form.Q.value; i++)
		{
			Index = Radio = 0;
			if(document.Form.elements['Q'+i].value == "Ditt spørsmål nr. "+i)
			{
				alert("Skriv inn spørsmål "+i);
				document.Form.elements['Q'+i].select();
				return false;
			}
			if(document.Form.elements['txtPic'+i])
			{
				if(!isValidFileExt("Form","txtPic"+i,".jpg .jpeg .gif .png"))
				{
					alert("Tillatte bildeformater: JPG, GIF og PNG (spørsmål nr."+i+")");
					document.Form.elements['txtPic'+i].focus();
					return false
				}
			}
			for(j=1; j<=document.Form.A.value; j++)
			{
				if(document.Form.elements['A'+i+'-'+j].value != "Alternativ : "+j)
					Index++;
			}
			if(Index < 2)
			{
				alert("Velg minst 2 alternativer for spørsmål "+i);
				for(j=1; j<=document.Form.A.value; j++)
				{
					if(document.Form.elements['A'+i+'-'+j].value == "Alternativ : "+j)
					{
						Index++;
						document.Form.elements['A'+i+'-'+j].select();
						return false;
					}
				}				
			}
		}
		
		for(i=1; i<=3; i++)
		{
			if(IsBlank("Form","txtTil"+i))
			{
				alert("Skriv inn poeng"); //Please enter points
				document.Form.elements['txtTil'+i].focus();
				return false;
			}
		}
		for(i=1; i<=4; i++)
		{
			if(IsBlank("Form","txtMessage"+i))
			{
				alert("Please enter messaeg to display");
				document.Form.elements['txtMessage'+i].focus();
				return false;
			}
		}
		return true;
	}
	
	function DelQ()
	{
		if(confirm("Du må finne bildene på nytt hvis du legger til flere spørsmål. Fortsette?"))
		{
			document.Form.Q.value = parseInt(document.Form.Q.value) - 1;
			document.Form.submit();
		}
	}
	function AddQ()
	{
		if(confirm("Du må finne bildene på nytt hvis du legger til flere spørsmål. Fortsette?"))
		{
			document.Form.Q.value = parseInt(document.Form.Q.value) + 1;
			document.Form.submit();
		}
	}

	function DelC()
	{
		if(confirm("Du må finne bildene på nytt hvis du legger til flere spørsmål. Fortsette?"))
		{
			document.Form.NumCats.value = parseInt(document.Form.NumCats.value) - 1;
			document.Form.submit();
		}
	}
	function AddC()
	{
		if(confirm("Du må finne bildene på nytt hvis du legger til flere spørsmål. Fortsette?"))
		{
			document.Form.NumCats.value = parseInt(document.Form.NumCats.value) + 1;
			document.Form.submit();
		}
	}

	function CalculateMax()
	{
		var MaxP = TotalP = 0;
		for(i=1; i<=document.Form.Q.value; i++)
		{
			MaxP = 0;
			for(j=1; j<=document.Form.A.value; j++)
			{
				if(parseInt(document.Form.elements['cbo'+i+'-'+j].value) > parseInt(MaxP))
					MaxP = parseInt(document.Form.elements['cbo'+i+'-'+j].value);
			}
			TotalP += parseInt(MaxP);
		}
		return TotalP;
	}
	
	function CalculatePoints()
	{
		var TotalCats = parseInt(document.Form.NumCats.value);
		document.Form.elements['txtTil'+TotalCats].value = CalculateMax();
	}
	
	function FillNext(i)
	{
		var Max = CalculateMax();
		if(IsNumber(document.Form.elements['txtTil'+i].value,false,false,1))
		{
			if(parseInt(document.Form.elements['txtTil'+i].value) >= parseInt(Max) && i < 4)
			{
				alert("Skriv et tall som er mindre enn "+Max); //Please enter points less than
				document.Form.elements['txtTil'+i].focus();
			}
			else
			{
				j = parseInt(i)+1;
				document.Form.elements['txtFra'+j].value = parseInt(document.Form.elements['txtTil'+i].value) + 1;
			}
		}
		else
		{
			alert("Skriv et gyldig tall"); //Please enter valid number
			/*document.Form.elements['txtTil'+i].focus();*/
		}
	}
</script>
</head>
<body>
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
        <li><a href="quiz.php" class="active"><span>Quiz</span></a></li>
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
  <div class="body3">
  <h1><?= GetValue("title","quiz","quizid = $QuizID");?></h1>
  <?php if($Error != ""){?><p style="color:#FF0000; margin:0; padding:0;"><strong><?=$Error?></strong></p><?php }?>
  	<form name="Form" method="post" enctype="multipart/form-data">
  	<table style="font:normal 14px Arial, Helvetica, sans-serif; width:700px;">
    	<tr>
      	<td>
        <?php
					$QryQ = "SELECT questionid, question, pictype FROM q2questions WHERE quizid = $QuizID ORDER BY questionid";
					//echo $QryQ;
					$Files = 0;
					$RstQ = mysql_db_query(DBName,$QryQ);
					$RowsQ = mysql_num_rows($RstQ);
					for($i=1; $i<=$Questions; $i++)
					{
						$Question = "Ditt spørsmål nr. $i";
						$QID = 0;
						if($RowsQ >= $i)
						{
							$objQ = mysql_fetch_object($RstQ);
							$Question = $objQ->question;
							$QID = $objQ->questionid;
						}
						else
						{
							$QID = 0;
							if(isset($_REQUEST['Q'.$i]))
								$Question = $_REQUEST['Q'.$i];
						}
				?>
	      <div style="float:left"><strong><?=$i." - ".$QID?></strong>&nbsp;<?php if(file_exists("img/quiz/$QuizID-$QID.$objQ->pictype")){?><img src="img/quiz/<?=$QuizID."-".$QID.".".$objQ->pictype?>" /><?php }?></div>
        <table style="border:1px solid #999999; padding:5px; margin-bottom:15px;">
          <tr><td colspan="2"><input type="text" name="Q<?=$i?>" value="<?=$Question?>" onfocus="Erase('Q','Ditt spørsmål nr. <?=$i?>',<?=$i?>,0);" onblur="Reverse('Q','Ditt spørsmål nr. <?=$i?>',<?=$i?>,0);" class="quizquestion" /><input type="hidden" name="hdnQ<?=$i?>" value="<?=$QID?>" /></td></tr>
					<?php if(!file_exists("img/quiz/$QuizID-$QID.$objQ->pictype") && $Files < 20){ $Files++;?>
					<tr><td colspan="2">Last opp bilde : <input type="file" name="txtPic<?=$i?>" class="quizfile" /></td></tr>
          <?php }
					
						$QryA = "SELECT answerid, answer, points FROM q2answers WHERE questionid = ".$objQ->questionid." ORDER BY answerid";
						//echo $QryQ;
						$RstA = mysql_db_query(DBName,$QryA);
          	for($j=1; $j<=$Answers; $j++)
						{
							$Answer = "";
							$Checked = $chkDisable = false;
							$AID = 0;
							if($RowsQ >= $i)
							{
								$objA = mysql_fetch_object($RstA);
								$Answer = $objA->answer;
								$AID = $objA->answerid;
								$Selected = $objA->points;
								$Cbo = "onChange='CalculatePoints();'";
							}
							else
							{
								if(isset($_REQUEST['A'.$i."-".$j]))
								{
									$Answer = $_REQUEST['A'.$i."-".$j];
									$Selected = $_REQUEST['cbo'.$i."-".$j];
								}
								else
								{
									$Answer = "Alternativ : $j";
									$Cbo = "disabled='true'";
									$Selected = 0;
								}
								$chkDisable = true;								
								$Cbo .= " onChange='CalculatePoints();'";
							}
							?>
          <tr>
          	<td>
            	<input type="text" name="A<?=$i."-".$j?>" value="<?=$Answer?>" onfocus="Erase('A','Alternativ : <?=$j?>',<?=$i?>,<?=$j?>);" onblur="Reverse('A','Alternativ : <?=$j?>',<?=$i?>,<?=$j?>);" class="quizanswer" /> &nbsp; &nbsp; <?php NumCombo("cbo".$i."-".$j,0,10,$Selected,"","textbox",$Cbo);?>
              <input type="hidden" name="hdnA<?=$i."-".$j?>" value="<?=$AID?>" />
            </td>
          </tr>
          <?php }?>
        </table>
				<?php }?>
        </td>
      </tr>
      <tr><td><input type="button" name="addQuestion" value="&nbsp;Legg til Spørsmål +&nbsp;" onclick="AddQ();" />&nbsp;&nbsp;<input type="button" name="delQuestion" value="&nbsp;Slett Spørsmål -&nbsp;" onclick="DelQ();" /></td></tr>
      <tr><td>&nbsp;</td></tr>
      <tr>
      	<td>
        <table style="font-size:12px; color:#444444;">
        	<tr style="font-size:16px; font-weight:bold;">
          	<td style="width:200px;">Poeng:</td>
            <td style="width:400px;">Tilbakemelding:</td>
          </tr>
          
          <?php
					$txtMsg = $Value = $Disable = "";					
          $QryM = "SELECT messageid, quizid, fromnum, tonum, message FROM q2messages WHERE quizid = $QuizID ORDER BY messageid";
					$RstM = mysql_db_query(DBName,$QryM);
					$RowsM = mysql_num_rows($RstM);					
					for($i=1; $i<=$NumCats; $i++)
					{
						if($RowsM >= $i)
						{
							$objM = mysql_fetch_object($RstM);
							$FrmNum = $objM->fromnum;
							$ToNum = $objM->tonum;
							$txtMsg = $objM->message;
							$MID = $objM->messageid;
						}
						else
						{
							$FrmNum = $ToNum;
							$ToNum = $ToNum;
							$txtMsg = "-Bedømmelse her-";
							$MID = 0;
						}

						$OnBlur = "onBlur='FillNext($i);'";
						if($i == $NumCats)
						{
							$Disable = "readonly='readonly'";
							$OnBlur = "";
						}
					?>
        	<tr>
          	<td>Fra: <input type="text" name="txtFra<?=$i?>" value="<?=$FrmNum?>" style="width:30px; height:13px; font-size:12px; color:#666666;" readonly="readonly" /> &nbsp; Til:  <input type="text" name="txtTil<?=$i?>" <?=$Disable?> <?=$OnBlur?> value="<?=$ToNum?>" style="width:30px; height:13px; font-size:12px; color:#333333;" /></td>
            <td>
            	<input type="text" name="txtMessage<?=$i?>" value="<?=$txtMsg?>" style="width:400px; height:13px; font-size:12px; color:#333333;" />
              <input type="hidden" name="hdnM<?=$i?>" value="<?=$MID?>" />
            </td>
          </tr>
          <?php }?>
          <tr><td colspan="2" style="height:10px;"></td></tr>
          <tr><td colspan="2"><input type="button" name="addCat" value="&nbsp;Bedømmelse +&nbsp;" onclick="AddC();" />&nbsp; &nbsp;<input type="button" name="delCat" value="&nbsp;Bedømmelse -&nbsp;" onclick="DelC();" /></td></tr>
          <tr><td colspan="2" style="height:10px;"></td></tr>
        </table>
        </td>
      </tr>
      <tr>
      	<td align="center"><input type="submit" name="btnNext" value="Generer quiz >>" onclick="return VerifyQuestions();" style="width:150px; height:35px;" />
      	<input type="hidden" name="Q" value="<?=$Questions?>" />
        <input type="hidden" name="A" value="<?=$Answers?>" />
        <input type="hidden" name="Qu" value="<?=$QuizID?>" />
        <input type="hidden" name="QuizType" value="<?=$QuizType?>" />
        <input type="hidden" name="NumCats" value="<?=$NumCats?>" />
        </td>
      </tr>
    </table>
    </form>
    <div class="clr"></div>
  </div>
  <div class="clr"></div>
</div>
<div class="footer_resize">
		<?php include("include/footer.php");?>
    <div class="clr"></div>
</div>
</body>
</html>
<script type="text/javascript" language="javascript">CalculatePoints();</script>