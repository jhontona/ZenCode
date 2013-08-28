<?php 
	
	$m = new MongoClient();
	$db = $m->selectDB("data");
	$mongo = $db->codes;
	$template = file_get_contents('template.html');
	
  	if (empty($page)){
  		$page = 'home';
  	}

  	if (isset($_GET["page"])) {
  		$page = $_GET["page"];
  	}

  	function caracteres($string){
  		$chars = array(	'á'=>'&#225;',
  						'é'=>'&#233;',
  						'í'=>'&#237;',
  						'ó'=>'&#243;',
  						'"'=>'&#34;',
  						'!'=>'&#33;',
  						'<'=>'&lt;',
  						'='=>'&#61;',
  						'>'=>'&gt;',
  						'|'=>'&#124;',
  						'ñ'=>'&#241;',
  						'\\'=>'&#92;'
  			);	
  		$cadena = str_replace(array_keys($chars), array_values($chars), $string);
  		return trim($cadena);
  	}

	function formatearBusqueda($string){
		$string = strtolower($string);
		$cEspecial = array('á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u');
		$stringFormateado = str_replace(array_keys($cEspecial), array_values($cEspecial), $string);
		$eliminar = array(" de la ", " de el ", " la ", " el ", " los ", " de ", " con ", " a ", " ante ", " bajo ", " cabe ", " desde ", " en ", " hacia ", " por ", " para ", " sin ", " sobre ", " tras ", " durante ", " entre ", " un ", " e ", " o ", " y ", " u ", " una ", " del ");
		$string = str_replace($eliminar, " ", $stringFormateado);
		return $string;
	}

  	switch ($page) {
  		case 'insertar':
  			$listLeng='';
  			$respuesta='';
			foreach ($db->lenguajes->find()->sort(array('name'=>1)) as $leng){
				$listLeng .= '<option value="'.$leng['name'].'">'.$leng['name'].'</option>';
			}
  			$frmInsert = file_get_contents('insert.html');
			if (isset($_POST["titulo"])) {
				$arrayTags = str_ireplace('<span class="label label-inverse">','',$_POST["hTags"]);
				$arrayTags = str_ireplace('</span> ',',',$arrayTags);
				$arrayTags = explode(",", $arrayTags);
				$arrayTags = array_slice($arrayTags, 0, -1);
				foreach ($arrayTags as $key => $value) {
					$arrayTags[$key] = ucwords($value);
				}
				$dato = array('titulo'=>caracteres($_POST["titulo"]), 'code'=>trim(nl2br(caracteres($_POST["code0"]))), 'comment'=>nl2br(caracteres($_POST["comment"])), 'tags'=>$arrayTags, 'lenguaje'=>$_POST["coleccion"]);
				if (isset($_POST["code1"]) and !(empty($_POST["code1"]))){
					$bool = true;
					$contadorCodigos = 0;
					while ($bool == true) {
						$contadorCodigos++;
						$dato['code'.$contadorCodigos] = caracteres($_POST["code".$contadorCodigos]);
						if (!(isset($_POST["code".$contadorCodigos + 1])) or empty($_POST["code".$contadorCodigos + 1])){
							$bool = false;
						}
					}
				}
				try {
					$mongo->insert($dato);
					$respuesta = 	'<div class="alert alert-success">
  										<a class="close" data-dismiss="alert">×</a>
  										<h4 class="alert-heading">Exitoso!</h4>
  										Se ha actualizado correctamente
									</div>';
				} catch (Exception $e) {
					$respuesta =	'<div class="alert alert-error">
  										<a class="close" data-dismiss="alert">×</a>
  										<h4 class="alert-heading">Upss!</h4>
  										Ha ocurrido un error no se puedo completar la actualización
									</div>';
				}		
			}
			$diccionarioFrmInsert = array('{LISTA}' => $listLeng, '{RESPUESTA}' => $respuesta);
			$contenido = str_replace(array_keys($diccionarioFrmInsert), array_values($diccionarioFrmInsert), $frmInsert);
  			break;
  		case 'home':
  			$renderResultados = '';
  			$contadorResultados = '';
			if (isset($_POST["buscador"]) and !(empty($_POST["buscador"]))){
				$stringBusqueda = trim(formatearBusqueda($_POST["buscador"]));
				$stringBusqueda = explode(" ", ucwords($stringBusqueda));
				if (isset($_POST["btnBuscar"]) and !(empty($_POST["btnBuscar"]))) {
					foreach ($stringBusqueda as $key) {
  						$tmp[] = new MongoRegex( "/". $key ."/i" );
  					}
  					$query = array(
  								'$and'=>array(
  									array("lenguaje"=>$_POST["btnBuscar"]),
  									array('$or'=>array(
		  									array('tags'=>array('$in'=>$tmp)), 
		  									array('titulo'=>array('$in'=>$tmp))
		  									)
  										)
  									)
  								);  					
  				} else{
  					foreach ($stringBusqueda as $key) {
  						$tmp[] = new MongoRegex( "/". $key ."/i" );
  					}
  					$query = array(
  								'$or'=>array(
  									array('tags'=>array('$in'=>$tmp)), 
  									array('titulo'=>array('$in'=>$tmp))
  									)
  								);
  				}
  				$resultados = $mongo->find($query);	
			} else {
				if (isset($_POST["btnBuscar"]) and !(empty($_POST["btnBuscar"]))) {
					$resultados = $mongo->find(array("lenguaje"=>$_POST["btnBuscar"]));
				} else {
					if (isset($_POST["btnBuscar"])){
						$resultados = $mongo->find();
					}
				}
			}

			if (isset($resultados) and !(empty($resultados))){
				$i = 0;
				$contadorResultados = 0;
				foreach($resultados as $item){
					$contadorResultados++;
				}
				$contadorResultados = '<span class="badge badge-inverse" style="position:absolute;right:0px;">'.$contadorResultados.' Resultados </span><br><hr>';
				foreach($resultados as $item){
					$moreCode = "";
					$i++;
					$boolean = true;
					if (isset($item["code1"]) and !(empty($item["code1"]))) {
						$i2 = 0;
						while ( $boolean == true) {
							$i2++;
							$moreCode .= '<pre class="prettyprint linenums">'.$item["code".$i2].'</pre>';
							if (!(isset($item["code".$i2+1])) or empty($item["code".$i2+1])){
								$boolean = false;
							}
						}
					}
					$plantillaRsultados = file_get_contents('resultadosBusqueda.html');
					$diccionarioRsultados = $arrayName = array('{ID}'=>$i,'{DESCRIP}'=>$item['comment'],'{TITULO}' =>$item['titulo'].'<img src="img/'.$item['lenguaje'].'.png" style="width:1.5em; height:1.5em; position:absolute; right:3%;" />', '{CODIGO}'=>$item['code'], '{MORECODE}'=>$moreCode);
					$renderResultados .= str_replace(array_keys($diccionarioRsultados), array_values($diccionarioRsultados), $plantillaRsultados); 
				}
			} 
			$listLeng='';
			foreach ($db->lenguajes->find()->sort(array('name'=>1)) as $leng){
				$listLeng .= '<li><a class="seleccion" onClick="busqueda(this.innerHTML)">'.$leng['name'].'</a></li>';
			}
			$buscadorPlantilla = file_get_contents('buscador.html');
			$diccionarioBusqueda = array('{BUSQUEDA}' => $contadorResultados.$renderResultados, '{LISTA}'=>$listLeng);
			$contenido = str_replace(array_keys($diccionarioBusqueda), array_values($diccionarioBusqueda), $buscadorPlantilla);
  			break;
		case 'lenguajes':
			$contenido = file_get_contents('lenguajes.html');
			if (isset($_FILES['image'])){
				$gridFS = $m->data->getGridFS();
				$filename = $_FILES['image']['name'];
				$filetype = $_FILES['image']['type'];
				$tmpfilepath = $_FILES['image']['tmp_name'];
				$caption = $_POST['caption'];
				$id = $gridFS->storeFile($tmpfilepath, array(
						'filename'=>$filename,
						'filetype'=>$filetype,
						'caption'=>$caption
					)
				);
			}
			break;
  		default:
  			$contenido = file_get_contents('404.html');
  			break;
  	}

	$diccionario = array('{contenido}'=>$contenido);
	$render = str_replace(array_keys($diccionario), array_values($diccionario), $template);
	echo $render;
?>