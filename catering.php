<?php
//warungbot for dapuralena

define('BOT_TOKEN', 'bottoken'); 

define('API_URL', 'https://api.telegram.org/bot'.BOT_TOKEN.'/'); //pake https
define('myVERSI','0.02');

$debug = false;

function exec_curl_request($handle) {
  $response = curl_exec($handle);

  if ($response === false) {
    $errno = curl_errno($handle);
    $error = curl_error($handle);
    error_log("Curl returned error $errno: $error\n");
    curl_close($handle);
    return false;
  }

  $http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
  curl_close($handle);

  if ($http_code >= 500) {
    // do not wat to DDOS server if something goes wrong
    sleep(10);
    return false;
  } else if ($http_code != 200) {
    $response = json_decode($response, true);
    error_log("Request has failed with error {$response['error_code']}: {$response['description']}\n");
    if ($http_code == 401) {
      throw new Exception('Invalid access token provided');
    }
    return false;
  } else {
    $response = json_decode($response, true);
    if (isset($response['description'])) {
      error_log("Request was successfull: {$response['description']}\n");
    }
    $response = $response['result'];
  }

  return $response;
}

function apiRequest($method, $parameters=null) {
  if (!is_string($method)) {
    error_log("Method name must be a string\n");
    return false;
  }

  if (!$parameters) {
    $parameters = array();
  } else if (!is_array($parameters)) {
    error_log("Parameters must be an array\n");
    return false;
  }

  foreach ($parameters as $key => &$val) {
    // encoding to JSON array parameters, for example reply_markup
    if (!is_numeric($val) && !is_string($val)) {
      $val = json_encode($val);
    }
  }
  $url = API_URL.$method.'?'.http_build_query($parameters);

  $handle = curl_init($url);
  curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
  curl_setopt($handle, CURLOPT_TIMEOUT, 60);
  curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

  return exec_curl_request($handle);
}

function apiRequestJson($method, $parameters) {
  if (!is_string($method)) {
    error_log("Method name must be a string\n");
    return false;
  }

  if (!$parameters) {
    $parameters = array();
  } else if (!is_array($parameters)) {
    error_log("Parameters must be an array\n");
    return false;
  }

  $parameters["method"] = $method;

  $handle = curl_init(API_URL);
  curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
  curl_setopt($handle, CURLOPT_TIMEOUT, 60);
  curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($parameters));
  curl_setopt($handle, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));

  return exec_curl_request($handle);
}

// jebakan token, klo ga diisi akan mati
if (strlen(BOT_TOKEN)<20) 
    die(PHP_EOL."-> -> Token BOT API nya mohon diisi dengan benar!\n");

function getUpdates($last_id = null){
  $params = [];
  if (!empty($last_id)){
    $params = ['offset' => $last_id+1, 'limit' => 1];
  }
  //echo print_r($params, true);
  return apiRequest('getUpdates', $params);
}

function sendMessage($idpesan, $idchat, $pesan){
  $data = [
    'chat_id'=> $idchat,
    'text' => $pesan,
    "reply_to_message_id" => $idpesan
  ];
  return apiRequest("sendMessage", $data);
}

function processMessage($message) {
  if ( $GLOBALS['debug']) print_r($message);

  if (isset($message["message"])) {
    $sumber   = $message['message'];
    $idpesan  = $sumber['message_id'];
    $idchat   = $sumber['chat']['id'];

    $namamu   = $sumber['from']['first_name'];

    if (isset($sumber['text'])) {
      $pesan  =  $sumber['text'];
      $pecah  = explode(' ', $pesan);
      $katapertama = strtolower($pecah[0]);
      switch ($katapertama) {
        case '/start':
          $text = "Halo kak $namamu.. Welcome to WarungBot milik dapuralena-jogja!";
          break;
		case '/t':		 
		  $text  = "Dapuralena adalah penyedia jasa catering harian/mingguan dan event catering untuk nasibox maupun snackbox \n";
		  $text .= "juga menyediakan warung onthespot: spesial ayam goreng laos dan sop ikan patin/nila n";
		  $text .= "- lokasi  : di jl.tasura no.16 maguwoharjo depok sleman Yogyakarta \n";
		  $text .= "- fb : id.dapuralena \n";
		  $text .= "- line: @zat0850e  \n";
		  $text .= "- instagram @dapuralena \n";
		  $text .= "- telegram @warungbot \n";
		  $text .= "- website: www.dapuralena.com  \n";
		  $text .= "- telp.WA 082136214451 \n";
		  $text .= "Status BUKA setiaphari 08:00 - 21:00 \n";
		  $text .= "Hemat Nikmat Sehat";
          break;
		case '/p':
          $text  = "Pengalaman dapuralena dalam melayani pesanan catering di Boyolali dan jogja sejak tahun 2013, nasibox maupun snack box.
					\n";
          $text .= " FOTO \n";
          break;  
		case '/c':
		 $text = "Paket catering harian :  \n";
		 $text .= "1.paket box standar mulai Rp 12.000  \n";
		 $text .= "2.paket box premium (pakai buah)mulai Rp 16.000 \n"; 
		 $text .= "3.paket rantangan/family (tanpa nasi) mulai Rp 40.000 untuk 3-4 orang  \n";
		 $text .= "#Catering Event mulai dari harga Rp. 500.000 untuk 20 orang. Bisa Nasi box/snack box atau prasmanan. 
			namun untuk custom menu dan harga bisa hubungi 082136214451 \n";
          break; 
		case '/sk':
		 $text  = "Syarat dan Ketentuan yang berlaku untuk pemesanan Catering \n";
		 $text .= "1.Pembayaran dilakukan di awal \n";
		 $text .= "2.Jadwal menu daily catering update tiap hari senin\n";
		 $text .= "3.Delivery harian dilakukan pengiriman jam 10.00 (siang) dan jam 17.00 (sore)\n"; 
		 $text .= "4.Untuk menu by request / min order event catering bisa konfirmasi telp diatas\n";
		 $text .= "5.Harga sewaktu-waktu bisa berubah, menyesuaikan bahan pokok\n";
		 $text .= "6.Free delivery untuk radius 3km area maguwoharjo, diluar itu kena ongkir menyesuaikan jarak\n";  
          break; 
		case '/m':
          $text  = "Menu warung dapuralena :\n";
		  $text .= "paket 1 (nasi ayam laos esteh) Rp.12500 \n";
		  $text .= "paket 2 (nasi ayam geprek esteh) Rp.12500 \n";
		  $text .= "ayam goreng laos 10000 \n";
		  $text .= "ayam geprek 10000 \n";
		  $text .= "sop ikan patin/nila 12000 \n";
		  $text .= "nila goreng Rp.12000 \n"; 
		  $text .= "lele goreng Rp.9000 \n"; 
		  $text .= "telur dadar Rp.3500 \n"; 
		  $text .= "kobis/terong goreng Rp.2000 \n"; 
		  $text .= "nasgor jamur Rp.10000 \n"; 
		  $text .= "nasgor bakso/ayam/tuna/sosis Rp.13000 \n"; 
		  $text .= "bakmi jamur Rp.10000 \n"; 
		  $text .= "bakmi bakso/ayam/tuna/sosis Rp.13000 \n"; 
		  $text .= "nasi putih Rp.2500 \n"; 
		  $text .= "ca kangkung Rp.3000 \n"; 
		  $text .= "ca jamur Rp.3000 \n"; 
		  $text .= "pecel/karedok Rp.4000 \n"; 
		  $text .= "sambal dadak Rp.2000 \n"; 
		  $text .= "sambal telenjeng Rp.1500 \n";
		  $text .= "sambal terasi segar Rp.2000 \n"; 
		  $text .= "sambal bawang/korek Rp.1500 \n"; 
          break;
		case '/k':		  
		  $text = "produk kemasan  siap kirim keseluruh indonesia \n"; 
		 $text .= "- peyek kacang, peyek udang Rp.15000 \n";
		 $text .= "- sambal terasi Rp.25000  \n";
		 $text .= "- sambal teri merah Rp.25000  \n";
		 $text .= "- sambal teri ijo Rp.25000 \n";
          break;

case '/h':		  
		  $text = "Hallo \n"; 
		 $text .= "Chef Ami \n";
          break;
		case '/a':		  
		  $text = "area pengiriman catering harian kami sejauh ini \n";
			$text .= "-maguwoharjo \n";
			$text .= "-condongcatur \n";
			$text .= "-seturan \n";
			$text .= "-demangan \n";
			$text .= "-UNY \n";
			$text .= "-jakal \n";
          break;
        case '/b':
          $text  = "List Command: \n";
          $text  .= "/t - tentang \n";
          $text  .="/p - portofolio \n";
          $text  .="/c - catering \n";
          $text  .="/sk - syarat dan ketentuan \n";
          $text  .= "/m - menu warung \n";
          $text  .="/k - produk kemasan \n";
          $text  .="/h - say hello \n";
          $text  .="/a - area delivery \n";
		  $text  .="/b - bantuan list command \n";
          break;
        default:
          $text = "Makasih pesan sudah diterima kak..";
          break;
      }
    } else {
      $text  = "Wrong way..!";
    }
    
    $hasil = sendMessage($idpesan, $idchat, $text);
    if ( $GLOBALS['debug']) {
      // hanya nampak saat metode poll dan debug = true;
      echo "Pesan yang dikirim: ".$text.PHP_EOL;
      print_r($hasil);
    }
  }    

}

// pencetakan versi dan info waktu server, berfungsi jika test hook
echo "Ver. ".myVERSI." OK Running Joon!".PHP_EOL.date('Y-m-d H:i:s'). PHP_EOL;

function printUpdates($result){
  foreach($result as $obj){
    // echo $obj['message']['text'].PHP_EOL;
    processMessage($obj);
    $last_id = $obj['update_id'];
  }
  return $last_id;
}

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
  // ini jebakan jika ada yang iseng mengirim sesuatu ke hook
  // dan tidak sesuai format JSON harus ditolak!
  exit;
} else {
  // sesuai format JSON, proses pesannya
  processMessage($update);
}



?>