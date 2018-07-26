<?php

require_once 'include/clsBase.inc.php';
require_once 'include/clsCadastro.inc.php';
require_once 'include/clsBanco.inc.php';
require_once 'include/pmieducar/geral.inc.php';
require_once 'include/modules/clsModulesProfessorTurma.inc.php';
require_once 'Portabilis/String/Utils.php';
require_once 'Portabilis/Utils/Database.php';

class clsIndexBase extends clsBase
{
    public function Formular()
    {
        $this->SetTitulo($this->_instituicao . ' Servidores - Servidor vínculo turma');
        $this->processoAp = 635;
    }
}

class indice extends clsCadastro
{
    public $pessoa_logada;

    public $id;
    public $ano;
    public $servidor_id;
    public $funcao_exercida;
    public $tipo_vinculo;
    public $permite_lancar_faltas_componente;

    public $ref_cod_instituicao;
    public $ref_cod_escola;
    public $ref_cod_curso;
    public $ref_cod_serie;
    public $ref_cod_turma;

    public function Inicializar()
    {
        $retorno = '';

        @session_start();
        $this->pessoa_logada = $_SESSION['id_pessoa'];
        @session_write_close();

        $this->servidor_id    = $_GET['ref_cod_servidor'];
        $this->ref_cod_instituicao = $_GET['ref_cod_instituicao'];
        $this->id = $_GET['id'];

        // URL para redirecionamento
        $backUrl = sprintf(
            'educar_servidor_vinculo_turma_lst.php?ref_cod_servidor=%d&ref_cod_instituicao=%d',
            $this->servidor_id,
            $this->ref_cod_instituicao
        );

        $obj_permissoes = new clsPermissoes();
        $obj_permissoes->permissao_cadastra(635, $this->pessoa_logada, 7, $backUrl);

        if ($obj_permissoes->permissao_excluir(635, $this->pessoa_logada, 7)) {
            $this->fexcluir = true;
        }

        $retorno = 'Novo';

        if (is_numeric($this->id)) {
            $obj = new clsModulesProfessorTurma($this->id);

            $registro  = $obj->detalhe();

            if ($registro) {
                $this->ref_cod_turma = $registro['turma_id'];
                $this->funcao_exercida = $registro['funcao_exercida'];
                $this->tipo_vinculo = $registro['tipo_vinculo'];
                $this->permite_lancar_faltas_componente = $registro['permite_lancar_faltas_componente'];

                $obj_turma = new clsPmieducarTurma($this->ref_cod_turma);
                $obj_turma = $obj_turma->detalhe();
                $this->ref_cod_escola = $obj_turma['ref_ref_cod_escola'];

                $this->ref_cod_curso = $obj_turma['ref_cod_curso'];
                $this->ref_cod_serie = $obj_turma['ref_ref_cod_serie'];

                if (!isset($_GET['copia'])) {
                    $retorno     = 'Editar';
                }

                if (isset($_GET['copia'])) {
                    $this->ano = date('Y');
                }
            }
        }

        $this->url_cancelar = ($retorno == 'Editar') ?
        'educar_servidor_vinculo_turma_det.php?id=' . $this->id :
        $backUrl;

        $this->nome_url_cancelar = 'Cancelar';

        $nomeMenu = $retorno == 'Editar' ? $retorno : 'Cadastrar';
        $localizacao = new LocalizacaoSistema();
        $localizacao->entradaCaminhos([
            $_SERVER['SERVER_NAME'].'/intranet' => 'Início',
            'educar_servidores_index.php' => 'Servidores',
            '' => "{$nomeMenu} vínculo do servidor à turma"
        ]);
        $this->enviaLocalizacao($localizacao->montar());

        return $retorno;
    }

    public function Gerar()
    {
        if ($this->id) {
            $objProfessorTurma = new clsModulesProfessorTurma($this->id);
            $detProfessorTurma = $objProfessorTurma->detalhe();
            $ano = $detProfessorTurma['ano'];
        }

        if (isset($_GET['copia'])) {
            $ano = null;
        }

        $this->campoOculto('id', $this->id);
        $this->campoOculto('servidor_id', $this->servidor_id);
        $this->inputsHelper()->dynamic('ano', ['value' => (is_null($ano) ? date('Y') : $ano)]);
        $this->inputsHelper()->dynamic(['instituicao', 'escola', 'curso', 'serie']);
        $this->inputsHelper()->dynamic(['turma'], ['required' => !is_null($this->ref_cod_turma)]);

        $obrigarCamposCenso = $this->validarCamposObrigatoriosCenso();
        $this->campoOculto('obrigar_campos_censo', (int) $obrigarCamposCenso);

        $resources = [
            null  => 'Selecione',
            1 => 'Docente',
            2 => 'Auxiliar/Assistente educacional',
            3 => 'Profissional/Monitor de atividade complementar',
            4 => 'Tradutor Intérprete de LIBRAS',
            5 => 'Docente titular - Coordenador de tutoria (de módulo ou disciplina) - EAD',
            6 => 'Docente tutor - Auxiliar (de módulo ou disciplina) - EAD'
        ];

        $options = [
            'label' => 'Função exercida',
            'resources' => $resources,
            'value' => $this->funcao_exercida
        ];
        $this->inputsHelper()->select('funcao_exercida', $options);

        $resources = [
            null => 'Nenhum',
            1 => 'Concursado/efetivo/estável',
            2 => 'Contrato temporário',
            3 => 'Contrato terceirizado',
            4 => 'Contrato CLT'
        ];

        $options = [
            'label' => 'Tipo do vínculo',
            'resources' => $resources,
            'value' => $this->tipo_vinculo,
            'required' => false
        ];
        $this->inputsHelper()->select('tipo_vinculo', $options);

        $options = [
            'label' => 'Professor de área específica?',
            'value' => $this->permite_lancar_faltas_componente,
            'help' =>  'Marque esta opção somente se o professor leciona uma disciplina específica na turma selecionada.'
        ];

        $this->inputsHelper()->checkbox('permite_lancar_faltas_componente', $options);

        $this->inputsHelper()->checkbox('selecionar_todos', ['label' => 'Selecionar/remover todos']);
        $this->inputsHelper()->multipleSearchComponenteCurricular(null, ['label' => 'Componentes lecionados', 'required' => true]);

        $scripts = [
            '/modules/Cadastro/Assets/Javascripts/ServidorVinculoTurma.js'
        ];
        Portabilis_View_Helper_Application::loadJavascript($this, $scripts);
    }

    public function Novo()
    {
        @session_start();
        $this->pessoa_logada = $_SESSION['id_pessoa'];
        @session_write_close();

        $backUrl = sprintf(
            'educar_servidor_vinculo_turma_lst.php?ref_cod_servidor=%d&ref_cod_instituicao=%d',
            $this->servidor_id,
            $this->ref_cod_instituicao
        );

        $obj_permissoes = new clsPermissoes();
        $obj_permissoes->permissao_cadastra(635, $this->pessoa_logada, 7, $backUrl);

        if ($this->ref_cod_turma) {
            $obj = new clsModulesProfessorTurma(null, $this->ano, $this->ref_cod_instituicao, $this->servidor_id, $this->ref_cod_turma, $this->funcao_exercida, $this->tipo_vinculo, $this->permite_lancar_faltas_componente);
            if ($obj->existe2()) {
                $this->mensagem .= 'Não é possível cadastrar pois já existe um vínculo com essa turma.<br>';

                return false;
            } else {
                $this->gravaComponentes($obj->cadastra());
            }
        } else {
            $obj = new clsPmieducarTurma();
            foreach ($obj->lista(null, null, null, $this->ref_cod_serie, $this->ref_cod_escola, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, $this->ano) as $reg) {
                $obj = new clsModulesProfessorTurma(null, $this->ano, $this->ref_cod_instituicao, $this->servidor_id, $reg['cod_turma'], $this->funcao_exercida, $this->tipo_vinculo, $this->permite_lancar_faltas_componente);
                $this->gravaComponentes($obj->cadastra());
            }
        }

        $this->mensagem .= 'Cadastro efetuado com sucesso.<br>';
        header('Location: ' . $backUrl);
        die();
    }

    public function Editar()
    {
        @session_start();
        $this->pessoa_logada = $_SESSION['id_pessoa'];
        @session_write_close();

        $backUrl = sprintf(
            'educar_servidor_vinculo_turma_lst.php?ref_cod_servidor=%d&ref_cod_instituicao=%d',
            $this->servidor_id,
            $this->ref_cod_instituicao
        );

        $obj_permissoes = new clsPermissoes();
        $obj_permissoes->permissao_cadastra(635, $this->pessoa_logada, 7, $backUrl);

        $obj = new clsModulesProfessorTurma($this->id, $this->ano, $this->ref_cod_instituicao, $this->servidor_id, $this->ref_cod_turma, $this->funcao_exercida, $this->tipo_vinculo, $this->permite_lancar_faltas_componente);

        if ($obj->existe2()) {
            $this->mensagem .= 'Não é possível cadastrar pois já existe um vínculo com essa turma.<br>';

            return false;
        }
        $obj->edita();
        $this->gravaComponentes($this->id);

        $this->mensagem .= 'Edição efetuada com sucesso.<br>';
        header('Location: ' . $backUrl);
        die();
    }

    public function Excluir()
    {
        @session_start();
        $this->pessoa_logada = $_SESSION['id_pessoa'];
        @session_write_close();

        $backUrl = sprintf(
            'educar_servidor_vinculo_turma_lst.php?ref_cod_servidor=%d&ref_cod_instituicao=%d',
            $this->servidor_id,
            $this->ref_cod_instituicao
        );

        $obj_permissoes = new clsPermissoes();
        $obj_permissoes->permissao_excluir(635, $this->pessoa_logada, 7, $backUrl);

        $this->excluiComponentes($this->id);
        $obj = new clsModulesProfessorTurma($this->id);
        $obj->excluir();

        $this->mensagem .= 'Exclusão efetuada com sucesso.<br>';
        header('Location:' . $backUrl);
        die();
    }

    public function gravaComponentes($professor_turma_id)
    {
        $this->excluiComponentes($professor_turma_id);
        foreach ($this->getRequest()->componentecurricular as $componenteCurricularId) {
            if (! empty($componenteCurricularId)) {
                Portabilis_Utils_Database::fetchPreparedQuery('INSERT INTO modules.professor_turma_disciplina VALUES ($1,$2)', [ 'params' =>  [$professor_turma_id, $componenteCurricularId] ]);
            }
        }
    }

    public function excluiComponentes($professor_turma_id)
    {
        Portabilis_Utils_Database::fetchPreparedQuery('DELETE FROM modules.professor_turma_disciplina WHERE professor_turma_id = $1', [ 'params' => [$professor_turma_id]]);
    }
}

// Instancia objeto de página
$pagina = new clsIndexBase();

// Instancia objeto de conteúdo
$miolo = new indice();

// Atribui o conteúdo à  página
$pagina->addForm($miolo);

// Gera o código HTML
$pagina->MakeAll();
