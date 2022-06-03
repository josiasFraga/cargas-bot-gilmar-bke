<?php
class DadosController extends AppController {

	public function datas() {
        $this->layout = 'ajax';
        $this->loadModel('Dado');
        $dados = $this->Dado->find('all',[
            'fields' => [
                'Dado.created'
            ],
            'group' => [
                'DATE(Dado.created)'
            ],
            'order' => [
                'DATE(Dado.created)'
            ]
            
        ]);
        if ( count($dados) > 0  ) {
            foreach($dados as $key => $dado){
                $dia = date('d',strtotime($dado['Dado']['created']));
                $mes = date('m',strtotime($dado['Dado']['created']));
                $ano = date('Y',strtotime($dado['Dado']['created']));
                $dia_semana = date('w',strtotime($dado['Dado']['created']));
                $dia_semana_str = $this->dias_da_semana[$dia_semana];
                $mes_abrev = $this->meses_abreviado[(int)$mes];
                $dados[$key]['Dado']['data_br'] = date('d/m/Y',strtotime($dado['Dado']['created']));
                $dados[$key]['Dado']['data'] = date('Y-m-d',strtotime($dado['Dado']['created']));
                $dados[$key]['Dado']['data_str'] = $dia_semana_str . ', ' . $dia . ' de ' .$mes_abrev . ' de ' . $ano;
                $dados[$key]['Dado']['data_str_abrev'] =  $dia . ' de ' .$mes_abrev;
            }
        }
        return new CakeResponse(array('type' => 'json', 'body' => json_encode(['status' => 'ok', 'dados' => $dados])));

	}

	public function dados($data = null) {
        $this->layout = 'ajax';
        $this->loadModel('Dado');
        //debug($data);
        $dados = $this->Dado->find('all',[
        //$dados = $this->Dado->find('first',[
            'conditions' => [
                'DATE(Dado.created)' => $data,
                'and' => [
                    ['Dado.dados like' => '%"_type": "custom.chamada_object"%'],
                    ['Dado.dados like' => '%"status_text": "finalizada"%'],
                ]
                
            ],
            'order' => [
                'DATE(Dado.created) DESC'
            ]
            
        ]);
        $dados_arr = [];
        if ( count($dados) > 0  ) {
            //$dados_arr = json_decode($dados['Dado']['dados'], true);
            foreach( $dados as $key => $dado ){
                $dados_json = json_decode($dado['Dado']['dados'], true);

                if ( !isset($dados_arr) || count($dados_arr)  == 0 ) {
                    $dados_arr = array_filter($dados_json, function($item){
                        return isset($item['status_text']) && $item['status_text'] == 'finalizada';
                    });
                    continue;
                }
                $dados_arr = array_merge($dados_arr, array_filter($dados_json, function($item){
                    return isset($item['status_text']) && $item['status_text'] == 'finalizada';
                }));
            }
            
        }

        if ( count($dados_arr) > 0 ) {
            $dados_arr = array_map("unserialize", array_unique(array_map("serialize", $dados_arr)));
            $dados_arr = array_map(function($item){
                /*$dados_arr_2 = array_map(function($subitem){

                    $json_text_array = json_decode($subitem['json_text'],true);
                    $subitem['json_text'] = $json_text_array;
                    return $subitem;

                },$item);*/

                $item['json_text'] = json_decode($item['json_text'],true);

                return $item;
            }, $dados_arr);
        }

        /*$dados = $this->Dado->find('first',[
            'conditions' => [
                'DATE(Dado.created)' => $data,
                'Dado.dados like' => '%90789%' 
            ],
            'order' => [
                'DATE(Dado.created) DESC'
            ]
            
        ]);

        if ( count($dados) > 0  ) {
            $dados_arr = json_decode($dados['Dado']['dados'], true);
        }*/
        return new CakeResponse(array('type' => 'json', 'body' => json_encode(['status' => 'ok', 'dados' => $dados_arr])));

	}
}