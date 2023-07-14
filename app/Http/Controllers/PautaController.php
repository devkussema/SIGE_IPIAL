<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\{
    Aluno_turma,Aluno,User,AnoTurmaCood,
    Ano_lectivo,Disciplina,Turma,
};

class PautaController extends Controller
{   

    //Metodo que retorna Pagina de Views da Pauta
    public function index()
    {
        $anoTurmaCoord = $this->buscarAnoTurmaCoord();
        return view('pauta.pautas', ['anoTurmaCoord' => $anoTurmaCoord]);
    }

    //Metodo que retorna todos os dados da Tabela Ano Turma Coord
    private function buscarAnoTurmaCoord()
    {
        $anoTurmaCoord = AnoTurmaCood::all()->toArray();

        foreach ($anoTurmaCoord as &$item){
            $turma = Turma::find($item['turma_id']);
            $anoLectivo = Ano_lectivo::find($item['ano_lectivo_id']);

            $item['turma'] = $turma;
            $item['anoLectivo'] = $anoLectivo;
        }
        return $anoTurmaCoord;
    }


    public static function show($id,$anoLectivo):mixed
    {
    
        $turma= Turma::find($id); //Busca na turma
        $ano= Ano_lectivo::find($anoLectivo);//Buscar Ano Lectivo

        $anoTurmaCoord = AnoTurmaCood::where('turma_id', $turma->turma_id)->first();
        $turmaAluno = Aluno_turma::where('turmaAno_id', $anoTurmaCoord->turmaAno_id)->get();

        $dadosAssinantes=self::entidadesAssinantes(); //Dados das Assinantes da Pauta
        $alunos = Aluno::find($turmaAluno);//Buscar os alunos pertecente a turma buscada acima. 
        
        //Buscar as Disciplinas de uma Turma pelos seu Curso
        $disciplinaGerais= Disciplina::where('componente','Cientificas')
                                        ->Orwhere('componente','Socio-culturais')
                                        ->get();

        $disciplinaEspecificas= Disciplina::where('componente','Técnicas')
                                            ->where('curso_id',$turma->curso->curso_id)
                                            ->get();

        //Combinei as duas coleções de disciplinas(Tecnicas e Gerais) em uma única variável            
        $disciplinasAll= $disciplinaGerais->concat($disciplinaEspecificas)->all();

        //Busca todas as diciplinas que existem em uma determinada turma
        foreach($disciplinasAll as $value){
            $disciplinas[]=$value;//As Disciplinas

            //As Medias com base ao numero de alunos
            foreach($alunos as $aluno){
                //Busca da Media dos alunos com base a Disciplina
                $OneMedia[]= MediasController::getMediaOneTrimestre($value->disciplina_id,$aluno->aluno_id, $anoLectivo); 
                $TwoMedia[]= MediasController::getMediaTwoTrimestre($value->disciplina_id,$aluno->aluno_id, $anoLectivo); 
                $ThreeMedia[]= MediasController::getMediaThreeTrimestre($value->disciplina_id,$aluno->aluno_id, $anoLectivo); 
            } 
        }   

        //Condição para Pauta ser Gerada
        if ( (count($turmaAluno) <= 0) && (empty($notas)) ){
            return redirect()->back()->with('msg_sem_pauta',"Lamentamos! Esta pauta ainda não esta composta... Aguarde o lançamento das notas");
        }

       
        // //1º Trimestre
        // foreach ($OneMedia as $valueMedias) { 
        //     $OneMedia[]= $valueMedias;
        // }
        // //2º Trimestre
        // foreach ($TwoMedia as $valueMedias) { 
        //     $OneMedia[]= $valueMedias;
        // }
        // //3º Trimestre
        // foreach ($ThreeMedia as $valueMedias) { 
        //     $OneMedia[]= $valueMedias;
        // }
        

     
        //Dados completos que vão para compor a pauta. 
        $dadosPauta= [
            'alunos' => $alunos, 
            'anoTurmaCoord' => $anoTurmaCoord, 
            'dadosAssinantes'=>$dadosAssinantes,
            'disciplinas'=>$disciplinas,
            'turma'=>$turma,  
            'ano'=>$ano,  
            'OneMedia'=>$OneMedia,
            'TwoMedia'=>$TwoMedia,
            'ThreeMedia'=>$ThreeMedia,
            'colspanDisciplina'=>(count($disciplinas)*6),

        ];  
        
        //Condições da apresentação da Pauta com base a class
        switch ($turma->classe_id){
            case 1:
                return view('pauta.pauta-10-doc',compact('dadosPauta') );
            case 2:
                return view('pauta.pauta-11-doc',compact('dadosPauta'));
            case 3:
                return view('pauta.pauta-12-doc', $dadosPauta);
            case 4:
                return view('pauta.pauta-13-doc', $dadosPauta);
            default:
                return redirect()->back()->with('erro_anormal',"Lamentamos, Detectamos um comportamento anormal e grave no sistema...");
        }

    }

    //Metodo que retorna todos os usuario que devem assinar a Pauta
    private static function entidadesAssinantes():mixed
    {
        $director = User::where('cargo_usuario', 'Director')->where('status_usuario',1)->first();
        $subdirector = User::where('cargo_usuario', 'Subdirector')->where('status_usuario',1)->first();
        $coordenadorArea= User::where('cargo_usuario', 'coordenacao')->where('status_usuario',1)->first();
  
        return[
            'director'=>$director,
            'subdirector'=>$subdirector,
            'coordenadorArea'=>$coordenadorArea,
            'directorTurma'=>" ",
        ];
    } 

   

}
