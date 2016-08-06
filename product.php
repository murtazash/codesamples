<?php
	session_start();
	include("lib/opencon-mysqli.php");
	include("lib/variables.php");
	include("lib/functions.php");
	include("lib/actions.php");
	include("lib/combos.php");
	
	if (is_numeric($_REQUEST['ProductID']))
		$ProductID = $db->real_escape_string($_REQUEST['ProductID']);
	else
	{
		$ProductID = 0;
		die("Error: 420");
	}

	$CategoryID = GetValuei("categories_id","products_to_categories","products_id = $ProductID",$db);
	if(isset($_REQUEST['AddToCart']))
	{
		$Where = "";
		if($_REQUEST['cboColor'] > 0){$Where .= " AND colorid = ".$db->real_escape_string($_REQUEST['cboColor']);}
		if($_REQUEST['cboSize'] > 0){$Where .= " AND sizeid = ".$db->real_escape_string($_REQUEST['cboSize']);}
		if(isAvailablei("products_id","customers_basket","customers_id = ".$db->real_escape_string($_SESSION['CustomerID'])." AND products_id = $ProductID $Where",$db))
		{
			$Qry = "UPDATE customers_basket SET quantity = ".$db->real_escape_string($_REQUEST['cboQty']).", date_added = '".date("Y-m-d H:i:s")."'
				WHERE customers_id = ".$db->real_escape_string($_SESSION['CustomerID'])." AND products_id = $ProductID $Where";
			$db->query($Qry);
			header("Location: shoppingcart.php?Msg=89");
		}
		else
		{
			$BasketID = getMaximumi("customers_basket","customers_basket_id",1,$db);
			$Qry = "INSERT INTO customers_basket(customers_basket_id,customers_id,products_id,colorid,sizeid,quantity,date_added)
				VALUES($BasketID,".$db->real_escape_string($_SESSION['CustomerID']).",$ProductID,".$db->real_escape_string($_REQUEST['cboColor']).",".$db->real_escape_string($_REQUEST['cboSize']).",".$db->real_escape_string($_REQUEST['cboQty']).",'".date("Y-m-d H:i:s")."')";
			$db->query($Qry);
			header("Location: shoppingcart.php");
		}
	}
	
	if(isset($_REQUEST['btnSubmit']))
	{
		if (md5($_POST['norobot']) != $_SESSION['randomnr2'])
		{ 
			die ("Feil kode inntastet. Gå tilbake og prøv igjen.");
		}
		else
		{
			$CommentID = getMaximumi("products_comments","comments_id",1,$db);
			$Qry = "INSERT INTO products_comments (comments_id,products_id,name,comments,dateadded)
				VALUES($CommentID,".$ProductID.",'".$db->real_escape_string($_REQUEST['txtName'])."','".$db->real_escape_string(addslashes($_REQUEST['comments']))."','".date("Y-m-d H:i:s")."')";
			if($db->query($Qry))
				header("Location: product.php?ProductID=$ProductID&CommentsOK");
		}
	}

	$Query = "SELECT P.products_id, P.products_quantity, P.products_price, P.products_oldprice, PD.products_name, PD.products_description, PD.size, PD.color, PD.pictype, PD.suppliercode
		FROM products P INNER JOIN products_description PD ON P.products_id = PD.products_id
		WHERE P.products_id = $ProductID";
	$Rst5 = $db->query($Query);
	$obj5 = mysqli_fetch_object($Rst5);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
  <meta name="author" content="<?=$a1?>" />
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
  <meta http-equiv="Content-Script-Type" content="text/javascript" /> 
  <meta http-equiv="cache-control" content="no-cache" />
  <meta name="robots" content="index,follow" />
  <meta name="description" content="<?php print($obj5->products_name." - ".$obj5->products_description." - ".strCompany)?>" />
  <meta name="keywords" content="<?php print($obj5->products_name." - ".strCompany)?>" />
  <link rel="stylesheet" type="text/css" media="screen,projection,print" href="./css/mf4_layout4_setup.css" />
  <link rel="stylesheet" type="text/css" media="screen,projection,print" href="./css/mf4_layout4_text.css" />
  <link rel="icon" type="image/x-icon" href="./img/favicon.ico" />
	<!--<script type="text/javascript" src="highslide/highslide-with-gallery.js"></script>-->
	<script type="text/javascript" src="highslide/highslide-with-html.js"></script>
  <script type="text/javascript" src="highslide/highslide.config.js" charset="utf-8"></script>
  <link rel="stylesheet" type="text/css" href="highslide/highslide.css" />
  <script type="text/javascript" src="js/functions.js"></script>
  <!--[if lt IE 7]>
  <link rel="stylesheet" type="text/css" href="highslide/highslide-ie6.css" />
  <![endif]-->  
  <title><?php print($obj5->products_name." - ".strCompany)?></title>
  <script type="text/javascript" language="javascript">
	function AddToOrder(Color,Size)
	{
		var Error = "";
		if(Color == 1)
		{
			if(document.Order.cboColor.value == 0)
				Error += "Vennligst velge farge av produkt\n"; //alert("Please select the color of the product!");
			else
				Color = document.Order.cboColor.value;
		}
		if(Size == 1)
		{
			if(document.Order.cboSize.value == 0)
				Error += "Vennligst velge størrelse av produkt\n"; //alert("Please select the size of the product!");
			else
				Size = document.Order.cboSize.value;
		}
		if(document.Order.cboQty.value <= 0)
			Error += "Vennligst velge antall av produkt\n";	//alert("Please select the quantity of the product!");
			
		if(Error == "")
		{
			document.Order.action = "product.php?ProductID="+document.Order.ProductID.value+"&cboColor="+Color+"&cboSize="+Size+"&cboQty="+document.Order.cboQty.value+"&AddToCart";
			document.Order.submit();
		}
		else
			alert(Error);
	}
	
	function VerifyComments()
	{
		if(document.frmComments.txtName.value == "")
		{
			alert("Skriv inn ditt navn!");
			document.frmComments.txtName.focus();
			return false;
		}
		if(document.frmComments.comments.value == "")
		{
			alert("Skriv inn ditt kommentar!");
			document.frmComments.comments.focus();
			return false;
		}
		return true;
	}
	</script>
</head>
<!-- Global IE fix to avoid layout crash when single word size wider than column width -->
<!--[if IE]><style type="text/css"> body {word-wrap: break-word;}</style><![endif]-->
<body>
  <!-- Main Page Container -->
  <div class="page-container">
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
      <div class="main-content" style="width:765px;">
      
        <!-- Pagetitle -->
        <h1 class="pagetitle"><?=$obj5->products_name?></h1>
        <!-- Content unit - Two columns -->

        <!-- Content unit - Two columns -->
        <div class="column2-unit-left" style=" width:300px;">
					<img src="image.php?image=/images/products/<?=$obj5->products_id.".".$obj5->pictype?>&width=300&height=300" alt="" style="border:1px solid #c2c2c2; z-index:0; position:relative;" class="center" />
          <br clear="all" />
          <!--<a href="#"><img src="img/find.png" style="border:none;"/></a><a href="#"><img src="img/zoom4.png" style="border:none;" /></a>-->
          <a href="images/products/<?=$obj5->products_id.".".$obj5->pictype?>" class="highslide" onclick="return hs.expand(this)" style="float:right; margin:-55px 30px 0 0; z-index:3; position:relative;">
          	<img src="img/zoom4.png"  alt="" style="border:none; z-index:5;" />
          </a><br clear="all" />
          
        </div>
        <div style="float:left; width:110px; height:115px; background:#fff url(img/vertical-back.gif) repeat-x; border:1px solid #dddddd; padding:8px;">
        	<div>
          	<a name="fb_share" type="button" share_url="http://<?=$_SERVER["SERVER_NAME"].strRootDirectoryName?>/product.php?ProductID=<?=$ProductID?>" href="http://www.facebook.com/sharer.php">del</a><script src="http://static.ak.fbcdn.net/connect.php/js/FB.Share" type="text/javascript"></script><br />
            <iframe src="http://www.facebook.com/plugins/like.php?locale=nb_NO&href=http%3A%2F%2F<?=$_SERVER["SERVER_NAME"].strRootDirectoryName?>%2Fproduct.php%3FProductID%3D<?=$ProductID?>&amp;layout=button_count&amp;show_faces=false&amp;width=110&amp;action=like&amp;font&amp;colorscheme=light&amp;height=22" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:110px; height:22px; margin-top:5px;" allowTransparency="true"></iframe>
          </div>
          <div style="display:block; height:21px;">
          	<a href="#" onclick="return hs.htmlExpand(this, { width: 400, headingText: 'BLOGG OM', wrapperClassName: 'titlebar' } )"><img src="img/blogger_16.png" style="margin:0; border:none; vertical-align:middle;"/>&nbsp;Blogg om</a>
            <div class="highslide-maincontent" style="font-size:9px;">
            <p><strong>Marker linken under, kopier og lim inn på bloggen din.</strong></p><br clear="all" />
            <p>Det er viktig at du kontrollerer koden som bloggverktøyet ditt lager.<br />Enkelte bloggverktøy gjør om litt på koden om man er i "HTML-läge",<br />dette gjør at bildet ikke fungerer på bloggen din.<br />Koden ska se HELT lik ut som den du kopierer her.</p><br clear="all" />
            <p><strong>De fleste bloggverkøy har et felt der du kan lagre den riktige koden du skriver.</strong></p><br clear="all" />
            <form name="blogform"><textarea style="width:395px; height:60px; font-size:9px;" readonly="readonly" id="imageUrl" name="imageUrl" onclick="this.focus(); this.select();"><a href="http://<?=$_SERVER["SERVER_NAME"].strRootDirectoryName?>/product.php?ProductID=<?=$ProductID?>" title="<?=$obj5->products_name?> - <?=strCompany?>"><img src="http://<?=$_SERVER["SERVER_NAME"].strRootDirectoryName?>/image.php?image=/images/products/<?=$obj5->products_id.".".$obj5->pictype?>&width=220&height=220" alt="<?=$obj5->products_name?> - <?=strCompany?>" border="0" /></a></textarea></form>
            </div>
          </div>
          <div style="display:block; height:21px;"><a href="recommend.php?ProductID=<?=$ProductID?>" onclick="return hs.htmlExpand(this, {objectType: 'iframe', width: 450, headingText: 'Tips en venn', wrapperClassName: 'titlebar' } )"><img src="img/email_16.png" style="margin:0; border:none; vertical-align:middle;"/>&nbsp;Tips en venn</a></div>


        </div>
        
        <div class="column2-unit-right" style=" width:320px;">
        	<h2 style="margin-top:0px; background-color:#e8e8e8;">Varenr:  <?=$obj5->suppliercode?></h2>
					<?php if($obj5->products_oldprice > 0){?><p style="text-decoration:line-through; color:#FF0000;">Kr <?=$obj5->products_oldprice?></p><?php }else print("<br>");?>
          <h2 style="margin-top:-15px;">Kr <?=$obj5->products_price?></h2>
          <?php 
						if($obj5->color != "" && $obj5->color != 0)
						{
							$Colors = "";
							$Qry = "SELECT * FROM pcolor WHERE colorid IN ($obj5->color)";
							$Rst = $db->query($Qry);
							while($obj = $Rst->fetch_object())
							{
								$Colors .= $obj->color.", ";
							}
							$Colors = substr($Colors,0,strlen($Colors)-2);
							print("<h3 style=\"color:#000000; font-size:15px; margin-top:5px;\">Farge : <span style=\"color:#555555;\">$Colors</span></h3>");
						}							
					?>
          <?php 
						if($obj5->size != "" && $obj5->size != 0)
						{
							$Sizes = "";
							$Qry = "SELECT * FROM psize WHERE sizeid IN ($obj5->size)";
							$Rst = $db->query($Qry);
							while($obj = $Rst->fetch_object())
							{
								$Sizes .= $obj->size.", ";
							}
							$Sizes = substr($Sizes,0,strlen($Sizes)-2);
							print("<h3 style=\"color:#000000; font-size:15px; margin-top:5px;\">Størrelse : <span style=\"color:#555555;\">$Sizes</span></h3>");
						}							
					?>
          <p style="font-size:100%; color:#444444;"><?=nl2br($obj5->products_description)?></p>
          <table cellpadding="0" cellspacing="7" style="border:1px solid #CCCCCC;">
          <?php if(isset($_SESSION['CustomerID']) && $_SESSION['CustomerID'] > 0){?>
          	<form name="Order" method="post">
            <tr>
              <?php $chkC = 0; if($obj5->color != "" && $obj5->color != 0){?><td><?php FillComboi("cboColor","pcolor","colorid","color","WHERE colorid IN ($obj5->color)","","Velg farge","textbox","",1,$db); $chkC = 1;?><td><?php }?>
              <?php $chkS = 0; if($obj5->size != "" && $obj5->size != 0)  {?><td><?php FillComboi("cboSize","psize","sizeid","size","WHERE sizeid IN ($obj5->size)","","Velg Størrelse","textbox","",2,$db); $chkS = 1;?><td><?php }?>
              <td> 
              	<select name="cboQty">
                	
									<?php
										if($obj5->products_quantity > 0)
										{
											echo "<option value=\"0\">Velg antall</option>";
											for($i=1; $i<=$obj5->products_quantity; $i++)
											{
												print("<option value=\"$i\">$i</option>");		
											} 
										}
										else
											echo "<option value=\"0\">Ikke på lager</option>";										
									?>                  
                </select>
              </td>
            </tr>
            <tr>
            	<td>
              	<div class="buy" style="margin-top:10px;"><a href="JAVASCRIPT: AddToOrder(<?=$chkC?>,<?=$chkS?>);" class="buylink" style="font-size:120%">Kjøp</a></div>
                </select><input type="hidden" name="ProductID" value="<?=$ProductID?>">
              </td>
            </tr>
            </form>
          <?php }else{?>
	          <tr><td><div class="buy" style="margin-top:10px;"><a href="secure.php" class="buylink" style="font-size:120%">Kjøp</a></div></td></tr>
            <?php }?>					
          </table>
        </div>
        <br clear="all" />
        
        <div class="product-lower-section" >
        <?php
          $Qry = "SELECT * FROM pics WHERE products_id = ".$obj5->products_id;
          $Rst = $db->query($Qry);
          if($Rst->num_rows > 0)
          {
        ?>
        <table cellpadding="0" cellspacing="0" style="width:200px; float:left; margin-right:40px;">
        <tr height="24"><th colspan="3" class="THeading">Mer bilder</th></tr>
          <?php 
            $Index = 0;
            while($obj = $Rst->fetch_object())
            {
              if($Index % 4 == 0)
                print("<tr>");
              $Index++;
          ?>
            <td>
            <table style="border:1px solid #999999; margin:5px 2px 2px 2px;">
              <tr>
                <td width="156" height="110">
                  <a href="images/products/sub/<?=$obj->picid.".".$obj->pictype?>" class="highslide" title="<?=$obj->picname?>" onclick="return hs.expand(this)">
                    <img src="image.php?image=/images/products/sub/<?=$obj->picid.".".$obj->pictype?>&width=150&height=100"  alt="<?=$obj->picname?>" style="border:none;" class="center" />
                  </a> 
                </td>
              </tr>
              <!--<tr><td style="text-align:center; font-size:9px;"><?=$obj->picname?></td></tr>-->
            </table>
            </td>
          <?php
              if($Index % 4 == 0)
                print("</tr>");
            }
          ?>
        </table>
        <?php }?>


        <?php
          
					$Qry = "SELECT DISTINCT P.products_id, PD.pictype, PD.products_name
						FROM products P 
						INNER JOIN products_description PD ON P.products_id = PD.products_id
						INNER JOIN products_to_categories PTC ON P.products_id = PTC.products_id 
						WHERE PTC.categories_id = $CategoryID AND PTC.products_id <> $ProductID
						ORDER BY RAND() LIMIT 0,8";
          //echo $Qry; die;
					$Rst = $db->query($Qry);
          if($Rst->num_rows > 0)
          {
        ?>
        <table cellpadding="0" cellspacing="0" style="width:300px; float:left; margin-right:0px;">
        <tr height="24"><th colspan="3" class="THeading">Andre har også kjøpt</th></tr>
          <?php 
            $Index = 0;
            while($obj = $Rst->fetch_object())
            {
              if($Index % 4 == 0)
                print("<tr>");
              $Index++;
          ?>
            <td>
            <table style="border:1px solid #999999; margin:5px 2px 2px 2px;">
              <tr>
                <td width="156" height="110">
                  <a href="product.php?ProductID=<?=$obj->products_id?>" title="<?=$obj->products_name?>">
                    <img src="image.php?image=/images/products/<?=$obj->products_id.".".$obj->pictype?>&width=150&height=100"  alt="$obj->products_name" style="border:none;" class="center" />
                  </a> 
                </td>
              </tr>
              <!--<tr><td style="text-align:center; font-size:9px;"><?=$obj->products_name?></td></tr>-->
            </table>
            </td>
          <?php
              if($Index % 4 == 0)
                print("</tr>");
            }
          ?>
        </table>
        <?php }?>

        </div>
        <br clear="all" />
        
        <div class="product-lower-section" >
        
        <table cellpadding="0" cellspacing="0" style="width:420px; float:left; margin-right:10px; color:#444444;">
        <tr height="24"><th class="THeading">Les kommentar</th></tr>
        <?php
					$Qry = "SELECT * FROM products_comments WHERE products_id = $ProductID ORDER BY dateadded DESC";
					$Rst = $db->query($Qry);
					if($Rst->num_rows > 0)
					{
				?>
        	<tr>
          	<td>
            <table cellpadding="0" cellspacing="0" >
            	<tr><td>
        <?php
						while($obj = $Rst->fetch_object())
						{
				?>
              <table cellpadding="0" cellspacing="0" style="height:35px; width:410px; background:url(img/comment-heading.png) no-repeat;">
              <tr>
                <th style="width:200px; text-align:left; padding-left:27px; padding-top:3px;"><img src="img/user.png" style="border:none; vertical-align:middle; margin-top:-1px;" /><?=$obj->name?></th>
                <th style="width:200px; text-align:right; padding-right:30px; padding-top:3px;"><img src="img/date.png" style="border:none; vertical-align:middle; margin-top:-2px;" /><?=ShowDate($obj->dateadded,0);?></th>
              </tr>
              </table>
              <table cellpadding="0" cellspacing="0">
              <tr><td colspan="2" style="padding:0 20px 0 27px; font-size:10px; line-height:11px;"><?=stripslashes(nl2br($obj->comments));?></td></tr>
              <tr><td colspan="2" style="height:10px;"></td></tr>
              </table>
				<?php		
						}
				?>
        		</td></tr>
            </table>
            </td>
          </tr>
        <?php
					}
				?>
        </table>
        
        
        <form name="frmComments" method="post">
        <table cellpadding="0" cellspacing="0" style="width:340px; float:left; margin-right:0px; color:#444444;">
        <tr height="24"><th colspan="3" class="THeading">Skriv kommentar</th></tr>
          <?php if(isset($_REQUEST['CommentsOK'])){?>
          <tr><td style="padding:10px 0 0 10px;">Takk for din kommentar<!--, den kommer snart til å bli publisert.--><td></td>          
          <?php }else{?>
          <tr style="height:25px;">
            <td style="width:120px; text-align:right;">Navn:</td>
            <td style="width:230px"><input type="text" name="txtName" style="width:220px; font-size:10px;" /></td>
          </tr>
          <tr>
            <td style="text-align:right; vertical-align:text-top;">Din kommentar:</td>
            <td><textarea name="comments" style="width:220px; height:50px; font-size:11px;"></textarea></td>
          </tr>
          <tr>
            <td style="width:120px; text-align:right;">Skriv inn kode:</td>
            <td style="width:230px"><img src="recaptcha/captcha.php" style="float:left;" /><br />&nbsp; <input type="text" name="norobot" style="width:100px; font-size:10px;" value=""  /></td>
          </tr>
          <tr style="height:25px;">
          	<td><input type="hidden" name="ProductID" value="<?=$ProductID?>" /></td>
            <td><input type="submit" name="btnSubmit" value="Send" style="width:50px; font-size:9px;" onclick="return VerifyComments();" /></td>
          <!--<tr><td style="text-align:center; font-size:9px;"><?=$obj->picname?></td></tr>-->
          </tr>
          <?php }?>
        </table>
        </form>
        
        
                
        </div>

        
        <!-- <hr class="clear-contentunit" /> -->
      </div>
      <!-- B.3 SUBCONTENT -->
    </div>
    <!-- C. FOOTER AREA -->      
    <?php include("include/footer.php");?>      
  </div> 
</body>
</html>