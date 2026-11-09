<?php
ini_set('memory_limit', '-1');
include('conexao.php');

date_default_timezone_set('America/Fortaleza');

$data_atual = date('Y-m-d');
$hora_atual = date('H:i:s');

 


$temp = $data_atual." ".$hora_atual; 

$url = "http://apijogos.com/betsports2.php";

    $page_content = @file_get_contents($url);
    if (!$page_content) {
        // Fallback moderno via SportsImporter integrado
        require_once __DIR__ . '/inc.config.php';
        try {
            $imported = \app\helpers\SportsImporter::fetchFromNodeApi('today', 'vaidebet');
            if (!empty($imported['jogos'])) {
                $res = \app\helpers\SportsImporter::importarJogos($imported['jogos']);
                echo "Jogos importados com sucesso via SportsImporter:\n" . ($res['message'] ?? 'OK');
                exit;
            }
        } catch (\Throwable $e) {
            // Fallback falhou
        }
        die('Não foi possível obter dados da API.');
    }
    $result = json_decode($page_content);
    if (!is_array($result) && !is_object($result)) {
        die('Dados retornados da API são inválidos.');
    }

    try {
        if ($conexao) {
            $conexao->beginTransaction();
        }

foreach($result as $key){
        
        $idjogo = $key->idPartida;
        $campeonato = $key->campeonato;
        $mandante = $key->mandante;
        $visitante = $key->visitante;
        $idjogo = $key->idPartida;
        $tempo = $key->tempo;
        $data = $key->data;
        $hora = $key->hora;
        $bandeira = $key->bandeira;
        $eventid = $key->eventid;

                //Consulata ao banco se o campeonato exite
          $foracara = 1.00; 
$foraempate= 1.00;
$casaempate= 1.00;
$empatecasa= 1.00;
$empatefora= 1.00;
$casafora= 1.00;
$forafora= 1.00;
$empateempate= 1.00;
$casacasa= 1.00;
$fvcm= 1.00;
$cvfm= 1.00;
$fv0= 1.00;
$cv0= 1.00;
$placarimpar= 1.00;
$placarpar= 1.00;
$fora2gm= 1.00;
$fora1gm= 1.00;
$casa2gm= 1.00;
$casa1gm= 1.00;
$gmenos5= 1.00;
$gmenos4= 1.00;
$gmenos3= 1.00;
$gmenos2= 1.00;
$gmais5= 1.00;
$gmais4= 1.00;
$gmais1= 1.00;
$gmais2= 1.00;
$ambnsg= 1.00;
$fmcn= 1.00;
$cmfn= 1.00;
$cof= 1.00;
$dplfora= 1.00;
$dplcasa= 1.00;
$ambn= 1.00;
$valorcasa= 1.00;
$valorempate= 1.00;
$valorfora= 1.00;
$amb= 1.00;
$pc1x0c= 1.00;
$pc2x0c= 1.00;
$pc2x1c= 1.00;
$pc3x0c= 1.00;
$pc3x1c= 1.00;
$pc3x2c= 1.00;

$pc4x0c= 1.00;
$pc4x1c= 1.00;
$pc4x2c= 1.00;
$pc5x0c= 1.00;
$pc5x1c= 1.00;
$pc6x0c= 1.00;
$pc0x0= 1.00;
$pc1x1= 1.00;
$pc2x2= 1.00;
$pc3x3= 1.00;
$pc0x1f= 1.00;
$pc0x2f= 1.00;
$pc0x2f2= 1.00;
$pc1x2f= 1.00;
$pc0x3f= 1.00;
$pc1x3f= 1.00;
$pc2x3f= 1.00;
$pc0x4f= 1.00;
$pc1x4f= 1.00;
$pc2x4f= 1.00;
$pc0x5f= 1.00;
$pc1x5f= 1.00;
$pc0x6f= 1.00;
$casaesim= 1.00;
$casaenao= 1.00;
$foraesim= 1.00;
$foraenao= 1.00;
$empateenao= 1.00;
$empatesim= 1.00;
$casaeemais15= 1.00;
$casaeemais25= 1.00;
$casaeemais35= 1.00;
$casaeemais45= 1.00;
$casaeemenos15= 1.00;
$casaeemenos25= 1.00;
$casaeemenos35= 1.00;
$casaeemenos45= 1.00;
$casaouforae15= 1.00;
$casaouforae25= 1.00;
$casaouforae35= 1.00;
$casaouforae45= 1.00;
$casaouforaemenos15= 1.00;
$casaouforaemenos25= 1.00;
$casaouforaemenos35= 1.00;
$casaouforaemenos45= 1.00;
$foraemais15= 1.00;
$foraemais25= 1.00;
$foraemais35= 1.00;
$foraemais45= 1.00;
$foraemenos15= 1.00;
$foraemenos25= 1.00;
$foraemenos35= 1.00;
$foraemenos45= 1.00;
$casaemenos15= 1.00;
$casaemenos25= 1.00;
$casaemenos35= 1.00;
$casaemenos45= 1.00;
$casaemais15= 1.00;
$casaemais25= 1.00;
$casaemais35= 1.00;
$casaemais45= 1.00;
$empateemenos15= 1.00;
$empateemenos25= 1.00;
$empateemenos35= 1.00;
$empateemenos45= 1.00;
$empateemais15= 1.00;
$empateemais15= 1.00;
$empateemais45= 1.00;
$foraemenos15= 1.00;
$foraemais15= 1.00;
$foraemais25= 1.00;
$foraemais35= 1.00;
$foraemais45= 1.00;
$casacasaemais15= 1.00;
$casacasaemais25= 1.00;
$casacasaemais35= 1.00;
$casacasaemais45= 1.00;
$casacasaemenos15= 1.00;
$casacasaemenos25= 1.00;
$casacasaemenos35= 1.00;
$casacasaemenos45= 1.00;
$casaforaemais15= 1.00;
$casaforaemais25= 1.00;
$casaforaemais35= 1.00;
$casaforaemais45= 1.00;
$casaforaemenos35= 1.00;
$casaforaemenos45= 1.00;
$casaempatemais15= 1.00;
$casaempatemais25= 1.00;
$casaempatemais35= 1.00;
$casaempatemais45= 1.00;
$casaempateemenos15= 1.00;
$casaempateemenos25= 1.00;
$casaempateemenos35= 1.00;
$casaempateemenos45= 1.00;
$foracasaemais15= 1.00;
$foracasaemais25= 1.00;
$foracasaemais35= 1.00;
$foracasaemais45= 1.00;
$foracasaemenos35= 1.00;
$foracasaemenos45= 1.00;
$foraforaemais15= 1.00;
$foraforaemais25= 1.00;
$foraforaemais35= 1.00;
$foraforaemais45= 1.00;
$foraforaemenos15= 1.00;
$foraforaemenos25= 1.00;
$foraforaemenos35= 1.00;
$foraforaemenos45= 1.00;
$foraempateemais15= 1.00;
$foraempateemais25= 1.00;
$foraempateemais35= 1.00;
$foraempateemais45= 1.00;
$foraempateemenos25= 1.00;
$foraempateemenos35= 1.00;
$foraempateemenos45= 1.00;
$empatecasaemais15= 1.00;
$empatecasaemais25= 1.00;
$empatecasaemais35= 1.00;
$empatecasaemais45= 1.00;
$empatecasaemenos15= 1.00;
$empatecasaemenos25= 1.00;
$empatecasaemenos35= 1.00;
$empatecasaemenos45= 1.00;
$empateforaemais15= 1.00;
$empateforaemais25= 1.00;
$empateforaemais35= 1.00;
$empateforaemais45= 1.00;
$empateforaemenos15= 1.00;
$empateforaemenos25= 1.00;
$empateforaemenos35= 1.00;
$empateforaemenos45= 1.00;
$empateempateemais15= 1.00;
$empateempateemais25= 1.00;
$empateempateemais35= 1.00;
$empateempateemais45= 1.00;
$empateempateemenos15= 1.00;
$empateempateemenos25= 1.00;
$empateempateemenos35= 1.00;
$empateempateemenos45= 1.00;
$casaforaesim= 1.00;
$casaforaenao= 1.00;
$casaempateenao= 1.00;
$casaempateesim= 1.00;
$empateforaesim= 1.00;
$empateforaenao= 1.00;
$placarpar1= 1.00;
$placarimpar1= 1.00;
$gmais11= 1.00;
$gmais21= 1.00;
$cof1= 1.00;
$dplfora1= 1.00;
$dplcasa1= 1.00;
$ambn1= 1.00;
$gmais31= 1.00;
$amb1= 1.00;
$fora1= 1.00;
$empate1= 1.00;
$casa1= 1.00;
$gmenos11= 1.00;
$gmenos31= 1.00;
$gmenos21= 1.00;
$pc1x0c1= 1.00;
$pc2x0c1= 1.00;
$pc2x1c1= 1.00;
$pc0x01= 1.00;
$pc1x11= 1.00;
$pc2x21= 1.00;
$pc0x1f1= 1.00;
$pc0x2f1= 1.00;
$pc1x2f1= 1.00;
$placarimpar2= 1.00;
$placarpar2= 1.00;
$gmenos32= 1.00;
$gmenos22= 1.00;
$gmais12= 1.00;
$gmais22= 1.00;
$cof2= 1.00;
$dplfora2= 1.00;
$dplcasa2= 1.00;
$ambn2= 1.00;
$gmais32= 1.00;
$amb2= 1.00;
$fora2= 1.00;
$empate2= 1.00;
$casa2= 1.00;
$gmenos12= 1.00;
$pc1x0c2= 1.00;
$pc2x0c2= 1.00;
$pc2x1c2= 1.00;
$pc0x02= 1.00;
$pc1x12= 1.00;
$pc2x22= 1.00;
$pc0x1f2= 1.00;
$pc0x2f= 1.00;
$pc1x2f2= 1.00;
$empateemais25= 1.00;
$gmais3 = 1.00;   
$escanteios6a8 = 1;
$escanteiosmenos6= 1;
$escanteios9a11= 1;
$escanteios12a14= 1;
$escanteiosmais14= 1;
$escanteiosmais10= 1;
$escanteiosigual10= 1;
$escanteiosmenos10= 1;
$t1escanteiosmais4=1;
$empateanulacasa=1;
$empateanulafora=1;
$golsno9minmenos=1;
$golsno9minmais=1;
$totaldecartaomais45=1;
$totaldecartaomenos45=1;
$cartahccasa=1;
$cartahcempate=1;
$cartahcfora=1;






         $selectOdd = "SELECT valorgrupo FROM sis_dados ";
    $result = $conexao->prepare($selectOdd);     
    $result->execute();

         while($oddsRes = $result->FETCH(PDO::FETCH_OBJ)){ 
             $valorgrupo = $oddsRes->valorgrupo;
         }
        $arr = array();
                              $q = 0; 
        foreach($key->odd as $odd){

                                    //
                                    $posG = $odd->posG;
                                    $grupoAposta = $odd->grupoAposta;
                                    $betv = $odd->taxa;
                                    $betvs = $odd->odds; 
                                    $nome = $odd->nome;
                                    $taxa = number_format($betv, 2);
                                    $taxa = $taxa;
                                        $taxa2 = $taxa;
                                    $taxas = $betvs;
                                    $pos = $odd->ordem;
                                    
                                    
$tempos2 =  date('Y-m-d H:i:s');
if($nome == "1T Ambas Sim"){
        $amb1 = $taxa;
        }
if($nome == "1T Ambas Nao"){
        $ambn1 = $taxa;
        }
if($nome == "2T Ambas Sim"){
        $amb2 = $taxa;
        }
if($nome == "2T Ambas Nao"){
        $ambn2 = $taxa;
        }
        
if($nome =="1T Casa"){
        $casa2 = $taxa*0.92;
         if($casa2>$valorgrupo){
            $casa2 =$valorgrupo;
        }
        }
if($nome =="1T Empate"){
        $empate2 = $taxa*0.92;
         if($empate2>$valorgrupo){
            $empate2 =$valorgrupo;
        }
        }
if($nome =="1T Fora"){
        $fora2 = $taxa*0.92;
         if($fora2>$valorgrupo){
            $fora2 =$valorgrupo;
        }
        }
 if($nome =="2T +0.5"){
        $gmais12 = $taxa;
        }
   if($nome =="2T +1.5"){
        $gmais22 = $taxa;
        }
     if($nome =="2T +2.5"){
        $gmais32 = $taxa;
        }
     if($nome =="2T -0.5"){
        $gmenos12 = $taxa;
        }
    if($nome =="2T -1.5"){
        $gmenos22 = $taxa;
        }
   if($nome =="2T -2.5"){
        $gmenos32 = $taxa;
        }
          if($nome =="Casa Ganha e Ambas"){
        $casaesim = $taxa;
        }
          if($nome =="Fora Ganha e Ambas"){
        $foraesim = $taxa;
        }
        if($nome =="Casa Ganha e Nao Amb"){
        $casaenao = $taxa;
        }
           if($nome =="Fora Ganha e Nao Amb"){
        $foraenao = $taxa;
        }
         if($nome =="Empate e Nao Amb"){
        $empateenao = $taxa;
        }
          if($nome =="Empate e Ambas"){
        $empatesim = $taxa;
        }
        
        //dupla hipo
          if($nome =="Casa ou Empate"){
        $casaeemais15 = $taxa*1.44;
         if($casaeemais15>$valorgrupo){
            $casaeemais15 =$valorgrupo;
        }
        }
        if($nome =="Casa ou Empate"){
        $casaeemais25 = $taxa*1.54;
         if($casaeemais25>$valorgrupo){
            $casaeemais25 =$valorgrupo;
        }
        }
          if($nome =="Casa ou Empate"){
        $casaeemais35 = $taxa*1.64;
         if($casaeemais35>$valorgrupo){
            $casaeemais35 =$valorgrupo;
        }
        }
          if($nome =="Casa ou Empate"){
        $casaeemais45 = $taxa*1.74;
         if($casaeemais45>$valorgrupo){
            $casaeemais45 =$valorgrupo;
        }
        } 
         if($nome =="Casa ou Empate"){
        $casaeemenos15 = $taxa*1.66;
         if($casaeemenos15>$valorgrupo){
            $casaeemenos15 =$valorgrupo;
        }
        } 
         if($nome =="Casa ou Empate"){
        $casaeemenos25 = $taxa*1.44;
         if($casaeemenos25>$valorgrupo){
            $casaeemenos25 =$valorgrupo;
        }
        } 
         if($nome =="Casa ou Empate"){
        $casaeemenos35 = $taxa*1.33;
         if($casaeemenos35>$valorgrupo){
            $casaeemenos35 =$valorgrupo;
        }
        }
          if($nome =="Casa ou Empate"){
        $casaeemenos45 = $taxa*1.22;
         if($casaeemenos45>$valorgrupo){
            $casaeemenos45 =$valorgrupo;
        }
        }
          if($nome =="Casa ou Fora"){
        $casaouforae15 = $taxa*1.44;
         if($casaouforae15>$valorgrupo){
            $casaouforae15 =$valorgrupo;
        }
        } 
         if($nome =="Casa ou Fora"){
        $casaouforae25 = $taxa*1.54;
         if($casaouforae25>$valorgrupo){
            $casaouforae25 =$valorgrupo;
        }
        }
         if($nome =="Casa ou Fora"){
        $casaouforae35 = $taxa*1.64;
         if($casaouforae35>$valorgrupo){
            $casaouforae35 =$valorgrupo;
        }
        }
          if($nome =="Casa ou Fora"){
         $casaouforae45 = $taxa*1.74;
          if($casaouforae45>$valorgrupo){
            $casaouforae45 =$valorgrupo;
        }
        }
          if($nome =="Casa ou Fora"){
        $casaouforaemenos15 = $taxa*1.64;
         if($casaouforaemenos15>$valorgrupo){
            $casaouforaemenos15 =$valorgrupo;
        }
        }
          if($nome =="Casa ou Fora"){
        $casaouforaemenos25 = $taxa*1.55;
         if($casaouforaemenos25>$valorgrupo){
            $casaouforaemenos25 =$valorgrupo;
        }
        }
         if($nome =="Casa ou Fora"){
        $casaouforaemenos35 = $taxa*1.44;
         if($casaouforaemenos35>$valorgrupo){
            $casaouforaemenos35 =$valorgrupo;
        }
        }
          if($nome =="Casa ou Fora"){
        $casaouforaemenos45 = $taxa*1.33;
         if($casaouforaemenos45>$valorgrupo){
            $casaouforaemenos45 =$valorgrupo;
        }
        }
          if($nome =="Empate ou Fora"){
        $foraemais15 = $taxa*1.44;
         if($foraemais15>$valorgrupo){
            $foraemais15 =$valorgrupo;
        }
        }
          if($nome =="Empate ou Fora"){
        $foraemais25 = $taxa*1.54;
         if($foraemais25>$valorgrupo){
            $foraemais25 =$valorgrupo;
        }
        }
          if($nome =="Empate ou Fora"){
        $foraemais35 = $taxa*1.65;
         if($foraemais35>$valorgrupo){
            $foraemais35 =$valorgrupo;
        }
        }
          if($nome =="Empate ou Fora"){
        $foraemais45 = $taxa*1.75;
         if($foraemais45>$valorgrupo){
            $foraemais45 =$valorgrupo;
        }
        }
          if($nome =="Empate ou Fora"){
        $foraemenos15 = $taxa*1.45;
         if($foraemenos15>$valorgrupo){
            $foraemenos15 =$valorgrupo;
        }
        }
          if($nome =="Empate ou Fora"){
        $foraemenos25 = $taxa*1.33;
         if($foraemenos25>$valorgrupo){
            $foraemenos25 =$valorgrupo;
        }
        }
          if($nome =="Empate ou Fora"){
        $foraemenos35 = $taxa*1.25;
         if($foraemenos35>$valorgrupo){
            $foraemenos35 =$valorgrupo;
        }
        }
          if($nome =="Empate ou Fora"){
        $foraemenos45 = $taxa*1.17;
         if($foraemenos45>$valorgrupo){
            $foraemenos45 =$valorgrupo;
        }
        }
        
        



if($nome =="CASA"){
        $valorcasa = $taxa2;
         if($valorcasa>$valorgrupo){
            $valorcasa =$valorgrupo;
        }
       }
    if($nome =="EMPATE"){
      $valorempate = $taxa2;
       if($valorempate>$valorgrupo){
            $valorempate =$valorgrupo;
        }
       }
    if($nome =="FORA"){
        $valorfora = $taxa2;
         if($valorfora>$valorgrupo){
            $valorfora =$valorgrupo;
        }
       }
    if($nome =="mais 2.5"){
        $valorgmais3 = $taxa;
       }  
    if($nome =="Fora-Empate"){
        $foraempate = $taxa;
       }  
    if($nome =="Casa-Empate"){
        $casaempate = $taxa;
       }
    if($nome =="Fora-Casa"){
        $foracara = $taxa;
       }
        if($nome =="Empate-Casa"){
        $empatecasa = $taxa;
       }
    if($nome =="Empate-Fora"){
        $empatefora = $taxa;
       }
        if($nome =="Casa-Fora"){
        $casafora = $taxa;
       }
        if($nome =="Fora-Fora"){
        $forafora = $taxa;
       }
        if($nome =="Empate-Empate"){
        $empateempate = $taxa;
       }
        if($nome =="Casa-Casa"){
        $casacasa = $taxa;
       }
        
        if($nome =="FORA"){
        $fvcm = $taxa*1.77;
         if($fvcm>$valorgrupo){
            $fvcm =$valorgrupo;
        }
       }
        
        if($nome =="CASA"){
        $cvfm = $taxa*1.77;
         if($cvfm>$valorgrupo){
            $cvfm =$valorgrupo;
        }
       }
        
        if($nome =="FORA"){
        $fv0 = $taxa*1.66;
         if($fv0>$valorgrupo){
            $fv0 =$valorgrupo;
        }
       }
        
        if($nome =="CASA"){
        $cv0 = $taxa*1.66;
         if($cv0>$valorgrupo){
            $cv0 =$valorgrupo;
        }
       }
        
        if($nome =="FORA"){
        $fora2gm = $taxa*1.89;
         if($fora2gm>$valorgrupo){
            $fora2gm =$valorgrupo;
        }
       }
        
        if($nome =="FORA"){
        $fora1gm = $taxa*1.44;
         if($fora1gm>$valorgrupo){
            $fora1gm =$valorgrupo;
        }
       }
        
        
        if($nome =="CASA"){
        $casa2gm = $taxa*1.89;
         if($casa2gm>$valorgrupo){
            $casa2gm =$valorgrupo;
        }
       }
        
        
        if($nome =="CASA"){
        $casa1gm = $taxa*1.44;
         if($casa1gm>$valorgrupo){
            $casa1gm =$valorgrupo;
        }
        
        }
        
        if($nome =="Gols -4.5"){
        $gmenos5 = $taxa;
       }
        
        if($nome =="Gols -3.5"){
        $gmenos4 = $taxa;
       }
        
        
        if($nome =="Gols -2.5"){
        $gmenos3 = $taxa;
       }
        
        if($nome =="Gols -1.5"){
        $gmenos2 = $taxa;
       }
        
        if($nome =="Gols -0.5"){
        $gmenos1 = $taxa;
       }
        
        
        
        if($nome =="Gols +4.5"){
        $gmais5 = $taxa;
       }
        
        
        if($nome =="Gols +3.5"){
        $gmais4 = $taxa;
       }
        
        if($nome =="Gols +2.5"){
        $gmais3 = $taxa;
       }
        
        if($nome =="Gols +1.5"){
        $gmais2 = $taxa;
       }
        
        if($nome =="Gols +0.5"){
        $gmais1 = $taxa;
       }
        
        
        if($nome =="EMPATE E SIM"){
        $ambnsg = $taxa;
       }
        
        
        
        if($nome =="QUAL TIME MARCARA? SO TIME DE FORA"){
        $fmcn = $taxa;
       }
        
        if($nome =="QUAL TIME MARCARA? SO TIME DE CASA"){
        $cmfn = $taxa;
       }
        
        
        
        if($nome =="Casa ou Fora"){
        $cof = $taxa;
       }
        if($nome =="Empate ou Fora"){
        $dplfora = $taxa;
       }
        if($nome =="Casa ou Empate"){
        $dplcasa = $taxa;
       }
        
        
        
        if($nome =="Ambas Nao"){
        $ambn = $taxa;
       }
    
       if($nome =="Ambas Sim"){
        $amb = $taxa;
       }
       
       
       
       
       
       if($nome =="Casa 1x0"){
        $pc1x0c = $taxa;
       }
        
        
        
        if($nome =="Casa 2x0"){
        $pc2x0c = $taxa;
       }
        
        
        if($nome =="Casa 2x0"){
        $pc2x1c = $taxa;
       }
        
        
        if($nome =="Casa 3x0"){
        $pc3x0c = $taxa;
       }
        
        if($nome =="Casa 3x1"){
        $pc3x1c = $taxa;
       }
        
        
        if($nome =="Casa 3x2"){
        $pc3x2c = $taxa;
       }
        
        if($nome =="Casa 4x0"){
        $pc4x0c = $taxa;
       }
        
        if($nome =="Casa 4x1"){
        $pc4x1c = $taxa;
       }
        
        if($nome =="Casa 4x2"){
        $pc4x2c = $taxa;
       }
        
        if($nome =="Casa 5x0"){
        $pc5x0c = $taxa;
       }
        
        if($nome =="Casa 5x1"){
        $pc5x1c = $taxa;
       }
        
        
        if($nome =="Casa 6x0"){
        $pc6x0c = $taxa;
       }
        
        
        
        if($nome =="Empate 0x0"){
        $pc0x0 = $taxa;
       }
        
        
        if($nome =="Empate 1x1"){
        $pc1x1 = $taxa;
       }
        
        if($nome =="Empate 2x2"){
        $pc2x2 = $taxa;
       }
        
        if($nome =="Empate 3x3"){
        $pc3x3 = $taxa;
       }
        
        
        if($nome =="Fora 1x0"){
        $pc0x1f = $taxa;
       }
        
        if($nome =="Fora 2x0"){
        $pc0x2f = $taxa;
       }
        
        if($nome =="Fora 2x1"){
        $pc1x2f = $taxa;
       }
        
        if($nome =="Fora 3x0"){
        $pc0x3f = $taxa;
       }
       
        
        if($nome =="Fora 3x2"){
        $pc2x3f = $taxa;
       }
         if($nome =="Fora 4x2"){
        $pc2x4f = $taxa;
       }
         if($nome =="Fora 4x1"){
        $pc1x4f = $taxa;
       }
         if($nome =="Fora 5x0"){
        $pc0x5f = $taxa;
       }
         if($nome =="Fora 4x0"){
        $pc0x4f = $taxa;
       }
        if($nome =="Fora 6x0"){
        $pc0x6f = $taxa;
       }
         if($nome =="Fora 5x1"){
        $pc1x5f = $taxa;
       }
        if($nome =="Fora 3x1"){
        $pc1x3f = $taxa;
       }
        if($nome =="Fora 3x2"){
        $pc3x2f = $taxa;
       }
        
        if($nome =="Gols Impar"){
        $placarimpar1 = $taxa*0.97;
         if($placarimpar1>$valorgrupo){
            $placarimpar1 =$valorgrupo;
        }
       }
        
        if($nome =="Gols Par"){
        $placarpar1 = $taxa*0.97;
         if($placarpar1>$valorgrupo){
            $placarpar1 =$valorgrupo;
        }
       }
        
        
        if($nome =="1T Gols +1.5"){
        $gmais21 = $taxa;
       }
        
        
        if($nome =="1T Fora"){
        $fora1 = $taxa;
       }
        
        
        
        if($nome =="1T Empate"){
        $empate1 = $taxa;
       }
        
        
        if($nome =="1T Casa"){
        $casa1 = $taxa;
       }
        
        
        if($nome =="1T Casa 1x0"){
        $pc1x0c1 = $taxa;
       }
        
        if($nome =="1T Casa 2x0"){
        $pc2x0c1 = $taxa;
       }
        
        if($nome =="1T Casa 2x1"){
        $pc2x1c1 = $taxa;
       }
        
        if($nome =="1T Empate 0x0"){
        $pc0x01 = $taxa;
       }
        
        if($nome =="1T Empate 1x1"){
        $pc1x11 = $taxa;
       }
        
        if($nome =="1T Empate 2x2"){
        $pc2x21 = $taxa;
       }
        
        if($nome =="1T Fora 1x0"){
        $pc0x1f1 = $taxa;
       }
        
        if($nome =="1T Fora 2x0"){
        $pc0x2f1 = $taxa;
       }
        
        if($nome =="1T Fora 2x1"){
        $pc1x2f1 = $taxa;
       }
        
        
        
        if($nome =="1T Casa 1x0"){
        $pc1x0c2 = $taxa*0.96;
         if($pc1x0c2>$valorgrupo){
            $pc1x0c2 =$valorgrupo;
        }
       }
        
        
        if($nome =="1T Casa 2x0"){
        $pc2x0c2 = $taxa*0.96;
         if($pc2x0c2>$valorgrupo){
            $pc2x0c2 =$valorgrupo;
        }
       }
        
        
        if($nome =="1T Casa 2x1"){
        $pc2x1c2 = $taxa*0.96;
         if($pc2x1c2>$valorgrupo){
            $pc2x1c2 =$valorgrupo;
        }
       }
        
        
        if($nome =="1T Empate 0x0"){
        $pc0x02 = $taxa*0.96;
         if($pc0x02>$valorgrupo){
            $pc0x02 =$valorgrupo;
        }
       }
        
        
        if($nome =="1T Empate 1x1"){
        $pc1x12 = $taxa*0.96;
         if($pc1x12>$valorgrupo){
            $pc1x12 =$valorgrupo;
        }
       }
        
        
        if($nome =="1T Empate 2x2"){
        $pc2x22 = $taxa*0.96;
         if($pc2x22>$valorgrupo){
            $pc2x22 =$valorgrupo;
        }
       }
        
        
        if($nome =="1T Fora 1x0"){
        $pc0x1f2 = $taxa*0.96;
         if($pc0x1f2>$valorgrupo){
            $pc0x1f2 =$valorgrupo;
        }
       }
        
        if($nome =="1T Fora 2x0"){
        $pc0x2f2 = $taxa*0.96;
         if($pc0x2f2>$valorgrupo){
            $pc0x2f2 =$valorgrupo;
        }
       }
        
        if($nome =="1T Fora 2x1"){
        $pc1x2f2 = $taxa*0.96;
         if($pc1x2f2>$valorgrupo){
            $pc1x2f2 =$valorgrupo;
        }
       }
        
        
        
         if($nome =="Gols Impar"){
        $placarimpar = $taxa;
       }
         if($nome =="Gols Par"){
        $placarpar = $taxa;
       }
        
         if($nome =="1T Casa ou Empate"){
        $dplcasa1 = $taxa;
       }
        if($nome =="1T Empate ou Fora"){
        $dplfora1 = $taxa;
       }
        if($nome =="1T Casa ou Fora"){
        $cof1 = $taxa;
       }
        
        
        if($nome =="2T Ambas Sim"){
        $amb2 = $taxa;
       }
        if($nome =="2T Ambas Nao"){
        $ambn2 = $taxa;
       }
        if($nome =="1T Casa ou Empate"){
        $dplcasa2 = $taxa*0.94;
         if($dplcasa2>$valorgrupo){
            $dplcasa2 =$valorgrupo;
        }
       }
        if($nome =="1T Empate ou Fora"){
        $dplfora2 = $taxa*0.94;
         if($dplfora2>$valorgrupo){
            $dplfora2 =$valorgrupo;
        }
       }
        if($nome =="1T Casa ou Fora"){
        $cof2 = $taxa*0.94;
         if($cof2>$valorgrupo){
            $cof2 =$valorgrupo;
        }
       }
        
        
    if($nome =="Gols Impar"){
        $placarimpar2 = $taxa*0.94;
         if($placarimpar2>$valorgrupo){
            $placarimpar2 =$valorgrupo;
        }
       }
     if($nome =="Gols Par"){
        $placarpar2 = $taxa*0.94;
         if($placarpar2>$valorgrupo){
            $placarpar2 =$valorgrupo;
        }
       }
        
        
    if($nome =="1T Gols +0.5"){
        $gmais11 = $taxa;
       }
    if($nome =="1T Gols +1.5"){
        $gmais21 = $taxa;
       }
     if($nome =="1T Gols +2.5"){
        $gmais31 = $taxa;
       }
     if($nome =="1T Gols -0.5"){
        $gmenos11 = $taxa;
       }
     if($nome =="1T Gols -1.5"){
        $gmenos21 = $taxa;
       }
    if($nome =="1T Gols -2.5"){
        $gmenos31 = $taxa;
       }
        
        
        
        
        
         if($nome =="Casa e -2.5"){
        $casaemenos15 = $taxa*1.04;
         if($casaemenos15>$valorgrupo){
            $casaemenos15 =$valorgrupo;
        }
       }
        if($nome =="Casa e -2.5"){
        $casaemenos25 = $taxa;
       }
        if($nome =="Casa e -2.5"){
        $casaemenos35 = $taxa*0.85;
         if($casaemenos35>$valorgrupo){
            $casaemenos35 =$valorgrupo;
        }
       }
        if($nome =="Casa e -2.5"){
        $casaemenos45 = $taxa*0.65;
         if($casaemenos45>$valorgrupo){
            $casaemenos45 =$valorgrupo;
        }
       }
        
        if($nome =="Casa e +2.5"){
        $casaemais15 = $taxa*0.86;
         if($casaemais15>$valorgrupo){
            $casaemais15 =$valorgrupo;
        }
       }
        if($nome =="Casa e +2.5"){
        $casaemais25 = $taxa;
       }
        if($nome =="Casa e +2.5"){
        $casaemais35 = $taxa*1.04;
         if($casaemais35>$valorgrupo){
            $casaemais35 =$valorgrupo;
        }
       }
        if($nome =="Casa e +2.5"){
        $casaemais45 = $taxa*1.11;
         if($casaemais45>$valorgrupo){
            $casaemais45 =$valorgrupo;
        }
       }
        
        if($nome =="Empate e -2.5"){
        $empateemenos15 = $taxa*1.08;
         if($empateemenos15>$valorgrupo){
            $empateemenos15 =$valorgrupo;
        }
       }
        if($nome =="Empate e -2.5"){
        $empateemenos25 = $taxa;
       }
        if($nome =="Empate e -2.5"){
        $empateemenos35 = $taxa*0.85;
         if($empateemenos35>$valorgrupo){
            $empateemenos35 =$valorgrupo;
        }
       }
        if($nome =="Empate e -2.5"){
        $empateemenos45 = $taxa*0.65;
         if($empateemenos45>$valorgrupo){
            $empateemenos45 =$valorgrupo;
        }
       }
        
      if($nome =="Empate e +2.5"){
        $empateemais15 = $taxa*0.88;
         if($empateemais15>$valorgrupo){
            $empateemais15 =$valorgrupo;
        }
       }
        if($nome =="Empate e +2.5"){
        $empateemais25 = $taxa;
       }
        if($nome =="Empate e +2.5"){
        $empateemais35 = $taxa*1.04;
         if($empateemais35>$valorgrupo){
            $empateemais35 =$valorgrupo;
        }
       }
        if($nome =="Empate e +2.5"){
        $empateemais45 = $taxa*1.11;
         if($empateemais45>$valorgrupo){
            $empateemais45 =$valorgrupo;
        }
       }
        
         
        
        if($nome =="Fora e +2.5"){
        $foraemais15 = $taxa*0.88;
         if($foraemais15>$valorgrupo){
            $foraemais15 =$valorgrupo;
        }
       }
        if($nome =="Fora e +2.5"){
        $foraemais25 = $taxa;
       }
        if($nome =="Fora e +2.5"){
        $foraemais35 = $taxa*1.04;
         if($foraemais35>$valorgrupo){
            $foraemais35 =$valorgrupo;
        }
       }
        if($nome =="Fora e +2.5"){
        $foraemais45 = $taxa*1.11;
         if($foraemais45>$valorgrupo){
            $foraemais45 =$valorgrupo;
        }
       }
     
        if($nome =="Casa e +2.5"){
        $casacasaemais15 = $taxa*1.11;
         if($casacasaemais15>$valorgrupo){
            $casacasaemais15 =$valorgrupo;
        }
       }
        if($nome =="Casa e +2.5"){
        $casacasaemais25 = $taxa*1.18;
         if($casacasaemais25>$valorgrupo){
            $casacasaemais25 =$valorgrupo;
        }
       }
        if($nome =="Casa e +2.5"){
        $casacasaemais35 = $taxa*1.22;
         if($casacasaemais35>$valorgrupo){
            $casacasaemais35 =$valorgrupo;
        }
       }
        if($nome =="Casa e +2.5"){
        $casacasaemais45 = $taxa*1.26;
         if($casacasaemais45>$valorgrupo){
            $casacasaemais45 =$valorgrupo;
        }
       }
        
          if($nome =="Casa e -2.5"){
        $casacasaemenos15 = $taxa*0.85;
         if($casacasaemenos15>$valorgrupo){
            $casacasaemenos15 =$valorgrupo;
        }
       }
        if($nome =="Casa e -2.5"){
        $casacasaemenos25 = $taxa*0.70;
         if($casacasaemenos25>$valorgrupo){
            $casacasaemenos25 =$valorgrupo;
        }
       }
        if($nome =="Casa e -2.5"){
        $casacasaemenos35 = $taxa*0.60;
         if($casacasaemenos35>$valorgrupo){
            $casacasaemenos35 =$valorgrupo;
        }
       }
        if($nome =="Casa e -2.5"){
        $casacasaemenos45 = $taxa*0.45;
         if($casacasaemenos45>$valorgrupo){
            $casacasaemenos45 =$valorgrupo;
        }
       }
        
        if($nome =="Casa e +2.5"){
        $casaforaemais15 = $taxa*1.43;
         if($casaforaemais15>$valorgrupo){
            $casaforaemais15 =$valorgrupo;
        }
       }
        if($nome =="Casa e +2.5"){
        $casaforaemais25 = $taxa*1.53;
         if($casaforaemais25>$valorgrupo){
            $casaforaemais25 =$valorgrupo;
        }
       }
        if($nome =="Casa e +2.5"){
        $casaforaemais35 = $taxa*1.66;
         if($casaforaemais35>$valorgrupo){
            $casaforaemais35 =$valorgrupo;
        }
       }
        if($nome =="Casa e +2.5"){
        $casaforaemais45 = $taxa*1.72;
         if($casaforaemais45>$valorgrupo){
            $casaforaemais45 =$valorgrupo;
        }
       }
        
        if($nome =="Casa e -2.5"){
        $casaforaemenos35 = $taxa*1.43;
         if($casaforaemenos35>$valorgrupo){
            $casaforaemenos35 =$valorgrupo;
        }
       }
        if($nome =="Casa e -2.5"){
        $casaforaemenos45 = $taxa*1.33;
         if($casaforaemenos45>$valorgrupo){
            $casaforaemenos45 =$valorgrupo;
        }
       }
        
        if($nome =="Empate e +2.5"){
        $casaempatemais15 = $taxa*1.44;
         if($casaempatemais15>$valorgrupo){
            $casaempatemais15 =$valorgrupo;
        }
       }
         if($nome =="Empate e +2.5"){
        $casaempatemais25 = $taxa*1.55;
         if($casaempatemais25>$valorgrupo){
            $casaempatemais25 =$valorgrupo;
        }
       }
         if($nome =="Empate e +2.5"){
        $casaempatemais35 = $taxa*1.59;
         if($casaempatemais35>$valorgrupo){
            $casaempatemais35 =$valorgrupo;
        }
       }
         if($nome =="Empate e +2.5"){
        $casaempatemais45 = $taxa*1.63;
         if($casaempatemais45>$valorgrupo){
            $casaempatemais45 =$valorgrupo;
        }
       }
        
        
         if($nome =="Empate e -2.5"){
        $casaempateemenos15 = $taxa*1.66;
         if($casaempateemenos15>$valorgrupo){
            $casaempateemenos15 =$valorgrupo;
        }
       }
         if($nome =="Empate e -2.5"){
        $casaempateemenos25 = $taxa*1.55;
         if($casaempateemenos25>$valorgrupo){
            $casaempateemenos25 =$valorgrupo;
        }
       }
         if($nome =="Empate e -2.5"){
        $casaempateemenos35 = $taxa*1.49;
         if($casaempateemenos35>$valorgrupo){
            $casaempateemenos35 =$valorgrupo;
        }
       }
         if($nome =="Empate e -2.5"){
        $casaempateemenos45 = $taxa*1.44;
         if($casaempateemenos45>$valorgrupo){
            $casaempateemenos45 =$valorgrupo;
        }
       }
        
        if($nome =="Fora e +2.5"){
        $foracasaemais15 = $taxa*1.33;
         if($foracasaemais15>$valorgrupo){
            $foracasaemais15 =$valorgrupo;
        }
       }
         if($nome =="Fora e +2.5"){
        $foracasaemais25 = $taxa*1.43;
         if($foracasaemais25>$valorgrupo){
            $foracasaemais25 =$valorgrupo;
        }
       }
         if($nome =="Fora e +2.5"){
        $foracasaemais35 = $taxa*1.55;
         if($foracasaemais35>$valorgrupo){
            $foracasaemais35 =$valorgrupo;
        }
       }
         if($nome =="Fora e +2.5"){
        $foracasaemais45 = $taxa*1.64;
         if($foracasaemais45>$valorgrupo){
            $foracasaemais45 =$valorgrupo;
        }
       }
        
         if($nome =="Fora e -2.5"){
        $foracasaemenos35 = $taxa*1.44;
         if($foracasaemenos35>$valorgrupo){
            $foracasaemenos35 =$valorgrupo;
        }
       }
         if($nome =="Fora e -2.5"){
        $foracasaemenos45 = $taxa*1.33;
         if($foracasaemenos45>$valorgrupo){
            $foracasaemenos45 =$valorgrupo;
        }
       }
        
        
          if($nome =="Fora e +2.5"){
        $foraforaemais15 = $taxa*1.44;
         if($foraforaemais15>$valorgrupo){
            $foraforaemais15 =$valorgrupo;
        }
       }
        if($nome =="Fora e +2.5"){
        $foraforaemais25 = $taxa*1.54;
         if($foraforaemais25>$valorgrupo){
            $foraforaemais25 =$valorgrupo;
        }
       }
        if($nome =="Fora e +2.5"){
        $foraforaemais35 = $taxa*1.64;
         if($foraforaemais35>$valorgrupo){
            $foraforaemais35 =$valorgrupo;
        }
       }
        if($nome =="Fora e +2.5"){
        $foraforaemais45 = $taxa*1.84;
         if($foraforaemais45>$valorgrupo){
            $foraforaemais45 =$valorgrupo;
        }
       }
        
        if($nome =="Fora e -2.5"){
        $foraforaemenos15 = $taxa*1.77;
         if($foraforaemenos15>$valorgrupo){
            $foraforaemenos15 =$valorgrupo;
        }
       }
        if($nome =="Fora e -2.5"){
        $foraforaemenos25 = $taxa*1.67;
         if($foraforaemenos25>$valorgrupo){
            $foraforaemenos25 =$valorgrupo;
        }
       }
        if($nome =="Fora e -2.5"){
        $foraforaemenos35 = $taxa*1.57;
         if($foraforaemenos35>$valorgrupo){
            $foraforaemenos35 =$valorgrupo;
        }
       }
        if($nome =="Fora e -2.5"){
        $foraforaemenos45 = $taxa*1.47;
         if($foraforaemenos45>$valorgrupo){
            $foraforaemenos45 =$valorgrupo;
        }
       }
        
        if($nome =="Fora e +2.5"){
        $foraempateemais15 = $taxa*1.99;
         if($foraempateemais15>$valorgrupo){
            $foraempateemais15 =$valorgrupo;
        }
       }
        if($nome =="Fora e +2.5"){
        $foraempateemais25 = $taxa*2.19;
         if($foraempateemais25>$valorgrupo){
            $foraempateemais25 =$valorgrupo;
        }
       }
        if($nome =="Fora e +2.5"){
        $foraempateemais35 = $taxa*2.28;
         if($foraempateemais35>$valorgrupo){
            $foraempateemais35 =$valorgrupo;
        }
       }
        if($nome =="Fora e +2.5"){
        $foraempateemais45 = $taxa*2.44;
         if($foraempateemais45>$valorgrupo){
            $foraempateemais45 =$valorgrupo;
        }
       }
       
        if($nome =="Fora e -2.5"){
        $foraempateemenos25 = $taxa*2;
         if($foraempateemenos25>$valorgrupo){
            $foraempateemenos25 =$valorgrupo;
        }
       }
        if($nome =="Fora e -2.5"){
        $foraempateemenos35 = $taxa*1.88;
         if($foraempateemenos35>$valorgrupo){
            $foraempateemenos35 =$valorgrupo;
        }
       }
        if($nome =="Fora e -2.5"){
        $foraempateemenos45 = $taxa*1.77;
         if($foraempateemenos45>$valorgrupo){
            $foraempateemenos45 =$valorgrupo;
        }
       }
        
        if($nome =="Empate e +2.5"){
        $empatecasaemais15 = $taxa*2.00;
         if($empatecasaemais15>$valorgrupo){
            $empatecasaemais15 =$valorgrupo;
        }
       }
         if($nome =="Empate e +2.5"){
        $empatecasaemais25 = $taxa*2.22;
         if($empatecasaemais25>$valorgrupo){
            $empatecasaemais25 =$valorgrupo;
        }
       }
         if($nome =="Empate e +2.5"){
        $empatecasaemais35 = $taxa*2.30;
         if($empatecasaemais35>$valorgrupo){
            $empatecasaemais35 =$valorgrupo;
        }
       }
         if($nome =="Empate e +2.5+"){
        $empatecasaemais45 = $taxa*2.40;
         if($empatecasaemais45>$valorgrupo){
            $empatecasaemais45 =$valorgrupo;
        }
       }
        
        if($nome =="Empate e -2.5"){
        $empatecasaemenos15 = $taxa*2.66;
         if($empatecasaemenos15>$valorgrupo){
            $empatecasaemenos15 =$valorgrupo;
        }
       }
         if($nome =="Empate e -2.5"){
        $empatecasaemenos25 = $taxa*2.56;
         if($empatecasaemenos25>$valorgrupo){
            $empatecasaemenos25 =$valorgrupo;
        }
       }
         if($nome =="Empate e -2.5"){
        $empatecasaemenos35 = $taxa*2.46;
         if($empatecasaemenos35>$valorgrupo){
            $empatecasaemenos35 =$valorgrupo;
        }
       }
         if($nome =="Empate e -2.5"){
        $empatecasaemenos45 = $taxa*2.36;
         if($empatecasaemenos45>$valorgrupo){
            $empatecasaemenos45 =$valorgrupo;
        }
       }
        
        
        if($nome =="Empate e +2.5"){
        $empateforaemais15 = $taxa*2.66;
         if($empateforaemais15>$valorgrupo){
            $empateforaemais15 =$valorgrupo;
        }
       }
        if($nome =="Empate e +2.5"){
        $empateforaemais25 = $taxa*2.76;
         if($empateforaemais25>$valorgrupo){
            $empateforaemais25 =$valorgrupo;
        }
       }
        if($nome =="Empate e +2.5"){
        $empateforaemais35 = $taxa*2.88;
         if($empateforaemais35>$valorgrupo){
            $empateforaemais35 =$valorgrupo;
        }
       }
        if($nome =="Empate e +2.5"){
        $empateforaemais45 = $taxa*2.99;
         if($empateforaemais45>$valorgrupo){
            $empateforaemais45 =$valorgrupo;
        }
       }
        
          if($nome =="Empate e -2.5"){
        $empateforaemenos15 = $taxa*2.99;
         if($empateforaemenos15>$valorgrupo){
            $empateforaemenos15 =$valorgrupo;
        }
       }
        if($nome =="Empate e -2.5"){
        $empateforaemenos25 = $taxa*2.89;
         if($empateforaemenos25>$valorgrupo){
            $empateforaemenos25 =$valorgrupo;
        }
       }
        if($nome =="Empate e -2.5"){
        $empateforaemenos35 = $taxa*2.79;
         if($empateforaemenos35>$valorgrupo){
            $empateforaemenos35 =$valorgrupo;
        }
       }
        if($nome =="Empate e -2.5"){
        $empateforaemenos45 = $taxa*2.59;
         if($empateforaemenos45>$valorgrupo){
            $empateforaemenos45 =$valorgrupo;
        }
       }
        
        if($nome =="Empate e +2.5"){
        $empateempateemais15 = $taxa*3.00;
         if($empateempateemais15>$valorgrupo){
            $empateempateemais15 =$valorgrupo;
        }
       }
        if($nome =="Empate e +2.5"){
        $empateempateemais25 = $taxa*3.22;
         if($empateempateemais25>$valorgrupo){
            $empateempateemais25 =$valorgrupo;
        }
       }
        if($nome =="Empate e +2.5"){
        $empateempateemais35 = $taxa*3.36;
         if($empateempateemais35>$valorgrupo){
            $empateempateemais35 =$valorgrupo;
        }
       }
        if($nome =="Empate e +2.5"){
        $empateempateemais45 = $taxa*3.44;
         if($empateempateemais45>$valorgrupo){
            $empateempateemais45 =$valorgrupo;
        }
       }
        
        if($nome =="Empate e -2.5"){
        $empateempateemenos15 = $taxa*3.44;
         if($empateempateemenos15>$valorgrupo){
            $empateempateemenos15 =$valorgrupo;
        }
       }
        if($nome =="Empate e -2.5"){
        $empateempateemenos25 = $taxa*3.33;
         if($empateempateemenos25>$valorgrupo){
            $empateempateemenos25 =$valorgrupo;
        }
       }
        if($nome =="Empate e -2.5"){
        $empateempateemenos35 = $taxa*3.22;
         if($empateempateemenos35>$valorgrupo){
            $empateempateemenos35 =$valorgrupo;
        }
       }
        if($nome =="Empate e -2.5"){
        $empateempateemenos45 = $taxa*3.11;
         if($empateempateemenos45>$valorgrupo){
            $empateempateemenos45 =$valorgrupo;
        }
       }
        
         if($nome =="Casa ou Fora"){
        $casaforaenao = $taxa*1.77;
          if($casaforaenao>$valorgrupo){
            $casaforaenao =$valorgrupo;
        }
       }
         if($nome =="Casa ou Fora"){
        $casaforaesim = $taxa*1.67;
        if($casaforaesim>$valorgrupo){
            $casaforaesim =$valorgrupo;
        }
       }
        
        if($nome =="Casa ou Empate"){
        $casaempateenao = $taxa*1.77;
         if($casaempateenao>$valorgrupo){
            $casaempateenao =$valorgrupo;
        }
       }
         if($nome =="Casa ou Empate"){
        $casaempateesim = $taxa*1.67;
        if($casaempateesim>$valorgrupo){
            $casaempateesim =$valorgrupo;
        }
       }
        
         if($nome =="Empate ou Fora"){
        $empateforaenao = $taxa*1.76;
        if($empateforaenao>$valorgrupo){
            $empateforaenao =$valorgrupo;
        }
       }
         if($nome =="Empate ou Fora"){
        $empateforaesim = $taxa*1.66;
        if($empateforaesim>$valorgrupo){
            $empateforaesim =$valorgrupo;
        }
       }
       
       
       
       
       
       
       
        if($nome =="Escanteios 6-8"){
        $escanteios6a8 = $taxa;
        if($escanteios6a8>$valorgrupo){
            $escanteios6a8 =$valorgrupo;
        }
       }
        if($nome =="Escanteios -6"){
        $escanteiosmenos6 = $taxa;
        if($escanteiosmenos6>$valorgrupo){
            $escanteiosmenos6 =$valorgrupo;
        }
       }
        if($nome =="Escanteios 9-11"){
        $escanteios9a11 = $taxa;
        if($escanteios9a11>$valorgrupo){
            $escanteios9a11 =$valorgrupo;
        }
       }
       
        if($nome =="Escanteios 12-14"){
        $escanteios12a14 = $taxa;
        if($escanteios12a14>$valorgrupo){
            $escanteios12a14 =$valorgrupo;
        }
       }
         if($nome =="Escanteios +14"){
        $escanteiosmais14 = $taxa;
        if($escanteiosmais14>$valorgrupo){
            $escanteiosmais14 =$valorgrupo;
        }
       }
       
         if($nome =="Escanteios +10"){
        $escanteiosmais10 = $taxa;
        if($escanteiosmais10>$valorgrupo){
            $escanteiosmais10 =$valorgrupo;
        }
       }
         if($nome =="Escanteios =10"){
        $escanteiosigual10 = $taxa;
        if($escanteiosigual10>$valorgrupo){
            $escanteiosigual10 =$valorgrupo;
        }
       }
         if($nome =="Escanteios -10"){
        $escanteiosmenos10 = $taxa;
        if($escanteiosmenos10>$valorgrupo){
            $escanteiosmenos10 =$valorgrupo;
        }
       }
       
        if($nome =="1T Escanteio +4.5"){
        $t1escanteiosmais4 = $taxa;
        if($t1escanteiosmais4>$valorgrupo){
            $t1escanteiosmais4 =$valorgrupo;
        }
       }
       
         if($nome =="Gols 00:00 - 09:59 +0.5"){
        $golsno9minmais = $taxa;
        if($golsno9minmais>$valorgrupo){
            $golsno9minmais =$valorgrupo;
        }
       }
       
       if($nome =="Gols 00:00 - 09:59 -0.5"){
        $golsno9minmenos = $taxa;
        if($golsno9minmenos>$valorgrupo){
            $golsno9minmenos =$valorgrupo;
        }
       }
       
       
         if($nome =="Empate anula aposta casa"){
        $empateanulacasa = $taxa;
        if($empateanulacasa>$valorgrupo){
            $empateanulacasa =$valorgrupo;
        }
       }
       
         if($nome =="Empate anula aposta fora"){
        $empateanulafora = $taxa;
        if($empateanulafora>$valorgrupo){
            $empateanulafora =$valorgrupo;
        }
       }
       
        if($nome =="Total de cartoes +4.5"){
        $totaldecartaomais45 = $taxa;
        if($totaldecartaomais45>$valorgrupo){
            $totaldecartaomais45 =$valorgrupo;
        }
       }
       
        if($nome =="Total de cartoes -4.5"){
        $totaldecartaomenos45 = $taxa;
        if($totaldecartaomenos45>$valorgrupo){
            $totaldecartaomenos45 =$valorgrupo;
        }
       }
       if($nome =="Cartao HC Casa 0"){
        $cartahccasa = $taxa;
        if($cartahccasa>$valorgrupo){
            $cartahccasa =$valorgrupo;
        }
       }
       
       if($nome =="Cartao HC Empate 0"){
        $cartahcempate = $taxa;
        if($cartahcempate>$valorgrupo){
            $cartahcempate =$valorgrupo;
        }
       }
       
       if($nome =="Cartao HC Fora 0"){
        $cartahcfora = $taxa;
        if($cartahcfora>$valorgrupo){
            $cartahcfora =$valorgrupo;
        }
       }
       
       
         
         

$cotacao = '{"90":{"foraempate":'.$foraempate.',"casaempate":'.$casaempate.',"foracasa":'.$foracara.',"empatecasa":'.$empatecasa.',"empatefora":'.$empatefora.',"casafora":'.$casafora.',"forafora":'.$forafora.',
"empateempate":'.$empateempate.',"casacasa":'.$casacasa.',"ambse":1.00,"foramp":1.00,"casamp":1.00,"empcg":1.00,"fvcm":'.$fvcm.',"cvfm":'.$cvfm.',
"fv0":'.$fv0.',"cv0":'.$cv0.',"placarimpar":'.$placarimpar.',"placarpar":'.$placarpar.',"fora3gm":1.00,"fora2gm":'.$fora2gm.',
"fora1gm":'.$fora1gm.',"casa3gm":1.00,"casa2gm":'.$casa2gm.',"casa1gm":'.$casa1gm.',"gmenos5":'.$gmenos5.',"gmenos4":'.$gmenos4.',
"gmenos3":'.$gmenos3.',"gmenos2":'.$gmenos2.',"gmais5":'.$gmais5.',"gmais4":'.$gmais4.',"gmais1":'.$gmais1.',"gmais2":'.$gmais2.',
"fnm":1.00,"fm":1.00,"cnm":1.00,"cm":1.00,"ambnsg":'.$ambnsg.',"fmcn":'.$fmcn.',"cmfn":'.$cmfn.',"cof":'.$cof.',"dplfora":'.$dplfora.',
"dplcasa":'.$dplcasa.',"ambn":'.$ambn.',"gmenos1":1.00,"empsg":1.00,"tg6":1.00,"tg5":1.00,"tg4":1.00,"tg3":1.00,"tg2":1.00,"tg1":1.00,"casa":'.
$valorcasa.',"empate":'.$valorempate.',"fora":'.$valorfora.',"gmais3":'.$gmais3.',"amb":'.$amb.',"pc1x0c":'.$pc1x0c.',"pc2x0c":'.$pc2x0c.',"pc2x1c":'.$pc2x1c.',"pc3x0c":'.$pc3x0c.',
"pc3x1c":'.$pc3x1c.',"pc3x2c":'.$pc3x2c.',"pc4x0c":'.$pc4x0c.',"pc4x1c":'.$pc4x1c.',"pc4x2c":'.$pc4x2c.',"pc5x0c":'.$pc5x0c.',"pc5x1c":'.$pc5x1c.',"pc6x0c":'.$pc6x0c.',"pc0x0":'.$pc0x0.',
"pc1x1":'.$pc1x1.',"pc2x2":'.$pc2x2.',"pc3x3":'.$pc3x3.',"pc0x1f":'.$pc0x1f.',"pc0x2f":'.$pc0x2f.',"pc1x2f":'.$pc1x2f.',"pc0x3f":'.$pc0x3f.',"pc1x3f":'.$pc1x3f.',"pc2x3f":'.$pc2x3f.',
"pc0x4f":'.$pc0x4f.',"pc1x4f":'.$pc1x4f.',"pc2x4f":'.$pc2x4f.',"pc0x5f":'.$pc0x5f.',"pc1x5f":'.$pc1x5f.',"pc0x6f":'.$pc0x6f.',"casaesim":'.$casaesim.',"casaenao":'.$casaenao.',"foraesim":'.$foraesim.',
"foraenao":'.$foraenao.',"empateenao":'.$empateenao.',"empateenao":'.$empatesim.',"casaeemais15":'.$casaeemais15.',"casaeemais25":'.$casaeemais25.',"casaeemais35":'.$casaeemais35.',"casaeemais45":'.$casaeemais45.',
"casaeemenos15":'.$casaeemenos15.',"casaeemenos25":'.$casaeemenos25.',"casaeemenos35":'.$casaeemenos35.',"casaeemenos45":'.$casaeemenos45.',"casaouforae15":'.$casaouforae15.',"casaouforae25":'.$casaouforae25.',"casaouforae35":'.$casaouforae35.',
"casaouforae45":'.$casaouforae45.',"casaouforaemenos15":'.$casaouforaemenos15.',"casaouforaemenos25":'.$casaouforaemenos25.',"casaouforaemenos35":'.$casaouforaemenos35.',"casaouforaemenos45":'.$casaouforaemenos45.',
"foraemais15":'.$foraemais15.',"foraemais25":'.$foraemais25.',"foraemais35":'.$foraemais35.',"foraemais45":'.$foraemais45.',
"foraemenos15":'.$foraemenos15.',"foraemenos25":'.$foraemenos25.',"foraemenos35":'.$foraemenos35.',"foraemenos45":'.$foraemenos45.',
"casaemenos15":'.$casaemenos15.',"casaemenos25":'.$casaemenos25.',"casaemenos35":'.$casaemenos35.',"casaemenos45":'.$casaemenos45.',"casaemais15":'.$casaemais15.',
"casaemais25":'.$casaemais25.',"casaemais35":'.$casaemais35.',"casaemais45":'.$casaemais45.',"empateemenos15":'.$empateemenos15.',"empateemenos25":'.$empateemenos25.',
"empateemenos35":'.$empateemenos35.',"empateemenos45":'.$empateemenos45.',"empateemais15":'.$empateemais15.',"empateemais25":'.$empateemais25.',"empateemais45":'.$empateemais45.',
"foraemenos15":'.$foraemenos15.',"foraemenos25":'.$foraemenos25.',"foraemenos35":'.$foraemenos35.',"foraemenos45":'.$foraemenos45.',
"foraemais15":'.$foraemais15.',"foraemais25":'.$foraemais25.',"foraemais35":'.$foraemais35.',"foraemais45":'.$foraemais45.',
"casacasaemais15":'.$casacasaemais15.',"casacasaemais25":'.$casacasaemais25.',"casacasaemais35":'.$casacasaemais35.',"casacasaemais45":'.$casacasaemais45.',
"casacasaemenos15":'.$casacasaemenos15.',"casacasaemenos25":'.$casacasaemenos25.',"casacasaemenos35":'.$casacasaemenos35.',"casacasaemenos45":'.$casacasaemenos45.',
"casaforaemais15":'.$casaforaemais15.',"casaforaemais25":'.$casaforaemais25.',"casaforaemais35":'.$casaforaemais35.',"casaforaemais45":'.$casaforaemais45.',
"casaforaemenos35":'.$casaforaemenos35.',"casaforaemenos45":'.$casaforaemenos45.',
"casaempatemais15":'.$casaempatemais15.',"casaempatemais25":'.$casaempatemais25.',"casaempatemais35":'.$casaempatemais35.',"casaempatemais45":'.$casaempatemais45.',
"casaempateemenos15":'.$casaempateemenos15.',"casaempateemenos25":'.$casaempateemenos25.',"casaempateemenos35":'.$casaempateemenos35.',"casaempateemenos45":'.$casaempateemenos45.',
"foracasaemais15":'.$foracasaemais15.',"foracasaemais25":'.$foracasaemais25.',"foracasaemais35":'.$foracasaemais35.',"foracasaemais45":'.$foracasaemais45.',
"foracasaemenos35":'.$foracasaemenos35.',"foracasaemenos45":'.$foracasaemenos45.',
"foraforaemais15":'.$foraforaemais15.',"foraforaemais25":'.$foraforaemais25.',"foraforaemais35":'.$foraforaemais35.',"foraforaemais45":'.$foraforaemais45.',
"foraforaemenos15":'.$foraforaemenos15.',"foraforaemenos25":'.$foraforaemenos25.',"foraforaemenos35":'.$foraforaemenos35.',"foraforaemenos45":'.$foraforaemenos45.',
"foraempateemais15":'.$foraempateemais15.',"foraempateemais25":'.$foraempateemais25.',"foraempateemais35":'.$foraempateemais35.',"foraempateemais45":'.$foraempateemais45.',
"foraempateemenos25":'.$foraempateemenos25.',"foraempateemenos35":'.$foraempateemenos35.',"foraempateemenos45":'.$foraempateemenos45.',
"empatecasaemais15":'.$empatecasaemais15.',"empatecasaemais25":'.$empatecasaemais25.',"empatecasaemais35":'.$empatecasaemais35.',"empatecasaemais45":'.$empatecasaemais45.',
"empatecasaemenos15":'.$empatecasaemenos15.',"empatecasaemenos25":'.$empatecasaemenos25.',"empatecasaemenos35":'.$empatecasaemenos35.',"empatecasaemenos45":'.$empatecasaemenos45.',
"empateforaemais15":'.$empateforaemais15.',"empateforaemais25":'.$empateforaemais25.',"empateforaemais35":'.$empateforaemais35.',"empateforaemais45":'.$empateforaemais45.',
"empateforaemenos15":'.$empateforaemenos15.',"empateforaemenos25":'.$empateforaemenos25.',"empateforaemenos35":'.$empateforaemenos35.',"empateforaemenos45":'.$empateforaemenos45.',
"empateempateemais15":'.$empateempateemais15.',"empateempateemais25":'.$empateempateemais25.',"empateempateemais35":'.$empateempateemais35.',"empateempateemais45":'.$empateempateemais45.',
"empateempateemenos15":'.$empateempateemenos15.',"empateempateemenos25":'.$empateempateemenos25.',"empateempateemenos35":'.$empateempateemenos35.',"empateempateemenos45":'.$empateempateemenos45.',
"casaforaesim":'.$casaforaesim.',"casaforaenao":'.$casaforaenao.',"casaempateenao":'.$casaempateenao.',"casaempateesim":'.$casaempateesim.',"empateforaesim":'.$empateforaesim.',
"empateforaenao":'.$empateforaenao.',
"escanteios6a8":'.$escanteios6a8.',
"escanteios9a11":'.$escanteios9a11.',
"escanteios12a14":'.$escanteios12a14.',
"escanteiosmais14":'.$escanteiosmais14.',
"escanteiosmenos6":'.$escanteiosmenos6.',
"escanteiosmenos10":'.$escanteiosmenos10.',
"escanteiosigual10":'.$escanteiosigual10.',
"escanteiosmais10":'.$escanteiosmais10.',
"golsno9minmais1gol":'.$golsno9minmais.',
"golsno9minmenos1gol":'.$golsno9minmenos.',
"empateanulacasa":'.$empateanulacasa.',
"empateanulafora":'.$empateanulafora.',
"totaldecartaomais45":'.$totaldecartaomais45.',
"totaldecartaomenos45":'.$totaldecartaomenos45.',
"cartaohccasa0":'.$cartahccasa.',
"cartaohcempate0":'.$cartahcempate.',
"cartaohcfora0":'.$cartahcfora.'
},

"pt":{"empcg":1.00,"fvcm":1.00,"cvfm":1.00,"fv0":1.00,"cv0":1.00,"placarimpar":'.$placarimpar1.',
"placarpar":'.$placarpar1.',"fora3gm":1.00,"fora2gm":1.00,"fora1gm":1.00,"casa3gm":1.00,"casa2gm":1.00,"casa1gm":1.00,"gmenos5":1.00,"gmenos4":1.00,"gmenos3":1.00,"gmenos2":1.00,"gmais5":1.00,"gmais1":'.$gmais11.',
"gmais2":'.$gmais21.',"fnm":1.00,"fm":1.00,"cnm":1.00,"cm":1.00,"fmcn":1.00,"cmfn":1.00,"cof":'.$cof1.',"dplfora":'.$dplfora1.',"dplcasa":'.$dplcasa1.',"ambn":'.$ambn1.',"gmais3":'.$gmais31.',"amb":'.$amb1.',"fora":'.$fora1.',
"empate":'.$empate1.',"casa":'.$casa1.',"gmenos1":'.$gmenos11.',"gmenos3":'.$gmenos31.',"gmenos2":'.$gmenos21.',"empsg":1.00,"pc1x0c":'.$pc1x0c1.',"pc2x0c":'.$pc2x0c1.',"pc2x1c":'.$pc2x1c1.',"pc0x0":'.$pc0x01.',"pc1x1":'.$pc1x11.',
"pc2x2":'.$pc2x21.',"pc0x1f":'.$pc0x1f1.',"pc0x2f":'.$pc0x2f1.',"pc1x2f":'.$pc1x2f1.',
    "t1escanteiosmais4":'.$t1escanteiosmais4.'
},

"st":{"empcg":1.00,"fvcm":1.00,"cvfm":1.00,"fv0":1.00,"cv0":1.00,"placarimpar":'.$placarimpar2.',"placarpar":'.$placarpar2.',"fora3gm":1.00,
"fora2gm":1.00,"fora1gm":1.00,"casa3gm":1.00,"casa2gm":1.00,"casa1gm":1.00,"gmenos5":1.00,"gmenos4":1.00,"gmenos3":'.$gmenos32.',"gmenos2":'.$gmenos22.',"gmais5":1.00,"gmais1":'.$gmais12.',"gmais2":'.$gmais22.',"fnm":1.00,
"fm":1.00,"cnm":1.00,"cm":1.00,"fmcn":1.00,"cmfn":1.00,"cof":'.$cof2.',"dplfora":'.$dplfora2.',"dplcasa":'.$dplcasa2.',"ambn":'.$ambn2.',"gmais3":'.$gmais32.',"amb":'.$amb2.',"fora":'.$fora2.',"empate":'.$empate2.',
"casa":'.$casa2.',"gmenos1":'.$gmenos12.',"empsg":1.00,"pc1x0c":'.$pc1x0c2.',"pc2x0c":'.$pc2x0c2.',"pc2x1c":'.$pc2x1c2.',"pc0x0":'.$pc0x02.',"pc1x1":'.$pc1x12.',"pc2x2":'.$pc2x22.',"pc0x1f":'.$pc0x1f2.',
"pc0x2f":'.$pc0x2f.',"pc1x2f":'.$pc1x2f2.'}}';


}
               
        
     
            //Update da Data e hora das partidas
                    echo $cotacao;      
           $status = 1.00;
 $update = "UPDATE sis_jogos SET cotacoes = :cotacao WHERE refimport = :idjogo AND alteroucotacoes = 0";    
            try {
                $result = $conexao->prepare($update);
                $result->bindValue(':cotacao', $cotacao);
                $result->bindValue(':idjogo', $idjogo);
                $result->execute();
                $contUp = $result->rowCount();
            } catch(PDOException $e) {
                error_log("Erro ao atualizar jogo " . $idjogo . ": " . $e->getMessage());
            }  
}

    if ($conexao && $conexao->inTransaction()) {
        $conexao->commit();
    }
    echo "Atualizacao concluida com sucesso!";
} catch (Exception $e) {
    if ($conexao && $conexao->inTransaction()) {
        $conexao->rollBack();
    }
    error_log("Erro na transacao de atualizacao: " . $e->getMessage());
    echo "Erro durante a atualizacao dos jogos.";
}
