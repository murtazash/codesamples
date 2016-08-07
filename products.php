<?php
	session_start();
	include("lib/opencon-mysqli.php");
	include("lib/variables.php");
	include("lib/functions.php");	
	include("lib/actions.php");	

	$strLinks = "";
	$CategoryID = 0 ;
	$Where = "";
	$Order = "ORDER BY P.products_id DESC";
	if(isset($_REQUEST['cboSort']))
		$cboSort = $db->real_escape_string($_REQUEST['cboSort']);
	else
		$cboSort = 1;
	if(isset($_REQUEST['CategoryID']))
	{
		if (is_numeric($_REQUEST['CategoryID']))
			$CategoryID = $db->real_escape_string($_REQUEST['CategoryID']);
		else
		{
			$CategoryID = 0;
			die("Error: 421");
		}
		$AllCats = AllSubCatsiPrepare($CategoryID,$db); //
		$Where = " AND PTC.categories_id IN ($AllCats) ";
		if($cboSort == 2)
			$Order = "ORDER BY P.products_price";
		else if($cboSort == 3)
			$Order = "ORDER BY P.products_price DESC";
		$strLinks .= "&CategoryID=".$CategoryID."&cboSort=$cboSort";
		$Heading = UCString(GetValuei("categories_name","categories_description","categories_id = $CategoryID",$db));
	}
	else if(isset($_REQUEST['btnSearch']))
	{
		$strLinks .= "&btnSearch&SearchText=".$db->real_escape_string($_REQUEST['SearchText']);
		$Where = " AND PD.products_name LIKE ? OR PD.suppliercode = ?";
		$Heading = "Søke resultat for '".$db->real_escape_string($_REQUEST['SearchText'])."'";
		$param1 = "%".$db->real_escape_string($_REQUEST['SearchText'])."%";
		$param2 = $db->real_escape_string($_REQUEST['SearchText']);
	}
	else if(isset($_REQUEST['New']))
	{
		$strLinks .= "&New";
		$Order = "ORDER BY P.products_date_added DESC";
		$Heading = "Nyheter";
		
	}
	else if(isset($_REQUEST['Sale']))
	{
		$strLinks .= "&Sale";
		$Where = " AND P.products_oldprice > 0";
		$Order = "ORDER BY P.products_date_added DESC";
		$Heading = "Salg";
		
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
  <meta name="author" content="<?=$a1?>" />
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
  <meta http-equiv="Content-Script-Type" content="text/javascript" /> 
  <meta http-equiv="cache-control" content="no-cache" />
  <meta name="robots" content="index,follow" />
  <meta name="description" content="<?php print($Heading." - ".strCompany)?>" />
  <meta name="description" content="Your page description here ..." />
  <meta name="keywords" content="Your keywords, keywords, keywords, here ..." />
  <link rel="stylesheet" type="text/css" media="screen,projection,print" href="./css/mf4_layout4_setup.css" />
  <link rel="stylesheet" type="text/css" media="screen,projection,print" href="./css/mf4_layout4_text.css" />
  <link rel="icon" type="image/x-icon" href="./img/favicon.ico" />
	<script type="text/javascript" src="highslide/highslide-with-gallery.js"></script>
  <script type="text/javascript" src="highslide/highslide.config.js" charset="utf-8"></script>
  <link rel="stylesheet" type="text/css" href="highslide/highslide.css" />
  <script type="text/javascript" src="js/functions.js"></script>
  <!--[if lt IE 7]>
  <link rel="stylesheet" type="text/css" href="highslide/highslide-ie6.css" />
  <![endif]-->  
  <title><?php print($Heading." - ".strCompany)?></title>
  <script type="text/javascript" src="js/functions.js"></script>
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
      <div class="main-content" style="width:760px;">
        <!-- Pagetitle -->
        
				<?php
        $Query = "SELECT DISTINCT P.products_id, P.products_price, P.products_oldprice, PD.products_name, PD.products_description, PD.pictype, PD.suppliercode
          FROM products P 
          INNER JOIN products_description PD ON P.products_id = PD.products_id
          INNER JOIN products_to_categories PTC ON P.products_id = PTC.products_id
          WHERE 1 ".$Where." ".$Order;
        //echo $Query; die;
        ?>
					
          <div style="position:absolute; margin-left:530px;">
          <?php if(!isset($_REQUEST['AllBest']) && isset($_REQUEST['CategoryID'])){?>
          <form name="Sort">
          Sorter etter
            <select name="cboSort" class="cboSort" onchange="document.Sort.submit();">
              <option value="1">Dato</option>
              <option value="2">Pris (lav til høy)</option>
              <option value="3">Pris (høy til lav)</option>
            </select>
            <script type="text/javascript">document.Sort.cboSort.value = <?=$cboSort?>;</script>
            <input type="hidden" name="CategoryID" value="<?=$_REQUEST['CategoryID']?>" />
          </form>
          <?php }?>
          </div>
        <h1 class="pagetitle"><?=$Heading?></h1>
        <?php
        /* Paging Class */
        if (isset($_REQUEST['page']))
          $Page = $db->real_escape_string($_REQUEST['page']);
        else
          $Page = 1;
        require_once("lib/pagingi-prepare.php");
        $strNext  = "<IMG HEIGHT=\"16\" SRC=\"img/right_arrow.gif\" WIDTH=\"16\" BORDER=\"0\">";
        $strPrev  = "<IMG HEIGHT=\"16\" SRC=\"img/left_arrow.gif\"  WIDTH=\"16\" BORDER=\"0\">";
				$stmt = $db->prepare($Query);
				if(isset($_REQUEST['btnSearch']))
					$stmt->bind_param("ss",$param1,$param2);
				$stmt->execute();
				$stmt->store_result();
				$Records = $stmt->num_rows;

				$Paging = new Paging($Page,12,10,$Query,$strLinks,"Paging",$strNext,$strPrev,"",$db,$Records);
					$stmt = $db->prepare($Paging->GetQuery());
					if(isset($_REQUEST['btnSearch']))
						$stmt->bind_param("ss",$param1,$param2);
					$stmt->execute();
					$stmt->store_result();

        if($stmt->num_rows > 0)
        {
          $Index = 0;
					?>
					<table width="725">
            <tr>
              <td valign="top" align="center">
              <table border="0" cellspacing="0" cellpadding="0" style="vertical-align:middle">
                <tr>
          <?php
					$stmt->bind_result($products_id, $products_price, $products_oldprice, $products_name, $products_description, $pictype, $suppliercode);
          while($stmt->fetch())
          {
            $Index++;
					?>
              	<td valign="top" align="left" class="box-border" width="230">
                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                  <tr><td align="left" class="box-head"><?=$products_name?></td></tr>
                  <tr><td height="5"></td></tr>
                  <tr>
                  	<td height="136">
                      <a href="images/products/<?=$products_id.".".$pictype?>" class="highslide" onclick="return hs.expand(this)">
	                      <img src="image.php?image=/images/products/<?=$products_id.".".$pictype?>&width=215&height=275" alt="" style="border:1px solid #c2c2c2;" class="center" />
                      </a>
                  	</td>
                  </tr>
                  <tr><td height="8"></td></tr>
                  <?php if($products_description != ""){?><tr><td align="left" class="smal-txt"><?php print(substr(stripslashes($products_description),0,300)); echo (strlen($products_description) > 300)? "...": "";?></td></tr><?php }?>
                  <tr><td height="5"></td></tr>
                  <?php if($products_price > 0){?>
                  <tr>
                  	<td align="center">
                    <table border="0" align="center" cellpadding="0" cellspacing="0" width="200">
                      <tr align="left">
                        <td width="100" class="price"><?php if($products_oldprice > 0){?><span style="font-size:10px; font-weight:normal; text-decoration:line-through; color:#ff0000;">Kr. <?=$products_oldprice?></span><br /><?php }?>Kr <?=$products_price?></td>
                        <td style="background:#d9d9d9; width:1px;"></td>
                        <td width="18"></td>
                        <td width="90"><div class="buy2"><a href="product.php?ProductID=<?=$products_id?>" class="buylink">Les mer</a></div></td>
                      </tr>
                      <tr align="center"><td colspan="4" style="height:20px;"><div style="font-size:9px; color:#444444; font-weight:bold; text-align:center;">Varenr: <?=$suppliercode?></div></td></tr>
                    </table>
                    </td>
                  </tr>                  
                  <tr><td height="3"></td></tr>
                  <?php }?>
                </table>
                </td>
          <?php	
								if($Index % 3 != 0)
									Print("<td width=\"30\"></td>");
								else
								{
					?>
              </tr>
            </table>
            </td>
          </tr>
          <tr>        
					<tr><td>&nbsp;</td></tr>
					<tr>
            <td valign="top" align="center">
            <table border="0" cellspacing="0" cellpadding="0">
              <tr>
          <?php 
								}
					}
					while($Index%3 != 0)
					{
						$Index++;
						Print("<td width=\"220\">&nbsp;</td><td width=\"30\"></td>");
					}
					
					?>
              </tr>
            </table>
            </td>
          </tr>
					<tr><td height="25"><hr /></td></tr>
          <tr><td><?php print("<div class=\"paging\" id=\"paging\">Sider : ".$Paging->GetPages()."</div>");?></td></tr>
        </table>          
          <?php
				}
				else
					echo ("<br>No Product found.")
				
        ?>
      </div>
      <!-- B.3 SUBCONTENT -->
    </div>
    <!-- C. FOOTER AREA -->      
    <?php include("include/footer.php");?>      
  </div> 
</body>
</html>