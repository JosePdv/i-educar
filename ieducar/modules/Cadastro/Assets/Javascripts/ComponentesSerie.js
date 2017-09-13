var modoCadastro   = $j('#retorno').val() == 'Novo';
var modoEdicao     = $j('#retorno').val() == 'Editar';
var instituicao_id = $j('#ref_cod_instituicao').val();
var curso_id       = $j('#curso_id').val();
var serie_id       = $j('#serie_id').val();
var fieldArea      = $j('#ref_cod_area_conhecimento');
var comboCurso     = $j('#ref_cod_curso');
var comboSerie     = $j('#ref_cod_serie');
var chosenOldArray = [];
var guardaAreas    = [];

if(modoEdicao){
    $j('#ref_cod_instituicao').attr('disabled', 'true');
    $j('#ref_cod_curso').attr('disabled', 'true');
    $j('#ref_cod_serie').attr('disabled', 'true');
    getCursos();
    getSeries();
    updateAreaConhecimento();
}

$j("#ref_cod_instituicao").change(function() {
    instituicao_id = $j('#ref_cod_instituicao').val();
    if (instituicao_id != '') {
        getCursos();
    }else{
        comboCurso.empty();
        comboCurso.append('<option value="">Selecione um curso</option>');
    }
    updateAreaConhecimento();
});

$j("#ref_cod_curso").change(function() {
    curso_id = $j('#ref_cod_curso').val();
    if (curso_id != '') {
        getSeriesSemComponentesViculados();
    }else{
        comboSerie.empty();
        comboSerie.append('<option value="">Selecione uma série</option>');
    }
});

$j("#ref_cod_area_conhecimento").change(function() {
    var chosenArray = $j("#ref_cod_area_conhecimento").chosen().val();
    if (!chosenOldArray) {
        chosenOldArray = [];
    }
    if(chosenArray && chosenOldArray){
        if (chosenArray.length > chosenOldArray.length) {
            chosenArray.forEach(function(area) {
                nome_area = $j(this).find("option[value='"+ area +"']").text();
                if (!$j('#area_conhecimento_' + area).length && area != '') {
                    $j('#componentes').append(htmlCabecalhoAreaConhecimento(area, nome_area));
                    carregaComponentesDaArea(area);
                }
            }, this);
        }else{
            chosenOldArray.forEach(function(area) {
                var areaExcluida = '';
                if($j.inArray(area,chosenArray) == -1){
                    areaExcluida = area;
                };
                $j('#area_conhecimento_'+areaExcluida).remove();
                $j('.area_conhecimento_'+areaExcluida).remove();
            }, this);
        }
    }else{
        $j('#componentes').empty();
    }
    chosenOldArray = chosenArray;
} );

function checkAll(id){
    var isChecked = $j('#check-all-'+id).is(':checked');
    $j( '.check_componente_area_' + id).prop( "checked", isChecked );
}

function expandClose(id){
    var expand = $j('.area_conhecimento_'+id).is(':visible');
    $j('.area_conhecimento_'+id).toggle('fast');
    if(expand){
        $j('#expandClose_'+id).css('background-image','url(/intranet/imagens/arrow-down2.png)');
    }else{
        $j('#expandClose_'+id).css('background-image','url(/intranet/imagens/arrow-up2.png)');
    }
}

function getCursos(){
    var url = getResourceUrlBuilder.buildUrl('/module/Api/Curso',
                                             'cursos',
                                             { instituicao_id : instituicao_id }
    );
    var options = {
        url      : url,
        dataType : 'json',
        success  : handleGetCursos
    };
    getResources(options);
}

function handleGetCursos(response){
    var cursos   = response.cursos;
    var selected = '';
    
    comboCurso.empty();
    comboCurso.append('<option value="">Selecione um curso</option>');

    for (var i = 0; i <= cursos.length - 1; i++) {
        if (cursos[i].id == curso_id) {
            selected = 'selected';
        }else{
            selected = ''
        }
        comboCurso.append('<option value="' + cursos[i].id + '"' + selected + '>' + cursos[i].nome + '</option>');
    }
}

function getSeries(){
    var url = getResourceUrlBuilder.buildUrl('/module/Api/Serie',
                                             'series-curso',
                                             { curso_id : curso_id }
    );
    var options = {
        url      : url,
        dataType : 'json',
        success  : handleGetSeries
    };
    getResources(options);
}

function getSeriesSemComponentesViculados(){
    var url = getResourceUrlBuilder.buildUrl('/module/Api/Serie',
                                             'series-curso-sem-componentes',
                                             { curso_id : curso_id }
    );
    var options = {
        url      : url,
        dataType : 'json',
        success  : handleGetSeries
    };
    getResources(options);
}

function handleGetSeries(response){
    var series   = response.series;
    var selected = '';
    comboSerie.empty();
    if(series.length == 0){
        comboSerie.append('<option value="">Sem opções</option>');
    }else{
        comboSerie.append('<option value="">Selecione uma série</option>');
    }
    for (var i = 0; i <= series.length - 1; i++) {
        if (series[i].id == serie_id) {
            selected = 'selected';
        }else{
            selected = ''
        }
        comboSerie.append('<option value="' + series[i].id + '"' + selected + '>' + series[i].nome + '</option>');
    }
}

function carregaDadosComponentesSerie(){
    var url = getResourceUrlBuilder.buildUrl('/module/Api/ComponenteCurricular',
                                             'componentes-curriculares-serie',
                                             { instituicao_id : instituicao_id,
                                               serie_id       : serie_id }
    );
    var options = {
        url      : url,
        dataType : 'json',
        success  : handleCarregaDadosComponentesSerie
    };
    getResources(options);
}

function handleCarregaDadosComponentesSerie(response){
    componentes = response.disciplinas;
    console.log(response.disciplinas);
    componentes.forEach(function(componente) {
        $j( '#componente_' + componente.id).prop( "checked", true );
        $j( '#carga_horaria_' + componente.id ).val(componente.carga_horaria);
        $j( '#tipo_nota_' + componente.id ).val(componente.tipo_nota);
    }, this);
}

function carregaComponentesDaArea(id){
    var url = getResourceUrlBuilder.buildUrl('/module/Api/ComponenteCurricular',
                                             'componentes-curriculares',
                                             { instituicao_id       : instituicao_id,
                                               area_conhecimento_id : id }
    );
    var options = {
        url      : url,
        dataType : 'json',
        success  : handleCarregaComponentesDaArea
    };
    getResources(options);
}

function handleCarregaComponentesDaArea(response){
    var componentes          = response.disciplinas;
    var urlRequisicao        = new URLSearchParams(this.url);
    var area_conhecimento_id = urlRequisicao.get('area_conhecimento_id');

    for (var i = componentes.length - 1; i >= 0 ; i--) {
        $j(htmlComponentesAreaConhecimento(componentes[i].area_conhecimento_id, componentes[i].id, componentes[i].nome)).insertAfter('#area_conhecimento_' + componentes[i].area_conhecimento_id);
    }

    $j(htmlSubCabecalhoAreaConhecimento(area_conhecimento_id)).insertAfter('#area_conhecimento_' + area_conhecimento_id);

    if(serie_id != ''){
        carregaDadosComponentesSerie();
    }
}

function handleGetAreaConhecimento(response) {
    var areaConhecimentoField = $j('#ref_cod_area_conhecimento');

    var selectOptions = {};

    response['areas'].forEach((area) => {
    selectOptions[area.id] = area.nome
    }, {});
    
    updateChozen(areaConhecimentoField, selectOptions);

    if (serie_id != '') { 
        getAreaConhecimentoSerie();
    }
}

function updateAreaConhecimento(){
      var instituicao_id = $j('#ref_cod_instituicao').val();
      var areaConhecimentoField = $j('#ref_cod_area_conhecimento');

      clearValues(areaConhecimentoField);
      if (instituicao_id != '') {

        var url = getResourceUrlBuilder.buildUrl('/module/Api/AreaConhecimento', 'areas-de-conhecimento', {
          instituicao_id : instituicao_id
        });

        var options = {
          url : url,
          dataType : 'json',
          success  : handleGetAreaConhecimento
        };

        getResources(options);
      }
}

function handleGetAreaConhecimentoSerie(response) {
    $j('#ref_cod_area_conhecimento').val('').trigger('liszt:updated');
    $j.each(response['options'], function(id,nome) {
        $j("#ref_cod_area_conhecimento").children("[value=" + id + "]").attr('selected', '');
        $j("#ref_cod_area_conhecimento").chosen().trigger("chosen:updated");
        $j('#componentes').append(htmlCabecalhoAreaConhecimento(id, nome));
        carregaComponentesDaArea(id);
    });
    chosenOldArray = $j("#ref_cod_area_conhecimento").chosen().val();
}


function getAreaConhecimentoSerie(){
    var url = getResourceUrlBuilder.buildUrl('/module/Api/AreaConhecimento', 'areaconhecimento-serie', {
        serie_id : serie_id
    });
    var options = {
        url : url,
        dataType : 'json',
        success  : handleGetAreaConhecimentoSerie
    };
    getResources(options);
}

function htmlCabecalhoAreaConhecimento(id, nome){
    return `<tr id="area_conhecimento_` + id + `"
                class="area_conhecimento_title">
                <td colspan="2">` + nome + `</td>
                <td class="td_check_all">
                </td>
                <td style="text-align: right;">
                     <div id="expandClose_` + id + `"
                          onClick="expandClose(` + id + `)"
                          style="background-image: url(/intranet/imagens/arrow-up2.png);
                                 width: 15px;
                                 height: 15px;
                                 background-size: cover;
                                 float: right;
                                 cursor: pointer;"/>
                     </div>
                </td>
            </tr>`;
}

function htmlSubCabecalhoAreaConhecimento(id){
    return `<tr class="area_conhecimento_` + id + `">
                <td colspan="2">
                    <label>
                        <input onClick="checkAll(` + id + `)" id="check-all-` + id + `" type="checkbox"/>
                        <b>Nome</b>
                    </label>
                </td>
                <td>
                    <b>Carga horária</b>
                </td>
                <td>
                    <b>Tipo de nota</b>
                </td>
            </tr>`;
}

function htmlComponentesAreaConhecimento(id, componente_id, componente_nome){
    return `<tr class="area_conhecimento_` + id + `">
                <td colspan="2">
                    <label>
                        <input type="checkbox"
                               name="componentes[` + id + componente_id + `][id]"
                               class="check_componente_area_`+ id +`"
                               id="componente_` + componente_id + `"
                               value="` + componente_id + `">` +
                        componente_nome +
                    `</label>
                </td>
                <td>
                    <input type="text"
                           size="5"
                           maxlength="5"
                           name="componentes[` + id + componente_id + `][carga_horaria]"
                           class="carga_horaria"
                           id="carga_horaria_` + componente_id + `" value="">
                </td>
                <td>
                    <select name="componentes[` + id + componente_id + `][tipo_nota]"
                            class="tipo_nota"
                            id="tipo_nota_` + componente_id + `">
                        <option value="1">Conceitual</option>
                        <option value="2">Numérica</option>
                    </select>
                </td>
            </tr>`;
}