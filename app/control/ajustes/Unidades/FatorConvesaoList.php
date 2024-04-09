<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TPageNavigation;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Util\TDropDown;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class FatorConvesaoList extends TPage
{
  private $form;
  private $datagrid;
  private $pageNavigation;
  private $formgrid;
  private $deleteButton;

  use Adianti\base\AdiantiStandardListTrait;

  public function __construct()
  {

    parent::__construct();


    //Conexão com a tabela
    $this->setDatabase('sample');
    $this->setActiveRecord('Fator_Convesao');
    $this->setDefaultOrder('id', 'asc');
    $this->setLimit(5);

    $this->addFilterField('unidade_origem', '=', 'unidade_origem');
    $this->addFilterField('unidade_destino', '=', 'unidade_destino');

    //Criação do formulario 
    $this->form = new BootstrapFormBuilder('form_search_fator');
    $this->form->setFormTitle('Unidades de Medida');

    //Criação de fields
    $unidade_origem = new TEntry('unidade_origem');
    $unidade_destino = new TEntry('unidade_destino');
    $fator = new TEntry('fator');

    //Add filds na tela
    $this->form->addFields([new TLabel('Origem')], [$unidade_origem]);
    $this->form->addFields([new TLabel('Destino')], [$unidade_destino]);

    //Tamanho dos fields
    $unidade_origem->setSize('100%');
    $unidade_destino->setSize('100%');

    $this->form->setData(TSession::getValue(__CLASS__ . '_filter_data'));

    //Adicionar field de busca
    $btn = $this->form->addAction(_t('Find'), new TAction([$this, 'onSearch']), 'fa:search');
    $btn->class = 'btn btn-sm btn-primary';
    $this->form->addActionLink(_t('New'), new TAction(['FatorConvesaoForm', 'onEdit'], ['register_state' => 'false']), 'fa:plus green');

    //Criando a data grid
    $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
    $this->datagrid->style = 'width: 100%';

    //Criando colunas da datagrid
    $column_id = new TDataGridColumn('id', 'Cod.', 'center', '10%');
    $column_origem = new TDataGridColumn('unidadeOri->nome', 'Origem', 'left');
    $column_destino = new TDataGridColumn('unidadeDes->nome', 'Destino', 'left');
   // $column_fator = new TDataGridColumn('fator', 'Fator', 'left');

    //add coluna da datagrid
    $this->datagrid->addColumn($column_id);
    $this->datagrid->addColumn($column_origem);
    $this->datagrid->addColumn($column_destino);
   // $this->datagrid->addColumn($column_fator);

    //Criando ações para o datagrid
    $column_id->setAction(new TAction([$this, 'onReload']), ['order' => 'id']);
    $column_origem->setAction(new TAction([$this, 'onReload']), ['order' => 'unidade_origem']);
    $column_destino->setAction(new TAction([$this, 'onReload']), ['order' => 'unidade_destino']);
    //$column_fator->setAction(new TAction([$this, 'onReload']), ['order' => 'fator']);

    $action1 = new TDataGridAction(['FatorConvesaoForm', 'onEdit'], ['id' => '{id}', 'register_state' => 'false']);
    $action2 = new TDataGridAction([$this, 'onDelete'], ['id' => '{id}']);

    //Adicionando a ação na tela
    $this->datagrid->addAction($action1, _t('Edit'), 'fa:edit blue');
    $this->datagrid->addAction($action2, _t('Delete'), 'fa:trash-alt red');


    //Criar datagrid 
    $this->datagrid->createModel();

    //Criação de paginador
    $this->pageNavigation = new TPageNavigation;
    $this->pageNavigation->setAction(new TAction([$this, 'onReload']));



    //Enviar para tela
    $panel = new TPanelGroup('', 'white');
    $panel->add($this->datagrid);
    $panel->addFooter($this->pageNavigation);

    //Exportar
    $drodown = new TDropDown('Exportar', 'fa:list');
    $drodown->setPullSide('right');
    $drodown->setButtonClass('btn btn-default waves-effect dropdown-toggle');
    $drodown->addAction('Salvar como CSV', new TAction([$this, 'onExportCSV'], ['register_state' => 'false', 'static' => '1']), 'fa:table green');
    $drodown->addAction('Salvar como PDF', new TAction([$this, 'onExportPDF'], ['register_state' => 'false',  'static' => '1']), 'fa:file-pdf red');
    $panel->addHeaderWidget($drodown);

    //Vertical container
    $container = new TVBox;
    $container->style = 'width: 100%';
    $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
    $container->add($this->form);
    $container->add($panel);

    parent::add($container);
  }
 

  
}
