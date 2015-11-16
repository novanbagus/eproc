<?php
$this->load->library("paketinfo"); $paketInfo = new paketinfo();
$this->load->model("Aanwijzing");
$this->load->model("PaketRekanan");
$this->load->model("PaketTahap");
$this->load->model("PaketDokumen");
$this->load->model("PaketAanwijzingValidasi");
$this->load->model("QrValidasi");
$this->load->library("kauth");  $userLogin = new kauth(); 
include_once("WEB-INF/functions/default.func.php");

$aanwijzing = new Aanwijzing();
$aanwijzing_room = new Aanwijzing();
$paket_aanwijzing_validasi = new PaketAanwijzingValidasi();
$qr_validasi = new QRValidasi();

$reqId = httpFilterRequest("reqId");

$jumlah_aanwijzing = $aanwijzing->getCountByParams(array("PAKET_ID" => $reqId, "AANWIJZING_PARENT_ID" => 0));
if($jumlah_aanwijzing == 0) 
{
	echo '<script language="javascript">';
	echo "alert('Materi aanwijzing belum dibuat.');";
	echo "window.top.location.href = 'main/?pg=ebc1536e4e96c8379aa257db020a8eef&reqId=".$reqId."';";
	echo '</script>';					
	exit();	
}

/* LOGIN CHECK */
if ($userLogin->checkUserLogin()) 
{ 
	$userLogin->retrieveUserInfo();
}

if($userLogin->userLevel == 3)
	$nickname = "Panitia Lelang";
elseif($userLogin->userLevel == 6)
	$nickname = $userLogin->userKodeRekanan;//$userLogin->nama;
else
	$nickname = $userLogin->nama;
	
$paketInfo->getPaket($reqId);

if($userLogin->userLevel == 6)
{
	$paket_dokumen = new PaketDokumen();
	$dokumen_berbayar = $paket_dokumen->getCountByParams(array("STATUS" => 1, "PAKET_ID" => $reqId));
	if($dokumen_berbayar > 0)
	{
		$paket_rekanan = new PaketRekanan();
		if($paket_rekanan->getPaketRekananBayar($reqId, $userLogin->userRekanan) == 0)
		{
			echo '<script language="javascript">';
			echo 'alert("Anda tidak punya hak mengakses halaman ini.\n Silakan bayar terlebih dahulu.");';
			echo 'top.location.href = "index";';
			echo '</script>';
			exit;
		}
	}
	
	if($paketInfo->metode_kualifikasi_id == 1)
	{
		$paket_rekanan_check = new PaketRekanan();
		$paket_rekanan_check->selectByParamsPaketLelang(array("PAKET_ID" =>$reqId, "REKANAN_ID" => $userLogin->userRekanan));
		$paket_rekanan_check->firstRow();
		if($paket_rekanan_check->getField("LULUS_KUALIFIKASI") == 0)
		{
			echo '<script language="javascript">';
			echo 'alert("Anda tidak punya hak mengakses halaman ini.\n Anda telah gagal pada tahap kualifikasi.");';
			echo 'top.location.href = "index";';
			echo '</script>';
			exit;
		}	
	}
	
}

$paket_tahap_metode = new PaketTahap();
$paket_tahap = new PaketTahap();
$arrAanwijzing = array(0, 10, 5, 10, 5, 9, 5, 10, 10);

$jenis_tahap = $paket_tahap_metode->getJenisTahapById($reqId);
$check = $paket_tahap->getCountByParams(array("URUT" => $arrAanwijzing[$jenis_tahap], "PAKET_ID" => $reqId), " AND SYSDATE BETWEEN TANGGAL_AWAL AND TANGGAL_AKHIR ");
if((int)$check == 0)
{}
else
{
	
	$paket_rekanan = new PaketRekanan();
	$reqPaketRekananId = $paket_rekanan->getPaketRekananId($reqId, $userLogin->userRekanan);
	$paket_rekanan->setField("FIELD", "AANWIJZING");
	$paket_rekanan->setField("FIELD_VALUE", 1);
	$paket_rekanan->setField("PAKET_REKANAN_ID", $reqPaketRekananId);
	$paket_rekanan->update();
}

$paket_aanwijzing_validasi->selectByParamsValidasi(array("NIP" => $userLogin->UID, "A.PAKET_ID" => $reqId));
$paket_aanwijzing_validasi->firstRow();

if($userLogin->userLevel == 6)
{
	if($paket_aanwijzing_validasi->getField("KODE") == "")
	{
		$paket_aanwijzing_validasi->setField("PAKET_ID", $reqId);
		$paket_aanwijzing_validasi->setField("USER_LOGIN_ID", $userLogin->UID);
		$paket_aanwijzing_validasi->setField("KODE", $userLogin->UID);
		$paket_aanwijzing_validasi->setField("JENIS", "REKANAN");
		$paket_aanwijzing_validasi->insert();
	}
}

$aanwijzing_publish = new Aanwijzing();
$aanwijzing_publish->selectByParams(array("PAKET_ID" => $reqId));
$aanwijzing_publish->firstRow();
				  
if($userLogin->userLevel == 3)
{
	$kode_qr = generateZero($paketInfo->unit_kerja_id, 3).generateZero($reqId, 6);
	$qr_validasi->selectByParams(array("KODE_QR" => $kode_qr, "SUMBER" => "DOKUMEN_AANWIJZING"));
	$qr_validasi->firstRow();
	
	if($qr_validasi->getField("KODE_QR") == "")
	{
		$qr_validasi->setField("SUMBER", "DOKUMEN_AANWIJZING");
		$qr_validasi->setField("KODE_QR", $kode_qr);
		$qr_validasi->setField("PAKET_ID", $reqId);
		$qr_validasi->setField("INFORMASI", "DOKUMEN BERITA ACARA AANWIJZING\n\n".$aanwijzing_publish->getField("NAMA")."\n\n".strtoupper($paketInfo->nama));
		$qr_validasi->insert();
	}
}

?>
<script type="text/javascript" src="WEB-INF/lib/chatboxfb/js/jquery-1.9.0.min.js"></script>

<script type="text/javascript">

function hiddenBodyScroll()
{
	document.body.style.overflow = "hidden";
}
</script>

<style type="text/css">

.arrowsidemenu{
	width: 180px; /*width of menu*/
	border-style: solid solid none solid;
	border-color: #c1c1c1;
	border-size: 1px;
	border-width: 1px;
}
	
.arrowsidemenu div a{ /*header bar links*/
	font: bold 12px Verdana, Arial, Helvetica, sans-serif;
	display: block;
	background: transparent url("WEB-INF/base-main/DDAccordionMenu/bg-row.png") 100% 0;
  height: 24px; /*Set to height of bg image-padding within link (ie: 32px - 4px - 4px)*/
	padding: 4px 0 4px 10px;
	line-height: 24px; /*Set line-height of bg image-padding within link (ie: 32px - 4px - 4px)*/
	text-decoration: none;
}
	
.arrowsidemenu div a:link, .arrowsidemenu div a:visited{
	color: #26370A;
}

.arrowsidemenu div a:hover{
	background-position: 100% -32px;
}

.arrowsidemenu div.unselected a{ /*header that's currently not selected*/
	color: #6F3700;
}

	
.arrowsidemenu div.selected a{ /*header that's currently selected*/
	color: blue;
	background-position: 100% -64px !important;
}

.arrowsidemenu ul{
	list-style-type: none;
	margin: 0;
	padding: 0;
}

.arrowsidemenu ul li{
	border-bottom: 1px solid #e1dfdf;
}


.arrowsidemenu ul li a{ /*sub menu links*/
	display: block;
	font: normal 12px Verdana, Arial, Helvetica, sans-serif;
	text-decoration: none;
	color: black;
	padding: 5px 0;
	padding-left: 10px;
	border-left: 10px double #e1dfdf;
}

.arrowsidemenu ul li a:hover{
	background: #d5e5c1;
}

</style>  
<style>
h2#pfc_title{ color:#0086a9; padding-bottom:7px;}
.klik-buku a span:nth-child(1){
	font-family: 'Open SansRegular';
}
.klik-buku a span:nth-child(2){
	
}
.klik-buku a span:nth-child(3){
	
}
.loader {
	position: fixed;
	left: 0px;
	top: 0px;
	width: 100%;
	height: 100%;
	z-index: 9999;
	background: url('WEB-INF/base-main/images/page-loader.gif') 50% 50% no-repeat rgb(249,249,249);
}

</style>  

<script type="text/javascript">

	$(window).load(function() {
		$(".loader").fadeOut("slow");
		timeoutMsgID = setTimeout(refresh, 100);
	})
	
	var count = 0;
	var files = '';
	var lastTime = 0;		

	function prepare(response) {
	  var d = new Date();
	  count++;
	  d.setTime(response.time*1000);
	  var mytime = (d.getHours() < 10 ? '0' + d.getHours() : d.getHours()) +':'+ (d.getMinutes() < 10 ? '0' + d.getMinutes() : d.getMinutes()) + ':' + (d.getSeconds() < 10 ? '0' + d.getSeconds() : d.getSeconds());
	  var string = '<div class="shoutbox-list" id="list-'+count+'">'
		  + '<span class="shoutbox-list-time">'+response.waktu+'</span>'
		  + '<span class="shoutbox-list-nick">'+response.nickname+':</span>'
		  + '<span class="shoutbox-list-message">'+response.message+'</span>'
		  +'</div>';
	  return string;
	}
				
	function refresh() {
		
		clearTimeout(timeoutMsgID);
        $.getJSON(files+"daddy-shoutbox.php?reqKode=0&reqId=<?=$reqId?>&action=view&time="+lastTime, function(json) {
            if(json.length) {
              for(i=0; i < json.length; i++) {
				$('.frameBuku').contents().find('.frameShoutbox').contents().find('#daddy-shoutbox-list-global').prepend(prepare(json[i]));				  
              }
              var j = i-1;
              lastTime = json[j].time;
            }
        });
		
		<?
		if($userLogin->userLevel == 3)
		{}
		else
		{
		?>
		$.getJSON('../json/aanwijzing_publish_json.php?reqId=<?=$reqId?>', function (json) 
		{
		   if(json.length) {
			if(json[0].PUBLISH == '1')
			{
				$("#btnCetak").css("display", "");	
			}
		  }
		});	
		<?
		}
		?>	
        timeoutMsgID = setTimeout(refresh, 5000);
		  		
		
	}
	
	<?
	if($userLogin->userLevel == 3)
	{
	?>
	function publishAanwijzing()
	{
		$.getJSON('../json/aanwijzing_publish_validasi_json.php?reqId=<?=$reqId?>',
		function(dataJson){
			if(dataJson.PESAN == "1")
			{
				if(confirm("Publish berita acara aanwijzing ?"))
				{
					$.getJSON('../json/setPublishAanwijzing.php?reqId=<?=$reqId?>', function (data) 
					{
						$.each(data, function (i, SingleElement) {
							$("#btnPublish").css("display", "none");
						});
					});				
				}	
			}
			else
				alert(dataJson.PESAN);
					 
		});			
		
	}
	<?
	}
	?>

	<?
	if($userLogin->userLevel == 3 || $userLogin->userLevel == 9)
	{
	?>
	function submitValidasi(kode, jenis)
	{
		if(confirm("Validasi berita acara aanwijzing ?"))
		{
			$.getJSON('../json/aanwijzing_validasi_json.php?reqId=<?=$reqId?>&reqKode='+kode+'&reqJenis='+jenis,
			function(data){
			  alert(data.PESAN);								
			  $("#tombolValidasi").css("display", "none");			
			});		
		}
	}
	<?
	}
	?>
		
	function disableF5(e) { if ((e.which || e.keyCode) == 116) e.preventDefault(); };
		$(document).ready(function(){
		$(document).on("keydown", disableF5);
	});

</script>

<div class="loader"></div>
<!-- BOOK PREVIEW -->
<link rel="stylesheet" type="text/css" href="WEB-INF/lib/BookPreview/css/normalize.css" />
<link rel="stylesheet" type="text/css" href="WEB-INF/lib/BookPreview/css/demo.css" />
<link rel="stylesheet" type="text/css" href="WEB-INF/lib/BookPreview/css/bookblock.css" />
<link rel="stylesheet" type="text/css" href="WEB-INF/lib/BookPreview/css/component.css" />
<script src="WEB-INF/lib/BookPreview/js/modernizr.custom.js"></script>

<div style="position: relative; float: left; height: auto;">
</div>
<div style="position:relative; clear:both; height: auto;">
    <!-- Main Konten -->
    <div style="color:#666; position:relative; margin-top: 10px; margin-left:12px; width:926px; background-color:#FFF;">
        <div id="kontenn" style="height:auto; overflow:hidden;">                
        
            <!--Konten Kiri Start-->
            <div id="side-kiri2">
              <table width="100%" border="0" cellpadding="2" cellspacing="2">
                <tr>
                  <td width="20%">&nbsp;</td>
                  <td width="29%">&nbsp;</td>
                  <td width="17%" align="right">&nbsp;</td>
                  <td width="17%" align="right">&nbsp;</td>
                  <td width="17%" align="right">&nbsp;</td>
                </tr>
                <tr>
                  <td colspan="5" class="judul-halaman">Aanwijzing [ <?=$userLogin->nama?> ]</td>
                </tr>
                <tr>
                <?
                $link_paket_lelang = $link_home.'main/?pg=paket_lelang&reqMode=view';
                $link_paket_lelang_detil = $link_home.'main/?pg=ebc1536e4e96c8379aa257db020a8eef&reqId='.$reqId;
                ?>
                  <td colspan="5">
                    <div id="nav">
                        <ul>
                            <!--<li>Current onedha</li>-->
                            <li>Aanwijzing</li>
                            <li style="width:142px;"><a href="<?=$link_paket_lelang_detil?>"><span>Paket Lelang Detil</span></a></li>
                            <li style="width:125px;"><a href="<?=$_SESSION['sesBackToPaketLelangParams']?>"><span>Paket Lelang</span></a></li>
                            <li style="width:95px;"><a href="<?=$link_home?>"><span>Home</span></a></li>
                        </ul>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td colspan="5">&nbsp;</td>
                </tr>
                <tr>
                  <td valign="top" colspan="5">
                <!-- ########################################################################### -->
                
                <div id="scroll-wrap" class="container" style="margin-top:1px; width:886px;">
                    <div class="main">
                        <div id="bookshelf" class="bookshelf">
                            <?
                            if($jumlah_aanwijzing == 1)
                            {
                            ?>
                            <figure>
                                <div class="book" data-book="book-1" style="margin-bottom:-260px;"></div>
                                <div class="buttons klik-buku" onClick="hiddenBodyScroll();">
                                    <a href="#" id="bukaBuku1">
                                        &nbsp;
                                    </a>
                                    <a href="#">Details</a>
                                </div>
                                <div class="details">&nbsp;</div>
                            </figure>
                            <?
                            }
                            else
                            {
                                $buku_ke = 2;
                                for($i=1;$i<=$jumlah_aanwijzing;$i++)
                                {
                            ?>
                                    <figure>
                                        <div class="book" data-book="book-<?=$buku_ke?>" style="margin-bottom:-260px;"></div>
                                        <div class="buttons klik-buku" onClick="hiddenBodyScroll();">
                                            <a href="#" id="bukaBuku<?=$i?>">
                                                &nbsp;
                                            </a>
                                            <a href="#">Details</a>
                                        </div>
                                        <div class="details">&nbsp;</div>
                                    </figure>
                            <?
                                    $buku_ke++;
                                    
                                }
                            }
                            ?>
                            <figure>
                                <div class="book" data-book="book-16" style="margin-bottom:-260px;"></div>
                                <div class="buttons klik-buku" onClick="hiddenBodyScroll();">
                                    <a href="#" id="bukaPanduan">
                                        &nbsp;
                                    </a>
                                    <a href="#">Details</a>
                                </div>
                                <div class="details">&nbsp;</div>
                            </figure>                            
                        </div>
                    </div><!-- /main -->
                </div><!-- /container -->
        
                <!-- Fullscreen BookBlock -->
                <?
                if($jumlah_aanwijzing == 1)
                {
                ?>                        
                    <div class="bb-custom-wrapper" id="book-1">
                        <div class="bb-bookblock">
                            <div class="bb-item" style="height:100%; min-height:100%">
                                <iframe id="buku-frame" class="frameBuku" src="aanwijzing_buku.php?reqJumlahBuku=1&reqKode=1&reqId=<?=$reqId?>"></iframe>
                            </div>
                        </div><!-- /bb-bookblock -->
                        <nav>
                            <a href="#" class="bb-nav-prev">Previous</a>
                            <a href="#" class="bb-nav-next">Next</a>
                            <a href="#" class="bb-nav-close">Close</a>
                        </nav>
                    </div><!-- /bb-custom-wrapper -->
                <?
                }
                else
                {
                    $buku_ke = 2;
                    for($i=1;$i<=$jumlah_aanwijzing;$i++)
                    {
                ?>                        
                        <div class="bb-custom-wrapper" id="book-<?=$buku_ke?>">
                            <div class="bb-bookblock">
                                <div class="bb-item" style="height:100%; min-height:100%">
                                    <iframe id="buku-frame" class="frameBuku" src="aanwijzing_buku.php?reqJumlahBuku=<?=$jumlah_aanwijzing?>&reqKode=<?=$i?>&reqId=<?=$reqId?>"></iframe>
                                </div>
                            </div>
                            <nav>
                                <a href="#" class="bb-nav-prev">Previous</a>
                                <a href="#" class="bb-nav-next">Next</a>
                                <a href="#" class="bb-nav-close">Close</a>
                            </nav>
                        </div>
                <?
                        $buku_ke++;
                    }
                }
                ?>          
						<div class="bb-custom-wrapper" id="book-16">
                        <div class="bb-bookblock">
                            <div class="bb-item" style="height:100%; min-height:100%">
                                <iframe id="buku-frame" src="aanwijzing_buku_panduan.php"></iframe>
                            </div>
                        </div><!-- /bb-bookblock -->
                        <nav>
                            <a href="#" class="bb-nav-prev">Previous</a>
                            <a href="#" class="bb-nav-next">Next</a>
                            <a href="#" class="bb-nav-close">Close</a>
                        </nav>
                    </div>                              
                <script src="WEB-INF/lib/BookPreview/js/bookblock.min.js"></script>
                <script src="WEB-INF/lib/BookPreview/js/classie.js"></script>
                <script src="WEB-INF/lib/BookPreview/js/bookshelf.js"></script>
                
                  </td>
                </tr>
                <tr>
                  <td colspan="5">&nbsp;</td>
                </tr>
                <tr>
                  <td>
                      <a href="main/?pg=ebc1536e4e96c8379aa257db020a8eef&reqId=<?=$reqId?>"><img src="WEB-INF/base-main/images/button/kembali.png" alt="" width="101" height="36" border="0" /></a>
                  </td>
                  <td colspan="4" align="right">
                  <?
				  if($userLogin->userLevel == 3 || $userLogin->userLevel == 9)
				  {
					  if($paket_aanwijzing_validasi->getField("KODE") == "")
					  {
					  ?>
					  <a href="#" id="tombolValidasi" onclick="submitValidasi('<?=$paket_aanwijzing_validasi->getField("NIP")?>', '<?=$paket_aanwijzing_validasi->getField("JENIS")?>')"><img src="WEB-INF/base-main/images/button/validasi.png" height="36"/></a>
					  <?
					  }
				  }
				  ?>
                  <?				  
                  if($userLogin->userLevel == 3)
				  {
					  if($aanwijzing_publish->getField("PUBLISH") == 0)
					  {
						  if($paketInfo->user_login_id == $userLogin->UID)
						  {
				  ?>
			                  <a href="#" onClick="publishAanwijzing();" id="btnPublish"><img src="WEB-INF/base-main/images/button/publish.png" alt="" width="101" height="36" border="0" /></a>
                  <?
						  }
					  }
				  ?>
	                  <a href="aanwijzing_cetak.php?reqId=<?=$reqId?>" target="_blank"><img src="WEB-INF/base-main/images/button/cetak.png" alt="" width="101" height="36" border="0" /></a>
                  <?
				  }
				  else
				  {
					  if($aanwijzing_publish->getField("PUBLISH") == 0)
					  	$style = "style='display:none'";
				  ?>
	                  <a href="aanwijzing_cetak.php?reqId=<?=$reqId?>" target="_blank" <?=$style?> id="btnCetak" ><img src="WEB-INF/base-main/images/button/cetak.png" alt="" width="101" height="36" border="0" /></a>                  
                  <?
				  }
				  ?>
                  </td>
                </tr>
                </table>
                

                </div>
            <!-- Konten Kiri End -->
            
            </div> <!-- END kontenn / ikut dimasing2 konten -->
        </div> <!-- END div style="color:#666;  / ikut dimasing2 konten -->
    </div> <!--  div awal yg ikut dimasing2 konten -->
            