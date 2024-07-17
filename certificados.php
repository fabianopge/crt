<?php

setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Sao_Paulo');

//INICIA A SESSÃO
session_start();

//VALIDA A SESSÃO
if(isset($_SESSION["DONO"])){
    
    //GERA O TOKEN
    $token_usuario = md5('abraPPd0sxnh12!@#$rastro-associado'.$_SERVER['REMOTE_ADDR']);
    
    //SE FOR DIFERENTE
    if($_SESSION["DONO"] !== $token_usuario){

        //VERIFICA SE VEIO DO AJAX
        if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            
            //RETORNA PRO AJAX QUE A SESSÃO É INVÁLIDA
            echo "SESSÃO INVÁLIDA";
            
        } else {
            
            //REDIRECIONA PARA A TELA DE LOGIN
            echo "<script>location.href='../../modulos/login/php/encerra-sessao.php';</script>";
            
        }

    } else {

        //RECEBE O IDENTIFICADOR
        $identificador = trim(strip_tags(filter_input(INPUT_GET, "id", FILTER_SANITIZE_STRING)));

        if(!empty($identificador) & mb_strlen($identificador ) === 32){

            //CONECTA NO BANCO
            include_once 'bd/conecta.php';

            //BUSCA OS DADOS DO CERTIFICADO
            $certificado = mysqli_query($conn, "SELECT * FROM certificado WHERE identificador = '$identificador'");
            $certificado = mysqli_fetch_array($certificado);

            $certificado_data          = $certificado['data'];
            $certificado_horas         = $certificado['horas'];
            $certificado_curso         = $certificado['curso'];
            $certificado_descricao     = $certificado['descricao'];
            $certificado_logo          = $certificado['logo'];
            $certificado_participantes = json_decode($certificado['participantes'], true);
            $total_participates        = count($certificado_participantes['nomes']);
            
            //INCLUI A BIBLIOTECA DO MPDF
            require_once 'MPDF/mpdf.php';      
            require_once "QRCODE/qrlib.php";         

            //GERA NOVO MPDF
            $mpdf = new mPDF('utf-8', 'A4-L',0,0,0,0,0,0);
            
            $array_cpfs = [];

            //ATIVA O BUFFER DE SAÍDA
            ob_start();
            
            for($i=0; $i < $total_participates; $i++){ 

                //GERA O QRCODE DO LINK
                $url_cod = 'https://abrarastro.org/certificado-curso/'.$identificador.'/'.preg_replace('/[^0-9]/', '', $certificado_participantes['cpfs'][$i]);
                $cod_aux = preg_replace('/[^0-9]/', '',$certificado_participantes['cpfs'][$i]);
                QRcode::png($url_cod, $cod_aux.'.png', QR_ECLEVEL_L, 2.2);
                
                $array_cpfs[] = $cod_aux;

                ?>

                <div style="width: 100%; height: 100%; background-image: url(img/background-certificado.png); background-size: cover; background-position: center;">
                    <div style="padding: 100px; width: 100%; height: 100%; color: #696969;">

                        <div style="margin-bottom: 20px;">
                            <img src="img/logo.png">
                            <?php if($certificado_logo != ''){ ?>
                                <img style="float:right; max-width: 200px; max-height: 100px;" src="modulos/certificados/logos/<?= $certificado_logo ?>">
                            <?php } ?>
                        </div>

                        <hr>
                        
                        <div style="font-size: 35px; text-transform: uppercase; width: 100%; text-align: center; margin-top: 35px;">CERTIFICADO DE CONCLUSÃO</div>
                        <div style="font-size: 60px; text-transform: capitalize; width: 100%; text-align: center; margin-top: 20px;"><?= $certificado_participantes['nomes'][$i] ?></div>
                        <div style="font-size: 20px; width: 100%; text-align: center; margin-top: 20px;">Participou da Palestra</div>
                        <div style="font-size: 20px; text-transform: uppercase; width: 100%; text-align: center;"><?= $certificado_curso ?> (<?= $certificado_horas ?> horas)</div>
                        <div style="font-size: 20px; text-transform: uppercase; width: 100%; text-align: center;"><?= date('d/m/Y', strtotime($certificado_data)) ?></div>
                    
                        <div style="margin-top: 100px;">
                            <div style="width: 50%;float: left; text-align: center;"><img src="img/assinatura.png"></div>
                            <div style="float:right;width: 50%;">
                                <ul style="list-style-type: none; padding: 0; margin: 0; text-align: center; margin-top: 45px;">
                                    <?php if($identificador == 'e2115007a78132a1ece2ae7e3657ac6a'){ ?>
                                        <li style="margin-top: -22px; margin-bottom: -10px;"><img style="max-width: 250px;" src="img/assinatura-fernando.png"></li>
                                    <?php } ?>
                                    <li>_________________________________________________</li>
                                    <li>Ministrante</li>
                                </ul>
                            </div>
                        </div>

                    </div>
                </div>

                <pagebreak />
                
                <div style="width: 100%; height: 100%; background-image: url(img/background-certificado.png); background-size: cover; background-position: center;">
                    <div style="padding: 100px 150px 0 150px; width: 50%; height: 100%; color: #696969;">
                        <ul style="list-style-type: none;">
                            <li style="margin-bottom: 5px;"><b>Atividades ministradas:</b></li>
                            <li><?= $certificado_descricao ?></li>
                        </ul>                        
                    </div>
                </div>

                <div style="position: absolute; top: 100px; right: 100px;">
                    <img style="width: 100px;" alt="QR Code" src="<?= $cod_aux.'.png' ?>" />
                </div>
                
                <?php if($i != $total_participates-1){ ?>
                    <pagebreak />
                <?php } ?>
            
            <?php }

            //FINALIZA O BUFFER DE SAÍDA
            $certificados = ob_get_clean();

            //ADICIONA O HTML
            $mpdf->WriteHTML($certificados);

            //OUTPUT
            $mpdf->Output('certificados.pdf', 'I');
            
            for($i=0; $i < count($array_cpfs); $i++){ 
                unlink($array_cpfs[$i].'.png');
            }
            
            //DESCONECTA DO BANCO
            include_once 'bd/desconecta.php';

        } else {
                
            //REDIRECIONA PARA A TELA DE LOGIN
            echo "<script>location.href='../../modulos/login/php/encerra-sessao.php';</script>";
            
        }
        
    }
    
} else {
    
    //VERIFICA SE VEIO DO AJAX
    if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {

        //RETORNA PRO AJAX QUE A SESSÃO É INVÁLIDA
        echo "SESSÃO INVÁLIDA";

    } else {

        //REDIRECIONA PARA A TELA DE LOGIN
        echo "<script>location.href='../../modulos/login/php/encerra-sessao.php';</script>";

    }
        
}