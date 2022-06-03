<?php
/**
 * Application level Controller
 *
 * This file is application-wide controller file. You can put all
 * application-wide controller-related methods here.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       app.Controller
 * @since         CakePHP(tm) v 0.2.9
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Controller', 'Controller');

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @package		app.Controller
 * @link		https://book.cakephp.org/2.0/en/controllers.html#the-app-controller
 */

header("Access-Control-Allow-Origin: *");

class AppController extends Controller {
    public $meses = array(
        1 => 'Janeiro',
        'Fevereiro',
        'Março',
        'Abril',
        'Maio',
        'Junho',
        'Julho',
        'Agosto',
        'Setembro',
        'Outubro',
        'Novembro',
        'Dezembro'
    );
    
    public $meses_abreviado = array(
        1 => 'Jan',
        'Fev',
        'Mar',
        'Abr',
        'Mai',
        'Jun',
        'Jul',
        'Ago',
        'Set',
        'Out',
        'Nov',
        'Dez'
    );
    
    public $dias_da_semana = array(
        'Domingo',
        'Segunda-Feira',
        'Terça-Feira',
        'Quarta-Feira',
        'Quinta-Feira',
        'Sexta-Feira',
        'Sábado'
    );

    public $branchs = [
        'GRAVATAI', 
        'S.B.CAMPO'
    ];

    public function verificaValidadeToken($usuario_email, $token = null){

        $this->loadModel('Usuario');
        $dados_token = $this->Usuario->find('first',array(
            'fields' => array(
                'Usuario.id',
                'Usuario.nome',
                'Usuario.email',
            ),
            'conditions' => array(
                'Usuario.email' => $usuario_email,
                'Usuario.ativo' => 'Y',
                'Usuario.token' => $token,
                'Usuario.token_validade >=' => date('Y-m-d H:i:s'),
            ),
            'link' => array(
            )
        ));

        if (count($dados_token) > 0){
            return $dados_token;
        }
        return false;
    }
}
