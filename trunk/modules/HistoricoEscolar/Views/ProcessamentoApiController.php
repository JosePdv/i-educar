<?php

#error_reporting(E_ALL);
#ini_set("display_errors", 1);

/**
 * i-Educar - Sistema de gestão escolar
 *
 * Copyright (C) 2006  Prefeitura Municipal de Itajaí
 *     <ctima@itajai.sc.gov.br>
 *
 * Este programa é software livre; você pode redistribuí-lo e/ou modificá-lo
 * sob os termos da Licença Pública Geral GNU conforme publicada pela Free
 * Software Foundation; tanto a versão 2 da Licença, como (a seu critério)
 * qualquer versão posterior.
 *
 * Este programa é distribuí­do na expectativa de que seja útil, porém, SEM
 * NENHUMA GARANTIA; nem mesmo a garantia implí­cita de COMERCIABILIDADE OU
 * ADEQUAÇÃO A UMA FINALIDADE ESPECÍFICA. Consulte a Licença Pública Geral
 * do GNU para mais detalhes.
 *
 * Você deve ter recebido uma cópia da Licença Pública Geral do GNU junto
 * com este programa; se não, escreva para a Free Software Foundation, Inc., no
 * endereço 59 Temple Street, Suite 330, Boston, MA 02111-1307 USA.
 *
 * @author    Lucas D'Avila <lucasdavila@portabilis.com.br>
 * @category  i-Educar
 * @license   @@license@@
 * @package   Avaliacao
 * @subpackage  Modules
 * @since   Arquivo disponível desde a versão ?
 * @version   $Id$
 */

require_once 'Core/Controller/Page/EditController.php';
require_once 'Avaliacao/Model/NotaComponenteDataMapper.php';
require_once 'Avaliacao/Service/Boletim.php';
require_once 'App/Model/MatriculaSituacao.php';
require_once 'RegraAvaliacao/Model/TipoPresenca.php';
require_once 'RegraAvaliacao/Model/TipoParecerDescritivo.php';
require_once 'include/pmieducar/clsPmieducarMatricula.inc.php';
require_once 'include/portabilis/dal.php';
require_once 'include/pmieducar/clsPmieducarHistoricoEscolar.inc.php';

class ProcessamentoApiController extends Core_Controller_Page_EditController
{
  protected $_dataMapper  = 'Avaliacao_Model_NotaComponenteDataMapper';
  protected $_processoAp  = 644;
  protected $_nivelAcessoOption = App_Model_NivelAcesso::SOMENTE_ESCOLA;
  protected $_saveOption  = FALSE;
  protected $_deleteOption  = FALSE;
  protected $_titulo   = '';


  protected function validatesPresenceOf(&$value, $name, $raiseExceptionOnEmpty = false, $msg = '', $addMsgOnEmpty = true){
    if (! isset($value) || (empty($value) && !is_numeric($value))){
      if ($addMsgOnEmpty)
      {
        $msg = empty($msg) ? "É necessário receber uma variavel '$name'" : $msg;
        $this->appendMsg($msg);
      }

      if ($raiseExceptionOnEmpty)
         throw new Exception($msg);

      return false;
    }
    return true;
  }

  protected function validatesValueIsNumeric(&$value, $name, $raiseExceptionOnError = false, $msg = '', $addMsgOnError = true){
    if (! is_numeric($value)){
      if ($addMsgOnError)
      {
        $msg = empty($msg) ? "O valor recebido para variavel '$name' deve ser numerico" : $msg;
        $this->appendMsg($msg);
      }

      if ($raiseExceptionOnError)
         throw new Exception($msg);

      return false;
    }
    return true;
  }

  protected function validatesValueInSetOf(&$value, $setExpectedValues, $name, $raiseExceptionOnError = false, $msg = ''){
    if (! in_array($value, $setExpectedValues)){
      $msg = empty($msg) ? "Valor recebido na variavel '$name' é invalido" : $msg;
      $this->appendMsg($msg);

      if ($raiseExceptionOnError)
         throw new Exception($msg);

      return false;
    }
    return true;
  }


  protected function requiresLogin($raiseExceptionOnEmpty){
    return $this->validatesPresenceOf($this->getSession()->id_pessoa, '', $raiseExceptionOnEmpty, 'Usuário deve estar logado');
  }

  protected function requiresUserIsAdmin($raiseExceptionOnError){

    if($this->getSession()->id_pessoa != 1){
      $msg = "O usuário logado deve ser o admin";
      $this->appendMsg($msg);

      if ($raiseExceptionOnError)
         throw new Exception($msg);

      return false;
    }
    return true;
  }

  protected function validatesPresenceOfInstituicaoId($raiseExceptionOnEmpty){
    return $this->validatesPresenceOf($this->getRequest()->instituicao_id, 'instituicao_id', $raiseExceptionOnEmpty);
  }

  protected function validatesPresenceOfEscolaId($raiseExceptionOnEmpty){
    return $this->validatesPresenceOf($this->getRequest()->escola_id, 'escola_id', $raiseExceptionOnEmpty);
  }

  protected function validatesPresenceOfAno($raiseExceptionOnEmpty){
    return $this->validatesPresenceOf($this->getRequest()->ano, 'ano', $raiseExceptionOnEmpty);
  }

  protected function validatesPresenceOfMatriculaId($raiseExceptionOnEmpty){
    return $this->validatesPresenceOf($this->getRequest()->matricula_id, 'matricula_id', $raiseExceptionOnEmpty);
  }

  protected function validatesValueOfAttValueIsNumeric($raiseExceptionOnError){
    return $this->validatesValueIsNumeric($this->getRequest()->att_value, 'att_value', $raiseExceptionOnError);
  }

  protected function validatesPresenceOfAttValue($raiseExceptionOnEmpty){
    return $this->validatesPresenceOf($this->getRequest()->att_value, 'att_value', $raiseExceptionOnEmpty);
  }


  protected function validatesPresenceAndValueInSetOfAtt($raiseExceptionOnError){
    $result = $this->validatesPresenceOf($this->getRequest()->att, 'att', $raiseExceptionOnError);

    if ($result){
      $expectedAtts = array('matriculas', 'processamento');
      $result = $this->validatesValueInSetOf($this->getRequest()->att, $expectedAtts, 'att', $raiseExceptionOnError);
    }
    return $result;
  }


  protected function validatesPresenceAndValueInSetOfOper($raiseExceptionOnError){
    $result = $this->validatesPresenceOf($this->getRequest()->oper, 'oper', $raiseExceptionOnError);

    if ($result){
      $expectedOpers = array('post', 'get', 'delete');
      $result = $this->validatesValueInSetOf($this->getRequest()->oper, $expectedOpers, 'oper', $raiseExceptionOnError);
    }
    return $result;
  }

  /* esta funcao só pode ser chamada após setar $this->getService() */
  protected function validatesPresenceOfComponenteCurricularId($raiseExceptionOnEmpty, $addMsgOnEmpty = true)
  {
    return $this->validatesPresenceOf($this->getRequest()->componente_curricular_id, 'componente_curricular_id', $raiseExceptionOnEmpty, $msg = '', $addMsgOnEmpty);
  }


  protected function canAcceptRequest()
  {
    try {
      $this->requiresLogin(true);
      $this->requiresUserIsAdmin(true);
      $this->validatesPresenceAndValueInSetOfAtt(true);
      $this->validatesPresenceAndValueInSetOfOper(true);
    }
    catch (Exception $e){
      return false;
    }
    return true;
  }


  protected function canGetMatriculas(){
    try {
      $this->validatesPresenceOfAno(true);
      $this->validatesPresenceOfInstituicaoId(true);
      $this->validatesPresenceOfEscolaId(true);
    }
    catch (Exception $e){
      return false;
    }
    return true;
  }


  protected function canPostProcessamento(){
    return $this->validatesPresenceOfAno(true) && 
           $this->validatesPresenceOfInstituicaoId(true) &&
           $this->validatesPresenceOfMatriculaId(false);
  }

  protected function canDeleteHistorico(){
    return $this->validatesPresenceOfMatriculaId(false);
  }


  protected function deleteHistorico(){
    $this->appendMsg('#TODO deleteHistorico', 'notice');
  }


  #TODO implement this functions
  protected function getdadosEscola($dadosMatricula){
  }

  protected function getNextSequencial(){
  }

  protected function getdadosEscola($dadosMatricula){
  }

  protected function postProcessamento()  {

    if ($this->canPostProcessamento()){

      $matriculaId = $this->getRequest()->matricula_id;

      try {

        $refCodAluno = $this->getAlunoIdByMatriculaId($matriculaId);
        $ano = $this->getRequest()->ano;
        $isNewHistorico = ! $this->existsHistorico($refCodAluno, $ano);
        $dadosMatricula = $this->getdadosMatricula($matriculaId);

        $dadosEscola = $this->getdadosEscola($dadosMatricula['escola_id']);

        if ($isNewHistorico){

          $historicoEscolar =  new clsPmieducarHistoricoEscolar(
                                  $ref_cod_aluno = $refCodAluno,
                                  $sequencial = $this->getNextSequencial(),
                                  $ref_usuario_exc = null,
                                  $ref_usuario_cad = $this->getSession()->id_pessoa,
                                  /*#TODO nm_curso*/
                                  $nm_serie = $dadosMatricula['nome_serie'], 
                                  $ano = $ano,
                                  $carga_horaria
                                  $dias_letivos
                                  $escola
                                  $escola_cidade
                                  $escola_uf
                                  $observacao
                                  $aprovado
                                  $data_cadastro
                                  $data_exclusao
                                  $ativo
                                  $faltas_globalizadas
                                  $ref_cod_instituicao
                                  $origem
                                  $extra_curricular
                                  $ref_cod_matricula
                                  $frequencia
                                  $registro
                                  $livro
                                  $folha
                                );

          $historicoEscolar->cadastra();
        }
        else{
          $historicoEscolar->edita();
        }

      }
      catch (Exception $e){
        $this->appendMsg('Erro ao processar histórico, detalhes:' . $e, 'error');
        return false;
      }

      //$this->appendMsg('Histórico processado com sucesso', 'success');
      $this->appendMsg('#TODO processar histórico', 'notice');

      $situacaoHistorico = $this->getSituacaoHistorico($refCodAluno , $ano);
      $linkToHistorico = $this->getLinkToHistorico($refCodAluno , $ano);

      $this->appendResponse('matricula_id', $matriculaId);
      $this->appendResponse('situacao_historico', $situacaoHistorico);
      $this->appendResponse('link_to_historico', $linkToHistorico);
    }
  }


  protected function getDadosMatricula($matriculaId){
    $matriculas = array();

    $matriculaTurma = new clsPmieducarMatriculaTurma();
    $matriculaTurma->setOrderby('ref_cod_curso, ref_ref_cod_serie, ref_cod_turma, nome');
    $matriculaTurma = $matriculaTurma->lista($matriculaId);

    $dadosMatricula = array();
    if (is_array($matriculaTurma) && count($matriculaTurma) > 0){
      $dadosMatricula['instituicao_id'] = $matriculaTurma['ref_cod_instituicao'];
      $dadosMatricula['escola_id'] = $matriculaTurma['ref_ref_cod_escola'];
      $dadosMatricula['matricula_id'] = $matriculaTurma['ref_cod_matricula'];
      $dadosMatricula['aluno_id'] = $matriculaTurma['ref_cod_aluno'];
      $dadosMatricula['nome'] = ucwords(strtolower(utf8_encode($matriculaTurma['nome_aluno'])));
      $dadosMatricula['nome_curso'] = ucwords(strtolower(utf8_encode($matriculaTurma['nm_curso'])));
      $dadosMatricula['nome_serie'] = ucwords(strtolower(utf8_encode($this->getNomeSerie($matriculaTurma['ref_ref_cod_serie']))));
      $dadosMatricula['nome_turma'] = ucwords(strtolower(utf8_encode($matriculaTurma['nm_turma'])));
      $dadosMatricula['situacao_historico'] = $this->getSituacaoHistorico($matriculaTurma['ref_cod_aluno'], $this->getRequest()->ano);
      $dadosMatricula['link_to_historico'] = $this->getLinkToHistorico($matriculaTurma['ref_cod_aluno'], $this->getRequest()->ano);
    }

    return $dadosMatricula;
  }


  protected function getAlunoIdByMatriculaId($matriculaId){
    $sql = "select ref_cod_aluno from pmieducar.matricula where cod_matricula = $matriculaId";

    $db = new Db();
    return $db->selectField($sql);
  }


  protected function getNomeSerie($serieId){
    $sql = "select nm_serie from pmieducar.serie where cod_serie = $serieId";

    $db = new Db();
    $nome = $db->select($sql);
    return $nome[0]['nm_serie'];
  }


  protected function existsHistorico($alunoId, $ano){
    $sql = "select 1 from pmieducar.historico_escolar where ref_cod_aluno = $alunoId and ano = $ano";

    $db = new Db();
    return ($db->selectField($sql) == '1');
  }


  protected function getSituacaoHistorico($alunoId, $ano){
    if ($this->existsHistorico($alunoId, $ano))
        $situacao = 'Histórico processado';
    else 
        $situacao = 'Não processado';

    return ucwords(strtolower(utf8_encode($situacao)));
  }


  protected function getLinkToHistorico($alunoId, $ano){
    $sql = "select sequencial from pmieducar.historico_escolar where ref_cod_aluno = $alunoId and ano = $ano";

    $db = new Db();
    $sequencial = $db->selectField($sql);
    
    if (is_numeric($sequencial))
        $link = "/intranet/educar_historico_escolar_det.php?ref_cod_aluno=$alunoId&sequencial=$sequencial";
    else 
        $link = '';

    return $link;
  }


  protected function getMatriculas(){
    $matriculas = array();

    if ($this->canGetMatriculas()){

      
      $alunos = new clsPmieducarMatriculaTurma();
      $alunos->setOrderby('ref_cod_curso, ref_ref_cod_serie, ref_cod_turma, nome');

      $alunos = $alunos->lista(
        $this->getRequest()->matricula_id,
        $this->getRequest()->turma_id,
        NULL,
        NULL,
        NULL,
        NULL,
        NULL,
        NULL,
        1,
        $this->getRequest()->serie_id,
        $this->getRequest()->curso_id,
        $this->getRequest()->escola_id,
        $this->getRequest()->instituicao_id,
        $this->getRequest()->aluno_id,
        NULL,
        NULL,
        NULL,
        NULL,
        $this->getRequest()->ano,
        NULL,
        TRUE,
        NULL,
        NULL,
        TRUE,
        NULL,
        NULL,
        NULL,
        NULL,
        NULL,
        NULL
      );
      

      if (! is_array($alunos))
        $alunos = array();

      foreach($alunos as $aluno)
      {
        $matricula = array();
        //$this->setService($matriculaId = $aluno['ref_cod_matricula']);

        $matricula['matricula_id'] = $aluno['ref_cod_matricula'];
        $matricula['aluno_id'] = $aluno['ref_cod_aluno'];
        $matricula['nome'] = ucwords(strtolower(utf8_encode($aluno['nome_aluno'])));
        $matricula['nome_curso'] = ucwords(strtolower(utf8_encode($aluno['nm_curso'])));
        $matricula['nome_serie'] = ucwords(strtolower(utf8_encode($this->getNomeSerie($aluno['ref_ref_cod_serie']))));
        $matricula['nome_turma'] = ucwords(strtolower(utf8_encode($aluno['nm_turma'])));
        $matricula['situacao_historico'] = $this->getSituacaoHistorico($aluno['ref_cod_aluno'], $this->getRequest()->ano);
        $matricula['link_to_historico'] = $this->getLinkToHistorico($aluno['ref_cod_aluno'], $this->getRequest()->ano);
        $matriculas[] = $matricula;
      }
    }

    return $matriculas;
  }

  protected function saveService()
  {
    try {
      $this->getService()->save();   
    }
    catch (CoreExt_Service_Exception $e){
      //excecoes ignoradas :( servico lanca excecoes de alertas, que não são exatamente erros.
      error_log('CoreExt_Service_Exception ignorada: ' . $e->getMessage());
    }
  }

  protected function getService($raiseExceptionOnErrors = false, $appendMsgOnErrors = true){
    if (isset($this->service) && ! is_null($this->service))
      return $this->service;

    $msg = 'Erro ao recuperar serviço boletim: serviço não definido.';
    if($appendMsgOnErrors)
      $this->appendMsg($msg);

    if ($raiseExceptionOnErrors)
      throw new Exception($msg);

    return null;
  }

  protected function canSetService($validatesPresenceOfMatriculaId = true)
  {
    try {
      $this->requiresLogin(true);
      if ($validatesPresenceOfMatriculaId)
        $this->validatesPresenceOfMatriculaId(true);
    }
    catch (Exception $e){
      return false;
    }
    return true;
  }

  protected function setService($matriculaId = null){
    if ($this->canSetService($validatesPresenceOfMatriculaId = is_null($matriculaId))){
      try {

        if (! $matriculaId)
          $matriculaId = $this->getRequest()->matricula_id;

        $this->service = new Avaliacao_Service_Boletim(array(
            'matricula' => $matriculaId,
            'usuario'   => $this->getSession()->id_pessoa
        ));

      return true;
      }
      catch (Exception $e){
        $this->appendMsg('Exception ao instanciar serviço boletim: ' . $e->getMessage(), 'error', $encodeToUtf8 = true);
      }
    }
    return false;
  }


  protected function notImplementedError()
  {
    $this->appendMsg("Operação '{$this->getRequest()->oper}' inválida para o att '{$this->getRequest()->att}'");    
  }


  public function Gerar(){
    $this->msgs = array();
    $this->response = array();

    if ($this->canAcceptRequest()){
      try {
        if ($this->getRequest()->oper == 'get')
        {
          if ($this->getRequest()->att == 'matriculas')
          {
            $matriculas = $this->getMatriculas();
            $this->appendResponse('matriculas', $matriculas);
          }
          else
            $this->notImplementedError();

        }
        elseif ($this->getRequest()->oper == 'post')
        {
          if ($this->getRequest()->att == 'processamento')
          {
            $this->postProcessamento();
          }
          else
            $this->notImplementedError();  
        }
        elseif ($this->getRequest()->oper == 'delete')
        {
            $this->notImplementedError();
        }
      }
      catch (Exception $e){
        $this->appendMsg('Exception: ' . $e->getMessage(), $type = 'error', $encodeToUtf8 = true);
      }
    }
    echo $this->prepareResponse();
  }

  protected function appendResponse($name, $value){
    $this->response[$name] = $value;
  }

  protected function prepareResponse(){
    $msgs = array();
    $this->appendResponse('att', isset($this->getRequest()->att) ? $this->getRequest()->att : '');

    foreach($this->msgs as $m)
      $msgs[] = array('msg' => $m['msg'], 'type' => $m['type']);
    $this->appendResponse('msgs', $msgs);

    echo json_encode($this->response);
  }

  protected function appendMsg($msg, $type="error", $encodeToUtf8 = false){
    if ($encodeToUtf8)
      $msg = utf8_encode($msg);

    error_log("$type msg: '$msg'");
    $this->msgs[] = array('msg' => $msg, 'type' => $type);
  }

  public function generate(CoreExt_Controller_Page_Interface $instance){
    header('Content-type: application/json');
    $instance->Gerar();
  }
}
