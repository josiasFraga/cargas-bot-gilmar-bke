<?php
set_time_limit(0);
require_once '../wp-config.php';
require_once '../wp-load.php';


define( 'CUSTOMER_TOKEN', 'a21fbf4615285043185e029ce715d645ab4da37cf8a97c4e251c5dd33dbc0ee0' ); // token da tabela b00clientes
define( 'API_BASEURL', 'https://beformless.net/_bke/api' ); // pode deixar assim

try {
    $cnn = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASSWORD);
    $cnn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $cnn->exec('SET NAMES utf8');
} catch (PDOException $e) {
    echo 'ERROR: ' . $e->getMessage();
    die();
}



// apaga todos desafios/projetos/fotos empresas
$configuracoes_site = get_option( 'configuracoes-do-site', array() );

$_ids_empresas = [];
if ($configuracoes_site['importe-ou-selecione-as-empresas'] != '') {
    $_ids_empresas = explode(",",$configuracoes_site['importe-ou-selecione-as-empresas']);
}

$query = $cnn->prepare("SELECT ID FROM ".$table_prefix."posts WHERE post_type = 'projetos' or post_type = 'desafios' OR post_title like '%IMAGETMPBEFORMLESS%'");
$query->execute();
$posts_apagar_ids = $query->fetchAll(PDO::FETCH_ASSOC);
$_posts_apagar_ids = [];
foreach ($posts_apagar_ids as $post_id) {
    $_posts_apagar_ids[] = $post_id['ID'];
}
$_posts_apagar_ids = array_merge($_ids_empresas, $_posts_apagar_ids); // apaga as logos de empresas junto.

if ($_posts_apagar_ids) {
    $query = $cnn->prepare("DELETE FROM ".$table_prefix."posts WHERE ID IN (".implode(",",$_posts_apagar_ids).")"); // apaga todos projetos/desafios e imagens de projetos/usuarios
    $query->execute();
    
    $query = $cnn->prepare("DELETE FROM ".$table_prefix."postmeta WHERE post_id IN (".implode(",",$_posts_apagar_ids).")"); // apaga todos projetos/desafios e imagens de projetos/usuarios
    $query->execute();
}
$files = glob('uploads/projetos/*'); // get all file names
foreach($files as $file){ // iterate files
  if(is_file($file)) {
    unlink($file); // delete file
  }
}
/* fim apagar tudo */

$ch = curl_init(API_BASEURL . '/Relatorios/resumo/'.CUSTOMER_TOKEN);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response_api_resumo = curl_exec($ch);
$response_api_resumo = json_decode($response_api_resumo);
var_dump($response_api_resumo);

$configuracoes_site['numero'] = $response_api_resumo->qtd_desafios;
$configuracoes_site['numero-2'] = $response_api_resumo->qtd_projetos_concluidos;
$configuracoes_site['numero-3'] = $response_api_resumo->qtd_projetos_ativos;
$configuracoes_site['numero-4'] = $response_api_resumo->qtd_mentores;
$configuracoes_site['numero-5'] = $response_api_resumo->qtd_pessoas_impactadas;
$configuracoes_site['numero-6'] = $response_api_resumo->qtd_empresas;
$configuracoes_site['numero-7'] = $response_api_resumo->qtd_alunos;

$lista_ids_empresas_upload = [];
foreach ($response_api_resumo->empresas as $empresa) {
    var_dump($empresa);
    $empresa_id = uploadImagem($empresa);
    var_dump($empresa_id);
    $lista_ids_empresas_upload[] = $empresa_id;
}
var_dump($lista_ids_empresas_upload);
if ($lista_ids_empresas_upload) {
    
    $configuracoes_site['importe-ou-selecione-as-empresas'] = implode(',', $lista_ids_empresas_upload);
} else {
    $configuracoes_site['importe-ou-selecione-as-empresas'] = '';
}

update_option('configuracoes-do-site', $configuracoes_site);

$ch = curl_init(API_BASEURL . '/Projetos/api_index/'.CUSTOMER_TOKEN);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response_api_projetos = curl_exec($ch);
$response_api_projetos = json_decode($response_api_projetos);
var_dump($response_api_projetos);

$ch = curl_init(API_BASEURL . '/Desafios/api_index/'.CUSTOMER_TOKEN);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response_api_desafios = curl_exec($ch);
$response_api_desafios = json_decode($response_api_desafios);
var_dump($response_api_desafios);


foreach ($response_api_desafios->desafios as $desafio) {
    $imagem_id = uploadImagem($desafio->Desafio->imagem);
    
    $query = $cnn->prepare("INSERT INTO ".$table_prefix."posts SET 
        post_author = 1,
        post_date = NOW(),
        post_date_gmt = NOW(),
        post_content = '',
        post_title = ?,
        post_excerpt = '',
        post_status = 'publish',
        comment_status = 'closed',
        ping_status = 'closed',
        post_password = '',
        post_name = 'rascunho-automatico',
        to_ping = '',
        pinged = '',
        post_modified = NOW(),
        post_modified_gmt = NOW(),
        post_content_filtered = '',
        post_parent = 0,
        guid = '',
        menu_order = 0,
        post_type = 'desafios',
        post_mime_type = '',
        comment_count = 0
    ");
    $query->bindValue(1, $desafio->Desafio->nome);
    $query->execute();
    $desafio_id = $cnn->lastInsertId();
    
    $imagem_desafio = uploadImagem($desafio->Desafio->imagem);
    $responsavel_foto = uploadImagem($desafio->Usuario->usfoto);
    
    $meta_fields = [
        '_edit_last' => '1',
        '_edit_lock' => '1608099685:1',
        'nome-do-responsavel' => $desafio->Usuario->usnome,
        'foto-do-responsavel' => $responsavel_foto,
        '_thumbnail_id' => $imagem_desafio
    ];

    foreach ($meta_fields as $metakey => $metavalue) {
        $query = $cnn->prepare("INSERT INTO ".$table_prefix."postmeta SET
            post_id = ?,
            meta_key = ?,
            meta_value = ?
        ");
        $query->bindValue(1, $desafio_id);
        $query->bindValue(2, $metakey);
        $query->bindValue(3, $metavalue);
        $query->execute();
    
    }
}

foreach ($response_api_projetos->projetos as $projeto) {
    $imagem_id = uploadImagem($projeto->Projeto->imagem);
    
    $query = $cnn->prepare("INSERT INTO ".$table_prefix."posts SET 
        post_author = 1,
        post_date = NOW(),
        post_date_gmt = NOW(),
        post_content = '',
        post_title = ?,
        post_excerpt = '',
        post_status = 'publish',
        comment_status = 'closed',
        ping_status = 'closed',
        post_password = '',
        post_name = 'rascunho-automatico',
        to_ping = '',
        pinged = '',
        post_modified = NOW(),
        post_modified_gmt = NOW(),
        post_content_filtered = '',
        post_parent = 0,
        guid = '',
        menu_order = 0,
        post_type = 'projetos',
        post_mime_type = '',
        comment_count = 0
    ");
    $query->bindValue(1, $projeto->Projeto->nome);
    $query->execute();
    $projeto_id = $cnn->lastInsertId();
    
    $integrante_1_nome = '';
    $integrante_1_foto = '';
     $integrante_2_nome = '';
    $integrante_2_foto = '';
     $integrante_3_nome = '';
    $integrante_3_foto = '';
     $integrante_4_nome = '';
    $integrante_4_foto = '';
    if (isset($projeto->Projeto->_integrantes[0])) {
        $integrante_1_nome = $projeto->Projeto->_integrantes[0]->Usuario->usnome;
        $integrante_1_foto = uploadImagem($projeto->Projeto->_integrantes[0]->Usuario->usfoto);
    }
    if (isset($projeto->Projeto->_integrantes[1])) {
        $integrante_2_nome = $projeto->Projeto->_integrantes[1]->Usuario->usnome;
        $integrante_2_foto = uploadImagem($projeto->Projeto->_integrantes[1]->Usuario->usfoto);
    }
    if (isset($projeto->Projeto->_integrantes[2])) {
        $integrante_3_nome = $projeto->Projeto->_integrantes[2]->Usuario->usnome;
        $integrante_3_foto = uploadImagem($projeto->Projeto->_integrantes[2]->Usuario->usfoto);
    }
    if (isset($projeto->Projeto->_integrantes[3])) {
        $integrante_4_nome = $projeto->Projeto->_integrantes[3]->Usuario->usnome;
        $integrante_4_foto = uploadImagem($projeto->Projeto->_integrantes[3]->Usuario->usfoto);
    }
    
    $meta_fields = [
        '_edit_last' => '1',
        '_edit_lock' => '1608099685:1',
        '_thumbnail_id' => $imagem_id,
        'integrante-1_tab' => '',
        'integrante-1' => '',
        'integrante-2' => '',
        'integrante-3' => '',
        'integrante-4' => '',
        'fotos_843' => $integrante_4_foto,
        'nome' => $integrante_1_nome,
        'fotos' => $integrante_1_foto,
        'nome_847' => $integrante_2_nome,
        'fotos_677' => $integrante_2_foto,
        'nome_529' => $integrante_3_nome,
        'fotos_410' => $integrante_3_foto,
        'nome_753' => $integrante_4_nome,
    ];

    foreach ($meta_fields as $metakey => $metavalue) {
        $query = $cnn->prepare("INSERT INTO ".$table_prefix."postmeta SET
            post_id = ?,
            meta_key = ?,
            meta_value = ?
        ");
        $query->bindValue(1, $projeto_id);
        $query->bindValue(2, $metakey);
        $query->bindValue(3, $metavalue);
        $query->execute();
    
    }
}


function uploadImagem($url) {
    global $cnn;
    global $table_prefix;
     // baixar foto
    $nome_imagem = time().basename($url);
    $salvar_imagem = 'uploads/projetos/' . $nome_imagem;
    grab_image($url, $salvar_imagem);
        
    $query = $cnn->prepare("INSERT INTO ".$table_prefix."posts SET 
        post_author = 1,
        post_date = NOW(),
        post_date_gmt = NOW(),
        post_content = '',
        post_title = ?,
        post_excerpt = '',
        post_status = 'inherit',
        comment_status = 'open',
        ping_status = 'closed',
        post_password = '',
        post_name = ?,
        to_ping = '',
        pinged = '',
        post_modified = NOW(),
        post_modified_gmt = NOW(),
        post_content_filtered = '',
        post_parent = 0,
        guid = '',
        menu_order = 0,
        post_type = 'attachment',
        post_mime_type = ?,
        comment_count = 0
    ");
    $query->bindValue(1, 'IMAGETMPBEFORMLESS_'.$nome_imagem);
    $query->bindValue(2, 'IMAGETMPBEFORMLESS_'.$nome_imagem);
    $query->bindValue(3, mime_content_type($salvar_imagem));
    $query->execute();
    $imagem_id = $cnn->lastInsertId();
    
    $meta_fields = [
        '_wp_attached_file' => 'projetos/'.$nome_imagem,
     ];

    foreach ($meta_fields as $metakey => $metavalue) {
        $query = $cnn->prepare("INSERT INTO ".$table_prefix."postmeta SET
            post_id = ?,
            meta_key = ?,
            meta_value = ?
        ");
        $query->bindValue(1, $imagem_id);
        $query->bindValue(2, $metakey);
        $query->bindValue(3, $metavalue);
        $query->execute();
    }
    
    return $imagem_id;
}

function grab_image($url,$saveto){
	$ch = curl_init ($url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

	$raw=curl_exec($ch);
	curl_close ($ch);
	if(file_exists($saveto)){
		unlink($saveto);
	}
	$fp = fopen($saveto,'x');
	fwrite($fp, $raw);
	fclose($fp);
}